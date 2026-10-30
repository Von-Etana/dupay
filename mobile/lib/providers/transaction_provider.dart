import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/transaction_repository.dart';
import '../models/base/api_response.dart';
import '../models/transaction/transaction_model.dart';
import '../core/network/api_service.dart';

// State class for transaction operations
class TransactionState {
  final bool isLoading;
  final bool isLoadingMore;
  final String? error;
  final List<TransactionModel>? transactions;
  final Map<String, dynamic>? lastTransaction;
  final int currentPage;
  final bool hasMore;

  TransactionState({
    this.isLoading = false,
    this.isLoadingMore = false,
    this.error,
    this.transactions,
    this.lastTransaction,
    this.currentPage = 1,
    this.hasMore = false,
  });

  TransactionState copyWith({
    bool? isLoading,
    bool? isLoadingMore,
    String? error,
    List<TransactionModel>? transactions,
    Map<String, dynamic>? lastTransaction,
    int? currentPage,
    bool? hasMore,
  }) {
    return TransactionState(
      isLoading:       isLoading       ?? this.isLoading,
      isLoadingMore:   isLoadingMore   ?? this.isLoadingMore,
      error:           error           ?? this.error,
      transactions:    transactions    ?? this.transactions,
      lastTransaction: lastTransaction ?? this.lastTransaction,
      currentPage:     currentPage     ?? this.currentPage,
      hasMore:         hasMore         ?? this.hasMore,
    );
  }
}

// StateNotifier for transaction operations
class TransactionNotifier extends StateNotifier<TransactionState> {
  final TransactionRepository _transactionRepository;

  TransactionNotifier(this._transactionRepository) : super(TransactionState());

  /// Get all transactions with optional search
  Future<ApiResponse<List<TransactionModel>>> getTransactions({
    String? search,
    String? type,
    String? status,
    String? startDate,
    String? endDate,
    int? page,
    int? limit,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('📊 TransactionProvider: Getting transactions');
      final response = await _transactionRepository.searchTransactions(
        search: search,
        type: type,
        status: status,
        startDate: startDate,
        endDate: endDate,
        page: page,
        limit: limit,
      );

      if (response.success) {
        print('📊 TransactionProvider: Transactions retrieved successfully');
        // statusCode holds last_page from repository pagination metadata
        final lastPage = response.statusCode ?? 1;
        print('📊 TransactionProvider: last_page=$lastPage, hasMore=${lastPage > 1}');
        state = state.copyWith(
          isLoading:    false,
          transactions: response.data,
          error:        null,
          currentPage:  1,
          hasMore:      lastPage > 1,
        );
      } else {
        print(
          '📊 TransactionProvider: Failed to get transactions - \${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error:     response.message ?? 'Failed to get transactions',
          hasMore:   false,
        );
      }

      return response;
    } catch (e) {
      print('📊 TransactionProvider: Error getting transactions - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<List<TransactionModel>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Load next page of transactions (appends to existing list)
  Future<void> loadMore({
    String? search,
    String? startDate,
    String? endDate,
  }) async {
    if (!state.hasMore || state.isLoadingMore) return;
    state = state.copyWith(isLoadingMore: true);
    try {
      final nextPage = state.currentPage + 1;
      final response = await _transactionRepository.searchTransactions(
        search:    search,
        startDate: startDate,
        endDate:   endDate,
        page:      nextPage,
      );
      if (response.success && response.data != null) {
        final combined = <TransactionModel>[...?(state.transactions), ...response.data!];
        final lastPage = response.statusCode ?? nextPage;
        state = state.copyWith(
          isLoadingMore: false,
          transactions:  combined,
          currentPage:   nextPage,
          hasMore:       nextPage < lastPage,
        );
      } else {
        state = state.copyWith(isLoadingMore: false, hasMore: false);
      }
    } catch (e) {
      state = state.copyWith(isLoadingMore: false);
    }
  }

  /// Create transaction PIN
  Future<ApiResponse<Map<String, dynamic>>> createTransactionPin({
    required String pin,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('🔐 TransactionProvider: Creating transaction PIN');
      final response = await _transactionRepository.createTransactionPin(
        pin: pin,
        confirmPin: pin, // Caller already validated pin == confirm on UI
      );

      if (response.success) {
        print('🔐 TransactionProvider: Transaction PIN created successfully');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
      } else {
        print(
          '🔐 TransactionProvider: Failed to create PIN - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to create transaction PIN',
        );
      }

      return response;
    } catch (e) {
      print('🔐 TransactionProvider: Error creating PIN - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Change transaction PIN
  Future<ApiResponse<Map<String, dynamic>>> changeTransactionPin({
    required String oldPin,
    required String newPin,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('🔐 TransactionProvider: Changing transaction PIN');
      final response = await _transactionRepository.changeTransactionPin(
        oldPin: oldPin,
        newPin: newPin,
        confirmNewPin: newPin, // Assuming confirmation is handled on frontend
      );

      if (response.success) {
        print('🔐 TransactionProvider: Transaction PIN changed successfully');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
      } else {
        print(
          '🔐 TransactionProvider: Failed to change PIN - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to change transaction PIN',
        );
      }

      return response;
    } catch (e) {
      print('🔐 TransactionProvider: Error changing PIN - $e');
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

  /// Clear transactions
  void clearTransactions() {
    state = state.copyWith(transactions: null);
  }
}

// Providers
final transactionRepositoryProvider = Provider<TransactionRepository>((ref) {
  return TransactionRepository();
});

final transactionNotifierProvider =
    StateNotifierProvider<TransactionNotifier, TransactionState>((ref) {
      final transactionRepository = ref.watch(transactionRepositoryProvider);
      return TransactionNotifier(transactionRepository);
    });

// Convenience providers
final transactionsProvider = Provider<List<TransactionModel>?>((ref) {
  return ref.watch(transactionNotifierProvider).transactions;
});

final transactionLoadingProvider = Provider<bool>((ref) {
  return ref.watch(transactionNotifierProvider).isLoading;
});

final transactionErrorProvider = Provider<String?>((ref) {
  return ref.watch(transactionNotifierProvider).error;
});

final transactionLastTransactionProvider = Provider<Map<String, dynamic>?>((
  ref,
) {
  return ref.watch(transactionNotifierProvider).lastTransaction;
});