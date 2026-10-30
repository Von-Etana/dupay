// lib/providers/recharge_card_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/api_service.dart';
import '../models/base/api_response.dart';
import '../models/recharge_card/epin_model.dart';
import '../repositories/recharge_card_repository.dart';
import '../services/cache/cache_service.dart';
import 'auth_provider.dart';

class RechargeCardState {
  final bool                     isLoading;
  final bool                     isPurchasing;
  final String?                  error;
  final List<EpinModel>          epins;
  final RechargeCardPurchase?    lastPurchase;

  const RechargeCardState({
    this.isLoading    = false,
    this.isPurchasing = false,
    this.error,
    this.epins        = const [],
    this.lastPurchase,
  });

  RechargeCardState copyWith({
    bool?                  isLoading,
    bool?                  isPurchasing,
    String?                error,
    bool                   clearError    = false,
    List<EpinModel>?       epins,
    RechargeCardPurchase?  lastPurchase,
  }) =>
      RechargeCardState(
        isLoading:    isLoading    ?? this.isLoading,
        isPurchasing: isPurchasing ?? this.isPurchasing,
        error:        clearError ? null : (error ?? this.error),
        epins:        epins        ?? this.epins,
        lastPurchase: lastPurchase ?? this.lastPurchase,
      );
}

class RechargeCardNotifier extends StateNotifier<RechargeCardState> {
  final RechargeCardRepository _repo;
  final Ref _ref;

  RechargeCardNotifier(this._repo, this._ref)
      : super(const RechargeCardState()) {
    _restoreFromCache();
  }

  /// Refresh wallet balances into the auth state after a purchase so the home
  /// screen updates on its own. Fire-and-forget — the money has already moved.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  /// Hydrate the e-pin list from cache on cold start so the previous-orders
  /// section shows instantly while the API call refreshes in the background.
  Future<void> _restoreFromCache() async {
    try {
      final cached = await CacheService().getCachedRechargeCardEpins();
      if (cached != null && cached.isNotEmpty) {
        final list = cached.map(EpinModel.fromJson).toList()
          ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
        // Only apply if nothing has been loaded yet — avoid clobbering fresh data.
        if (state.epins.isEmpty) {
          state = state.copyWith(epins: list);
          print('💳 RechargeCardProvider: Restored ${list.length} epins from cache');
        }
      }
    } catch (e) {
      print('💳 RechargeCardProvider: Error restoring from cache: $e');
    }
  }

  /// GET /user/fetch-epins
  Future<void> fetchEpins() async {
    state = state.copyWith(isLoading: true, clearError: true);
    final response = await _repo.fetchEpins();
    if (response.success) {
      // Server returns newest last; reverse for "most recent first" UX.
      final list = (response.data ?? const <EpinModel>[]).toList()
        ..sort((a, b) => b.createdAt.compareTo(a.createdAt));
      state = state.copyWith(isLoading: false, epins: list);

      // Persist for the next cold start.
      try {
        await CacheService()
            .cacheRechargeCardEpins(list.map((e) => e.toJson()).toList());
      } catch (e) {
        print('💳 RechargeCardProvider: Error caching epins: $e');
      }
    } else {
      state = state.copyWith(
          isLoading: false,
          error: response.message.isEmpty
              ? 'Failed to load recharge cards'
              : response.message);
    }
  }

  /// POST /buy-epin
  /// Returns the parsed [RechargeCardPurchase] on success, null otherwise.
  /// The raw ApiResponse is also exposed so callers can read .message.
  Future<({bool success, String message, RechargeCardPurchase? purchase})>
      buyEpin({
    required int    networkId,
    required int    value,
    required int    quantity,
    required String transactionPin,
  }) async {
    state = state.copyWith(isPurchasing: true, clearError: true);
    final response = await _repo.buyEpin(
      networkId:      networkId,
      value:          value,
      quantity:       quantity,
      transactionPin: transactionPin,
    );

    if (response.success && response.data != null) {
      final purchase = RechargeCardPurchase.fromJson(response.data!);
      state = state.copyWith(
        isPurchasing: false,
        lastPurchase: purchase,
      );
      // Refresh the history so the new order appears in Previous Orders.
      // Don't await — let it happen in the background.
      fetchEpins();
      _refreshBalances();
      return (success: true, message: response.message, purchase: purchase);
    }

    state = state.copyWith(
      isPurchasing: false,
      error: response.message.isEmpty
          ? 'Purchase failed'
          : response.message,
    );
    return (success: false, message: response.message, purchase: null);
  }

  void clearError() => state = state.copyWith(clearError: true);
}

final rechargeCardRepositoryProvider = Provider<RechargeCardRepository>(
  (ref) => RechargeCardRepository(ApiService.instance),
);

final rechargeCardNotifierProvider =
    StateNotifierProvider<RechargeCardNotifier, RechargeCardState>(
  (ref) =>
      RechargeCardNotifier(ref.watch(rechargeCardRepositoryProvider), ref),
);