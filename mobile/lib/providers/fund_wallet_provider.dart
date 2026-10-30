// lib/providers/fund_wallet_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/fund_wallet_repository.dart';
import '../models/fund_wallet/virtual_account_model.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';

// ─── State ────────────────────────────────────────────────────────────────────

class FundWalletState {
  final bool isLoading;
  final String? error;
  final VirtualAccountModel? virtualAccount;
  final bool hasChecked;

  const FundWalletState({
    this.isLoading = false,
    this.error,
    this.virtualAccount,
    this.hasChecked = false,
  });

  FundWalletState copyWith({
    bool? isLoading,
    String? error,
    VirtualAccountModel? virtualAccount,
    bool? hasChecked,
    bool clearError = false,
  }) {
    return FundWalletState(
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
      virtualAccount: virtualAccount ?? this.virtualAccount,
      hasChecked: hasChecked ?? this.hasChecked,
    );
  }

  bool get hasAccount => virtualAccount?.hasAccount == true;
}

// ─── Notifier ─────────────────────────────────────────────────────────────────

class FundWalletNotifier extends StateNotifier<FundWalletState> {
  final FundWalletRepository _repo;

  FundWalletNotifier(this._repo) : super(const FundWalletState());

  Future<void> loadVirtualAccount() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _repo.getVirtualAccount();
      VirtualAccountModel? account;
      if (response.success && response.data != null) {
        // Data might be at root or under 'data' key
        final raw = response.data!['data'] ?? response.data!;
        if (raw is Map<String, dynamic>) {
          account = VirtualAccountModel.fromJson(raw);
        }
      }
      state = state.copyWith(
        isLoading: false,
        hasChecked: true,
        virtualAccount: account,
        error: response.success ? null : response.message,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, hasChecked: true, error: e.toString());
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> createVirtualAccount() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _repo.createVirtualAccount();
      VirtualAccountModel? account;
      if (response.success && response.data != null) {
        final raw = response.data!['data'] ?? response.data!;
        if (raw is Map<String, dynamic>) {
          account = VirtualAccountModel.fromJson(raw);
        }
      }
      state = state.copyWith(
        isLoading: false,
        virtualAccount: account,
        error: response.success ? null : response.message,
      );
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  void clearError() => state = state.copyWith(clearError: true);
}

// ─── Providers ────────────────────────────────────────────────────────────────

final fundWalletRepositoryProvider = Provider<FundWalletRepository>((ref) {
  return FundWalletRepository(ApiService.instance);
});

final fundWalletNotifierProvider =
    StateNotifierProvider<FundWalletNotifier, FundWalletState>((ref) {
  return FundWalletNotifier(ref.watch(fundWalletRepositoryProvider));
});

final fundWalletLoadingProvider = Provider<bool>(
  (ref) => ref.watch(fundWalletNotifierProvider).isLoading,
);

final fundWalletVirtualAccountProvider = Provider<VirtualAccountModel?>(
  (ref) => ref.watch(fundWalletNotifierProvider).virtualAccount,
);
