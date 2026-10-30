import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/bill_repository.dart';
import '../models/bill/bills_response.dart';
import '../models/bill/bill_model.dart';
import '../models/bill/pay_bill_request.dart';
import '../models/bill/verify_bill_request.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import '../services/cache/cache_service.dart';
import 'auth_provider.dart';

// State class for bill operations
class BillState {
  final bool isLoading;
  final String? error;
  final List<BillModel>? bills;
  final Map<String, dynamic>? lastTransaction;
  final Map<String, dynamic>? verificationResult;

  BillState({
    this.isLoading = false,
    this.error,
    this.bills,
    this.lastTransaction,
    this.verificationResult,
  });

  BillState copyWith({
    bool? isLoading,
    String? error,
    List<BillModel>? bills,
    Map<String, dynamic>? lastTransaction,
    Map<String, dynamic>? verificationResult,
  }) {
    return BillState(
      isLoading: isLoading ?? this.isLoading,
      error: error ?? this.error,
      bills: bills ?? this.bills,
      lastTransaction: lastTransaction ?? this.lastTransaction,
      verificationResult: verificationResult ?? this.verificationResult,
    );
  }
}

// StateNotifier for bill operations
class BillNotifier extends StateNotifier<BillState> {
  final BillRepository _billRepository;
  final Ref _ref;

  BillNotifier(this._billRepository, this._ref) : super(BillState());

  /// Refresh wallet balances into the auth state after a payment so the home
  /// screen updates on its own. Fire-and-forget — the money has already moved.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  /// Get bills
  Future<void> getBills() async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('💡 BillProvider: Getting bills');
      final response = await _billRepository.getBills();

      if (response.success && response.data != null) {
        print(
          '💡 BillProvider: Successfully got ${response.data!.data?.length} bills',
        );

        // Cache the bill networks
        final networksToCache =
            response.data!.data!.map((bill) => bill.toJson()).toList();
        await CacheService().cacheBillNetworks(networksToCache);

        state = state.copyWith(
          isLoading: false,
          bills: response.data!.data,
          error: null,
        );
      } else {
        print('💡 BillProvider: Failed to get bills - ${response.message}');
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to get bills',
        );
      }
    } catch (e) {
      print('💡 BillProvider: Error getting bills - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  /// Initialize data from cache
  Future<void> _initializeData() async {
    print('💡 BillProvider: Initializing data state...');

    // Try to restore from cache first
    final cachedNetworks = await CacheService().getCachedBillNetworks();
    if (cachedNetworks != null) {
      print('💡 BillProvider: Found cached bill networks, restoring state');
      try {
        final networks =
            cachedNetworks
                .map((network) => BillModel.fromJson(network))
                .toList();
        state = state.copyWith(bills: networks);
        print(
          '💡 BillProvider: Restored ${networks.length} bill networks from cache',
        );
      } catch (e) {
        print('💡 BillProvider: Error restoring from cache: $e');
      }
    }
  }

  /// Pay bill
  Future<ApiResponse<Map<String, dynamic>>> payBill({
    required String phoneNumber,
    required String discoId,
    required String meterNumber,
    required String meterType,
    required String amount,
    required String transactionPin,  
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('💡 BillProvider: Paying bill');
      final request = PayBillRequest(
        phoneNumber: phoneNumber,
        discoId: discoId,
        meterNumber: meterNumber,
        meterType: meterType,
        amount: amount,
        transactionPin: transactionPin,  // ADD THIS
      );

      final response = await _billRepository.payBill(request);

      if (response.success) {
        print('💡 BillProvider: Bill payment successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
        _refreshBalances();
      } else {
        print('💡 BillProvider: Bill payment failed - ${response.message}');
        state = state.copyWith(isLoading: false, error: response.message);
      }

      return response;
    } catch (e) {
      print('💡 BillProvider: Error paying bill - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Verify bill
  Future<ApiResponse<Map<String, dynamic>>> verifyBill({
    required String discoId,
    required String meterNumber,
    required String meterType,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('💡 BillProvider: Verifying bill');
      final request = VerifyBillRequest(
        discoId: discoId,
        meterNumber: meterNumber,
        meterType: meterType,
      );

      final response = await _billRepository.verifyBill(request);

      if (response.success) {
        print('💡 BillProvider: Bill verification successful');
        state = state.copyWith(
          isLoading: false,
          verificationResult: response.data,
          error: null,
        );
      } else {
        print(
          '💡 BillProvider: Bill verification failed - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to verify bill',
        );
      }

      return response;
    } catch (e) {
      print('💡 BillProvider: Error verifying bill - $e');
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

  /// Clear verification result
  void clearVerificationResult() {
    state = state.copyWith(verificationResult: null);
  }
}

// Providers
final billRepositoryProvider = Provider<BillRepository>((ref) {
  return BillRepository(ApiService.instance);
});

final billNotifierProvider = StateNotifierProvider<BillNotifier, BillState>((
  ref,
) {
  final billRepository = ref.watch(billRepositoryProvider);
  return BillNotifier(billRepository, ref);
});

// Convenience providers
final billsProvider = Provider<List<BillModel>?>((ref) {
  return ref.watch(billNotifierProvider).bills;
});

final billLoadingProvider = Provider<bool>((ref) {
  return ref.watch(billNotifierProvider).isLoading;
});

final billErrorProvider = Provider<String?>((ref) {
  return ref.watch(billNotifierProvider).error;
});

final billLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(billNotifierProvider).lastTransaction;
});

final billVerificationResultProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(billNotifierProvider).verificationResult;
});
