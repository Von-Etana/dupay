import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/transfer_repository.dart';
import '../models/transfer/transfer_model.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import 'auth_provider.dart';

// State class for transfer operations
class TransferState {
  final bool isLoading;
  final String? error;
  final Map<String, dynamic>? lastTransaction;

  TransferState({this.isLoading = false, this.error, this.lastTransaction});

  TransferState copyWith({
    bool? isLoading,
    String? error,
    Map<String, dynamic>? lastTransaction,
  }) {
    return TransferState(
      isLoading: isLoading ?? this.isLoading,
      error: error ?? this.error,
      lastTransaction: lastTransaction ?? this.lastTransaction,
    );
  }
}

// StateNotifier for transfer operations
class TransferNotifier extends StateNotifier<TransferState> {
  final TransferRepository _transferRepository;
  final Ref _ref;

  TransferNotifier(this._transferRepository, this._ref)
      : super(TransferState());

  /// Refresh wallet balances into the auth state after a transfer/funding so
  /// the home screen updates on its own. Fire-and-forget — the money has
  /// already moved, so a refresh failure must not surface as a failed transfer.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  /// Fund Transfer
  Future<ApiResponse<Map<String, dynamic>>> fundTransfer({
    String? walletId,
    String? receiverId,
    required String amount,
    String? comment,
    String? transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('💸 TransferProvider: Fund transfer');
      final transfer = TransferModel(
        walletId: walletId,
        receiverId: receiverId,
        amount: amount,
        comment: comment,
        transactionPin: transactionPin,
      );

      final response = await _transferRepository.fundTransfer(transfer);

      if (response.success) {
        print('💸 TransferProvider: Fund transfer successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
        _refreshBalances();
      } else {
        print(
          '💸 TransferProvider: Fund transfer failed - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to transfer funds',
        );
      }

      return response;
    } catch (e) {
      print('💸 TransferProvider: Error fund transfer - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Request Fund
  Future<ApiResponse<Map<String, dynamic>>> requestFund({
    String? walletId,
    String? receiverId,
    required String amount,
    String? comment,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('💸 TransferProvider: Request fund');
      final request = TransferModel(
        walletId: walletId,
        receiverId: receiverId,
        amount: amount,
        comment: comment,
      );

      final response = await _transferRepository.requestFund(request);

      if (response.success) {
        print('💸 TransferProvider: Fund request successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
      } else {
        print('💸 TransferProvider: Fund request failed - ${response.message}');
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to request funds',
        );
      }

      return response;
    } catch (e) {
      print('💸 TransferProvider: Error request fund - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Manual Funding
  Future<ApiResponse<Map<String, dynamic>>> manualFunding({
    required String amount,
    String? proof,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('💸 TransferProvider: Manual funding');
      final funding = ManualFundingModel(amount: amount, proof: proof);

      final response = await _transferRepository.manualFunding(funding);

      if (response.success) {
        print('💸 TransferProvider: Manual funding successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
        _refreshBalances();
      } else {
        print(
          '💸 TransferProvider: Manual funding failed - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to submit manual funding',
        );
      }

      return response;
    } catch (e) {
      print('💸 TransferProvider: Error manual funding - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Clear error
  void clearError() {
    state = state.copyWith(error: null);
  }

  /// Clear last transaction
  void clearLastTransaction() {
    state = state.copyWith(lastTransaction: null);
  }
}

// Providers
final transferRepositoryProvider = Provider<TransferRepository>((ref) {
  return TransferRepository(ApiService.instance);
});

final transferNotifierProvider =
    StateNotifierProvider<TransferNotifier, TransferState>((ref) {
      final transferRepository = ref.watch(transferRepositoryProvider);
      return TransferNotifier(transferRepository, ref);
    });

// Convenience providers
final transferLoadingProvider = Provider<bool>((ref) {
  return ref.watch(transferNotifierProvider).isLoading;
});

final transferErrorProvider = Provider<String?>((ref) {
  return ref.watch(transferNotifierProvider).error;
});

final transferLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(transferNotifierProvider).lastTransaction;
});