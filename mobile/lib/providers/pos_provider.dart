// lib/providers/pos_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/pos_repository.dart';
import '../models/pos/pos_request_model.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import 'auth_provider.dart';

class PosState {
  final bool isLoading;
  final String? error;
  final PosRequestModel? currentPosRequest;
  final bool hasChecked; // true after first fetch attempt

  const PosState({
    this.isLoading = false,
    this.error,
    this.currentPosRequest,
    this.hasChecked = false,
  });

  PosState copyWith({
    bool? isLoading,
    String? error,
    PosRequestModel? currentPosRequest,
    bool? hasChecked,
    bool clearError = false,
    bool clearRequest = false,
  }) {
    return PosState(
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
      currentPosRequest:
          clearRequest ? null : (currentPosRequest ?? this.currentPosRequest),
      hasChecked: hasChecked ?? this.hasChecked,
    );
  }
}

class PosNotifier extends StateNotifier<PosState> {
  final PosRepository _repo;
  final Ref _ref;
  PosNotifier(this._repo, this._ref) : super(const PosState());

  /// Refresh wallet balances into the auth state after a request so the home
  /// screen updates on its own. Fire-and-forget — any fee has already moved.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  // ── GET existing request ──────────────────────────────────────────────────
  Future<void> getPosRequest() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      print('💳 PosProvider: Getting POS request');
      final response = await _repo.getPosRequest();

      if (response.success && response.data != null) {
        // API may return data under 'data' key or at root
        final raw = response.data!['data'] ?? response.data!;
        PosRequestModel? model;
        if (raw is Map<String, dynamic> && raw.isNotEmpty) {
          try {
            model = PosRequestModel.fromJson(raw);
          } catch (_) {}
        }
        state = state.copyWith(
          isLoading: false,
          hasChecked: true,
          currentPosRequest: model,
          clearError: true,
        );
      } else {
        // No existing request found — that's OK, show the form
        state = state.copyWith(
          isLoading: false,
          hasChecked: true,
          clearRequest: true,
          clearError: true,
        );
      }
    } catch (e) {
      print('💳 PosProvider: Error - $e');
      // On error still show the form (don't block the user)
      state = state.copyWith(
        isLoading: false,
        hasChecked: true,
        clearRequest: true,
        error: e.toString(),
      );
    }
  }

  // ── POST new request ──────────────────────────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> submitPosRequest({
    required String businessAddress,
    required String numberOfTerminal,
    required String transactionVolume,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final request = PosRequestModel(
        businessAddress: businessAddress,
        numberOfTerminal: numberOfTerminal,
        transactionVolume: transactionVolume,
      );
      final response = await _repo.submitPosRequest(request);

      if (response.success) {
        // Refresh to get the newly created request
        await getPosRequest();
        _refreshBalances();
      } else {
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to submit POS request',
        );
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  void clearError() => state = state.copyWith(clearError: true);
}

// ─── Providers ────────────────────────────────────────────────────────────────

final posRepositoryProvider = Provider<PosRepository>((ref) {
  return PosRepository(ApiService.instance);
});

final posNotifierProvider =
    StateNotifierProvider<PosNotifier, PosState>((ref) {
  return PosNotifier(ref.watch(posRepositoryProvider), ref);
});

final posLoadingProvider =
    Provider<bool>((ref) => ref.watch(posNotifierProvider).isLoading);

final posErrorProvider =
    Provider<String?>((ref) => ref.watch(posNotifierProvider).error);

final posCurrentRequestProvider = Provider<PosRequestModel?>(
    (ref) => ref.watch(posNotifierProvider).currentPosRequest);
