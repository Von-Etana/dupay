// lib/providers/foreign_currency_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/foreign_currency_repository.dart';
import '../models/foreign_currency/foreign_account_model.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import '../services/cache/cache_service.dart';
import 'auth_provider.dart';

// ─── State ────────────────────────────────────────────────────────────────────

class ForeignCurrencyState {
  final bool isLoading;
  final String? error;
  final Map<String, Map<String, dynamic>?> rawAccounts;
  final Map<String, double> rates;
  final Map<String, dynamic>? lastConversion;

  const ForeignCurrencyState({
    this.isLoading = false,
    this.error,
    this.rawAccounts = const {},
    this.rates = const {},
    this.lastConversion,
  });

  ForeignCurrencyState copyWith({
    bool? isLoading,
    String? error,
    Map<String, Map<String, dynamic>?>? rawAccounts,
    Map<String, double>? rates,
    Map<String, dynamic>? lastConversion,
    bool clearError = false,
  }) {
    return ForeignCurrencyState(
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
      rawAccounts: rawAccounts ?? this.rawAccounts,
      rates: rates ?? this.rates,
      lastConversion: lastConversion ?? this.lastConversion,
    );
  }

  ForeignAccountModel? accountFor(String currency) {
    final raw = rawAccounts[currency];
    if (raw == null) return null;
    // If API returned data: null (no account found), return null
    if (raw.containsKey('data') && raw['data'] == null) return null;
    final dataRaw = raw['data'];
    final data = dataRaw is Map<String, dynamic> ? dataRaw : raw;
    if (data.isEmpty) return null;
    // Must have an id to be a real account
    if (data['id'] == null) return null;
    try { return ForeignAccountModel.fromJson(data); } catch (_) { return null; }
  }
}

// ─── Notifier ─────────────────────────────────────────────────────────────────

class ForeignCurrencyNotifier extends StateNotifier<ForeignCurrencyState> {
  final ForeignCurrencyRepository _repo;
  final Ref _ref;
  final CacheService _cache = CacheService();

  ForeignCurrencyNotifier(this._repo, this._ref)
      : super(const ForeignCurrencyState());

  /// Pull fresh NGN / USD / EUR balances from the API and push them into the
  /// auth state.
  ///
  /// This is the piece that was missing after a conversion. Every balance the
  /// UI shows — `user.balance`, `user.usdBalanceAmount`, `user.eurBalanceAmount`
  /// — lives on the USER object, not on the foreign-account payload. Refreshing
  /// only the foreign account left all three of those reading the old numbers,
  /// so the screen still showed the pre-conversion balance until the next cold
  /// start.
  Future<void> _refreshUserBalances() async {
    try {
      await _ref.read(authNotifierProvider.notifier).refreshBalances();
    } catch (_) {
      // Never let a balance refresh failure surface as a failed conversion —
      // the money has already moved successfully at this point.
    }
  }

  // ── Fetch foreign account ─────────────────────────────────────────────────
  // Cache-first, stale-while-revalidate:
  //   1) Read the cached account (account number, IBAN, swift, routing,
  //      bank address, beneficiary address, etc.). If it's still within the
  //      7-day TTL, emit it immediately so the UI never shows a blank
  //      "no account yet" state on every open.
  //   2) Always hit the network in the background to refresh balance and
  //      any backend-side changes; update both state and cache when fresh
  //      data arrives.
  //   3) If the network call fails but we have cached data, keep showing it
  //      (don't surface an error to the user — they already see the account).
  //
  // [forceRefresh] skips the cache entirely (used after we know the account
  // changed, e.g. right after creating it).
  Future<void> fetchAccount(String currency, {bool forceRefresh = false}) async {
    // 1) Emit cached payload first when allowed.
    if (!forceRefresh) {
      final cached = await _cache.getCachedForeignAccount(currency);
      if (cached != null && cached.isNotEmpty) {
        final updated =
            Map<String, Map<String, dynamic>?>.from(state.rawAccounts);
        updated[currency] = cached;
        state = state.copyWith(
          rawAccounts: updated,
          isLoading: false,
          clearError: true,
        );
        // Cache-first with a 7-day TTL (enforced in CacheService): the cache
        // is still fresh, so skip the network entirely — same as the bank
        // list / data networks. The refresh button and any balance-changing
        // action (create / convert / send) pass forceRefresh / clear the
        // cache to force a fresh pull.
        print('🌍 ForeignCurrency: $currency cache fresh (<7d) — skipping network');
        return;
      } else {
        state = state.copyWith(isLoading: true, clearError: true);
      }
    } else {
      state = state.copyWith(isLoading: true, clearError: true);
    }

    // 2) Network refresh.
    try {
      final response = await _repo.getForeignAccount(currency);
      final updated =
          Map<String, Map<String, dynamic>?>.from(state.rawAccounts);

      if (response.success) {
        final data = response.data ?? <String, dynamic>{};
        updated[currency] = data;
        state = state.copyWith(
          isLoading: false,
          rawAccounts: updated,
          clearError: true,
        );
        // 3) Persist ONLY a real account. A "no account found" response comes
        // back as { success:true, data:null }; the outer map is non-empty
        // (it still has success/message/data keys) so the old isNotEmpty
        // check happily cached an empty placeholder — that's why USD logged
        // "Cached USD foreign account" with nothing in it. Cache only when
        // the inner payload is a real account (has an id); otherwise clear
        // any stale entry so we never serve a ghost account.
        final inner = data['data'];
        final hasRealAccount = inner is Map && inner['id'] != null;
        if (hasRealAccount) {
          await _cache.cacheForeignAccount(currency, data);
        } else {
          await _cache.clearForeignAccount(currency);
        }
      } else {
        // Only mark "no account" if we don't have a cached one already —
        // a transient network blip shouldn't erase the user's account
        // details from the UI.
        final hasCached = updated[currency] != null;
        updated[currency] = hasCached ? updated[currency] : null;
        state = state.copyWith(
          isLoading: false,
          rawAccounts: updated,
          error: hasCached ? null : response.message,
        );
      }
    } catch (e) {
      // Network failure — keep any cached data we already have.
      final hasCached = state.rawAccounts[currency] != null;
      state = state.copyWith(
        isLoading: false,
        error: hasCached ? null : e.toString(),
      );
    }
  }

  // ── Request foreign account ───────────────────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> requestForeignAccount(
    ForeignAccountRequestPayload payload,
  ) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _repo.requestForeignAccount(payload);
      state = state.copyWith(
        isLoading: false,
        error: response.success ? null : response.message,
      );
      if (response.success) {
        // A brand-new account was issued — invalidate any stale cache and
        // pull a fresh copy.
        await _cache.clearForeignAccount(payload.currency);
        await fetchAccount(payload.currency, forceRefresh: true);
        await _refreshUserBalances();
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── PREVIEW: POST /convert-currency (apply_fee=false, no wallet debit) ────
  Future<ApiResponse<Map<String, dynamic>>> previewConversion({
    required String fromCurrency,
    required String toCurrency,
    required double amount,
  }) async {
    try {
      return await _repo.previewConversion(
        fromCurrency: fromCurrency,
        toCurrency: toCurrency,
        amount: amount,
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── EXECUTE: POST /foreign/account/convert-currency (debits wallet) ───────
  Future<ApiResponse<Map<String, dynamic>>> executeConversion({
    required String fromCurrency,
    required String toCurrency,
    required double amount,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _repo.executeConversion(
        fromCurrency: fromCurrency,
        toCurrency: toCurrency,
        amount: amount,
        transactionPin: transactionPin,
      );
      if (response.success) {
        state = state.copyWith(isLoading: false, lastConversion: response.data);

        // Both sides of the conversion changed. Three things have to be
        // invalidated, and previously only the first was:
        //
        //   1. the cached foreign ACCOUNT payloads (account details screen)
        //   2. the cached USER profile/stats, which is where every balance
        //      actually shown in the UI comes from
        //   3. the transaction history, which now has a new entry
        //
        // NGN is deliberately skipped for fetchAccount — there is no foreign
        // account record for the naira wallet — but its balance still moved,
        // and _refreshUserBalances() is what picks that up.
        if (fromCurrency != 'NGN') {
          await _cache.clearForeignAccount(fromCurrency);
          await fetchAccount(fromCurrency, forceRefresh: true);
        }
        if (toCurrency != 'NGN') {
          await _cache.clearForeignAccount(toCurrency);
          await fetchAccount(toCurrency, forceRefresh: true);
        }

        await _cache.clearTransactionCache();
        await _refreshUserBalances();
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── Fetch exchange rates ───────────────────────────────────────────────────
  Future<void> fetchRates() async {
    try {
      final response = await _repo.getExchangeRates();
      if (response.success && response.data != null) {
        final rawData = response.data!['data'];
        final raw = rawData is Map<String, dynamic> ? rawData : <String, dynamic>{};
        final parsed = <String, double>{};
        raw.forEach((k, v) {
          parsed[k] = double.tryParse(v.toString()) ?? 0.0;
        });
        state = state.copyWith(rates: parsed);
      }
    } catch (_) {}
  }

  // ── Send foreign money ─────────────────────────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> sendForeignMoney({
    required String foreignAccountId,
    required String recipientName,
    required String recipientAccount,
    required String currency,
    required double amount,
    String? transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _repo.sendForeignMoney(
        foreignAccountId: foreignAccountId,
        recipientName: recipientName,
        recipientAccount: recipientAccount,
        currency: currency,
        amount: amount,
        transactionPin: transactionPin,
      );
      state = state.copyWith(
        isLoading: false,
        error: response.success ? null : response.message,
      );
      if (response.success) {
        // Balance changed — invalidate cache and force a fresh fetch, then
        // refresh the user object so the figure on screen actually moves.
        await _cache.clearForeignAccount(currency);
        await fetchAccount(currency, forceRefresh: true);
        await _cache.clearTransactionCache();
        await _refreshUserBalances();
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  void clearError() => state = state.copyWith(clearError: true);
}

// ─── Providers ────────────────────────────────────────────────────────────────

final foreignCurrencyRepositoryProvider = Provider<ForeignCurrencyRepository>((ref) {
  return ForeignCurrencyRepository(ApiService.instance);
});

final foreignCurrencyNotifierProvider =
    StateNotifierProvider<ForeignCurrencyNotifier, ForeignCurrencyState>((ref) {
  return ForeignCurrencyNotifier(
    ref.watch(foreignCurrencyRepositoryProvider),
    ref,
  );
});

final foreignCurrencyLoadingProvider = Provider<bool>(
  (ref) => ref.watch(foreignCurrencyNotifierProvider).isLoading,
);

final foreignCurrencyErrorProvider = Provider<String?>(
  (ref) => ref.watch(foreignCurrencyNotifierProvider).error,
);