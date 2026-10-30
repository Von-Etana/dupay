// lib/providers/card_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/api_service.dart';
import '../models/base/api_response.dart';
import 'auth_provider.dart';

// ─── Model ────────────────────────────────────────────────────────────────────

class VirtualCardModel {
  final int? id;
  final String? cardId;
  final String? cardNumber;
  final String? cardName;
  final String? cvv;
  final String? expiryDate;
  final String currency;
  final double balance;
  final bool isActive;
  final String? cardType;
  final String? billingStreet;
  final String? billingCity;
  final String? billingState;
  final String? billingCountry;
  final String? billingZip;
  // StroWallet never returns the full PAN/CVV inline — it returns last4 for
  // masking plus secure one-off URLs the app opens to reveal the real values.
  final String? last4;
  final String? cardNumberUrl;
  final String? cvvUrl;

  const VirtualCardModel({
    this.id,
    this.cardId,
    this.cardNumber,
    this.cardName,
    this.cvv,
    this.expiryDate,
    this.currency = 'USD',
    this.balance = 0.0,
    this.isActive = true,
    this.cardType,
    this.billingStreet,
    this.billingCity,
    this.billingState,
    this.billingCountry,
    this.billingZip,
    this.last4,
    this.cardNumberUrl,
    this.cvvUrl,
  });

  bool get isFrozen => !isActive;

  /// True until the issuer actually issues the card. StroWallet never returns
  /// the full `card_number` inline (it stays null even when active) — it returns
  /// `last4` + reveal URLs once the card is live — so `last4` is the real
  /// "issued" signal. Drives the "Card activating…" badge and the fetch polling.
  bool get isProvisioning =>
      (last4 ?? '').isEmpty && (cardNumber ?? '').isEmpty;

  /// The full PAN / CVV are only viewable via StroWallet's secure reveal URLs.
  bool get canRevealNumber => (cardNumberUrl ?? '').isNotEmpty;
  bool get canRevealCvv => (cvvUrl ?? '').isNotEmpty;

  factory VirtualCardModel.fromJson(Map<String, dynamic> json) {
    // `response_data` can arrive as an empty JSON array (`[]`) from the backend
    // when there's no issuer payload yet — casting that straight to a Map throws
    // ("List<dynamic> is not a subtype of Map<String, dynamic>?"). Guard it so a
    // freshly-created card still parses and displays instead of being dropped.
    final rawResponseData = json['response_data'];
    final responseData = rawResponseData is Map<String, dynamic>
        ? rawResponseData
        : const <String, dynamic>{};

    // Status resolution — priority order:
    // 1. top-level card_status injected from API root (most authoritative — from DB)
    // 2. data.status (from provider API — may lag)
    // 3. data.card_status
    // Only 'active' maps to active. Everything else (frozen, inactive) = frozen.
    final rawStatus = (json['card_status']?.toString() ??
            json['status']?.toString() ??
            'active')
        .toLowerCase()
        .trim();

    return VirtualCardModel(
      id: json['id'] is int
          ? json['id']
          : int.tryParse(json['id']?.toString() ?? ''),
      cardId: responseData['card_id']?.toString() ??
          json['card_id']?.toString(),
      cardNumber: json['card_number']?.toString(),
      cardName: json['card_name']?.toString(),
      cvv: json['cvv']?.toString(),
      expiryDate: json['expiry']?.toString(),
      currency:
          json['type']?.toString() ?? json['currency']?.toString() ?? 'USD',
      balance:
          double.tryParse(json['balance']?.toString() ?? '0') ?? 0.0,
      // Only 'active' = active. frozen / inactive / anything else = not active.
      isActive: rawStatus == 'active',
      cardType: json['card_type']?.toString(),
      billingStreet: json['billing_address']?.toString(),
      billingCity: json['billing_city']?.toString(),
      billingState: json['billing_state']?.toString(),
      billingCountry: json['billing_country']?.toString(),
      billingZip: json['billing_zip_code']?.toString(),
      last4: json['last4']?.toString(),
      cardNumberUrl: json['card_number_url']?.toString(),
      cvvUrl: json['cvv_url']?.toString(),
    );
  }

  VirtualCardModel copyWithActive(bool active) => VirtualCardModel(
        id: id,
        cardId: cardId,
        cardNumber: cardNumber,
        cardName: cardName,
        cvv: cvv,
        expiryDate: expiryDate,
        currency: currency,
        balance: balance,
        isActive: active,
        cardType: cardType,
        billingStreet: billingStreet,
        billingCity: billingCity,
        billingState: billingState,
        billingCountry: billingCountry,
        billingZip: billingZip,
        last4: last4,
        cardNumberUrl: cardNumberUrl,
        cvvUrl: cvvUrl,
      );

  /// Masked display for the card face. Uses the full PAN if we ever have it,
  /// otherwise `**** **** **** last4`, otherwise a fully-masked placeholder.
  String get displayCardNumber {
    final full = cardNumber ?? '';
    if (full.isNotEmpty) return full;
    final l4 = last4 ?? '';
    if (l4.isNotEmpty) return '**** **** **** $l4';
    return '**** **** **** ****';
  }

  String get displayBalance => currency == 'NGN'
      ? '₦${balance.toStringAsFixed(2)}'
      : '\$${balance.toStringAsFixed(2)}';
}

// ─── State ────────────────────────────────────────────────────────────────────

class CardState {
  final bool isLoading;
  final bool hasChecked;
  final String? error;
  final List<VirtualCardModel> cards;

  // Transactions keyed by "cardId_currency" so switching cards
  // doesn't wipe the other card's transaction history.
  final Map<String, List<Map<String, dynamic>>> _txCache;
  final String? _activeTxKey; // which card's txs are currently loaded

  const CardState({
    this.isLoading = false,
    this.hasChecked = false,
    this.error,
    this.cards = const [],
    Map<String, List<Map<String, dynamic>>> txCache = const {},
    String? activeTxKey,
  })  : _txCache = txCache,
        _activeTxKey = activeTxKey;

  bool get hasCards => cards.isNotEmpty;

  /// Returns transactions for the currently active card view.
  List<Map<String, dynamic>> get transactions =>
      _activeTxKey != null ? (_txCache[_activeTxKey] ?? []) : [];

  CardState copyWith({
    bool? isLoading,
    bool? hasChecked,
    String? error,
    List<VirtualCardModel>? cards,
    Map<String, List<Map<String, dynamic>>>? txCache,
    String? activeTxKey,
    bool clearError = false,
  }) {
    return CardState(
      isLoading: isLoading ?? this.isLoading,
      hasChecked: hasChecked ?? this.hasChecked,
      error: clearError ? null : (error ?? this.error),
      cards: cards ?? this.cards,
      txCache: txCache ?? _txCache,
      activeTxKey: activeTxKey ?? _activeTxKey,
    );
  }
}

// ─── Notifier ─────────────────────────────────────────────────────────────────

class CardNotifier extends StateNotifier<CardState> {
  final ApiService _api;
  final Ref _ref;
  CardNotifier(this._api, this._ref) : super(const CardState());

  /// Refresh wallet balances into the auth state after a card action that
  /// moves money (create / fund / withdraw) so the home screen updates on its
  /// own. Fire-and-forget — the money has already moved.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  // ── Fetch both NGN and USD cards ─────────────────────────────────────────
  Future<void> fetchCards() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final cards = <VirtualCardModel>[];

      for (final currency in ['NGN', 'USD']) {
        try {
          final response = await _api.get<Map<String, dynamic>>(
            '/virtual-card/fetch/$currency',
            fromJson: (j) => j,
          );

          if (response.data == null) continue;

          final errorField = response.data!['error']?.toString() ?? '';
          final data = response.data!['data'];

          if (errorField == 'not_found' || data == null) continue;

          if (data is Map<String, dynamic> && data.isNotEmpty) {
            final merged = Map<String, dynamic>.from(data);

            // Attach response_data so fromJson can read the UUID card_id
            if (response.data!['response_data'] != null) {
              merged['response_data'] = response.data!['response_data'];
            }

            // ⭐ Top-level card_status is the authoritative DB value.
            // It wins over any status that may come from the external provider.
            final topStatus =
                response.data!['card_status']?.toString() ?? '';
            if (topStatus.isNotEmpty) {
              merged['card_status'] = topStatus;
              // Overwrite data.status too so fromJson always reads the DB value
              merged['status'] = topStatus;
            }

            cards.add(VirtualCardModel.fromJson(merged));
          }
        } catch (_) {
          // Keep existing card for this currency if the request fails
          final existing =
              state.cards.where((c) => c.currency == currency).firstOrNull;
          if (existing != null) cards.add(existing);
        }
      }

      state = state.copyWith(
          isLoading: false, hasChecked: true, cards: cards);
    } catch (e) {
      state = state.copyWith(
          isLoading: false, hasChecked: true, error: e.toString());
    }
  }

  // ── Reveal full PAN + CVV (on demand) ─────────────────────────────────────
  // The issuer never returns the raw PAN/CVV in the card list — only via a
  // secure endpoint the backend resolves on request. Called when the user opens
  // Card Details, never on the poll. Returns null if it couldn't be revealed.
  Future<Map<String, String?>?> revealCard(String currency) async {
    try {
      final res = await _api.get<Map<String, dynamic>>(
        '/virtual-card/reveal/$currency',
        fromJson: (j) => j,
      );
      final data = res.data?['data'];
      if (res.data?['success'] == true && data is Map) {
        return {
          'card_number_url': data['card_number_url']?.toString(),
          'cvv_url': data['cvv_url']?.toString(),
        };
      }
    } catch (_) {
      // success:false / network error → treat as "couldn't reveal".
    }
    return null;
  }

  // ── Create card ───────────────────────────────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> createCard({
    required String currency,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/virtual-card',
        data: {
          'currency': currency,
          'transaction_pin': transactionPin,
        },
        // Card creation waits on StroWallet (up to ~60s server-side), which is
        // longer than the default 30s receive timeout. Give it headroom so the
        // app doesn't abort a request the backend is still completing.
        // A brand-new account also runs the slow customer (create-user) call
        // synchronously on the backend, so allow more headroom. (Proper fix:
        // move customer creation into the background job too — see notes.)
        receiveTimeout: const Duration(seconds: 120),
      );
      if (response.success) {
        await fetchCards();
        _refreshBalances();
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── Freeze / Unfreeze ─────────────────────────────────────────────────────
  // NGN → POST /virtual-card/card/change-status  (auto-toggles)
  // USD → POST /virtual-card/freeze-or-unfreeze  (explicit action)
  Future<ApiResponse<Map<String, dynamic>>> toggleFreeze({
    required int cardId,
    required String action, // 'freeze' or 'unfreeze'
    required String transactionPin,
    required String currency,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final ApiResponse<Map<String, dynamic>> response;

      if (currency == 'NGN') {
        response = await _api.postSimple(
          '/virtual-card/card/change-status',
          data: {
            'card_id': cardId.toString(),
            'transaction_pin': transactionPin,
          },
        );
      } else {
        response = await _api.postSimple(
          '/virtual-card/freeze-or-unfreeze',
          data: {
            'card_id': cardId.toString(),
            'action': action,
            'transaction_pin': transactionPin,
          },
        );
      }

      if (response.success) {
        // Re-fetch so status is always synced from DB
        await fetchCards();
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── Fund card ─────────────────────────────────────────────────────────────
  // POST /virtual-card/fund
  // currency = source wallet: NGN | USD | EUR (converts to USD card credit)
  Future<ApiResponse<Map<String, dynamic>>> fundCard({
    required int cardId,
    required String currency, // source wallet currency
    required double amount,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/virtual-card/fund',
        data: {
          'card_id': cardId.toString(),
          'currency': currency,
          'amount': amount,
          'transaction_pin': transactionPin,
        },
      );
      if (response.success) {
        await fetchCards();
        _refreshBalances();
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── Withdraw from card ────────────────────────────────────────────────────
  // POST /virtual-card/withdraw
  //
  // body: {
  //   "amount":          "5.00"   (string with 2 decimals),
  //   "card_id":          <int>,
  //   "transaction_pin":  "1111"
  // }
  //
  // Response on failure:
  //   { "success": false, "message": "Insufficient card balance" }
  //
  // Note: only USD cards support withdrawal — call sites should gate this.
  Future<ApiResponse<Map<String, dynamic>>> withdrawFromCard({
    required int cardId,
    required double amount,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/virtual-card/withdraw',
        data: {
          'amount':          amount.toStringAsFixed(2),
          'card_id':         cardId,
          'transaction_pin': transactionPin,
        },
      );
      if (response.success) {
        await fetchCards();
        _refreshBalances();
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
      }
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── Fetch transactions ────────────────────────────────────────────────────
  // GET /virtual-card/transactions/{id}/{currency}
  //
  // USD response: List of Strowallet objects with 'narrative', 'createdAt',
  //   'amount' (string), 'centAmount' (string in cents), 'type', 'method',
  //   'status', 'cardBalanceAfter' (in cents), 'transactionType'
  //
  // NGN response: varies by SafeHaven API version
  Future<void> fetchCardTransactions({
    required int cardId,
    required String currency,
  }) async {
    final txKey = '${cardId}_$currency';
    state = state.copyWith(isLoading: true, clearError: true, activeTxKey: txKey);
    try {
      final response = await _api.get<Map<String, dynamic>>(
        '/virtual-card/transactions/$cardId/$currency',
        fromJson: (j) => j,
      );

      List<Map<String, dynamic>> txs = [];
      if (response.success && response.data != null) {
        final raw = response.data!['data'];
        // Handle both flat list and nested { data: [...] }
        final list = (raw is Map) ? raw['data'] : raw;
        if (list is List) {
          txs = list.whereType<Map<String, dynamic>>().toList();
        }
      }

      final newCache = Map<String, List<Map<String, dynamic>>>.from(
          state._txCache);
      newCache[txKey] = txs;

      state = state.copyWith(
          isLoading: false, txCache: newCache, activeTxKey: txKey);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  // ── Enable 2FA on virtual card ────────────────────────────────────────────
  // POST /virtual-card/card/enable2fa
  // body: { card_id (int), transaction_pin }
  Future<ApiResponse<Map<String, dynamic>>> enable2FA({
    required String cardId,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/virtual-card/card/enable2fa',
        data: {
          'card_id': int.tryParse(cardId) ?? cardId,
          'transaction_pin': transactionPin,
        },
      );
      state = state.copyWith(
          isLoading: false,
          error: response.success ? null : response.message);
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── Reset virtual card PIN ────────────────────────────────────────────────
  // POST /virtual-card/card/reset-pin
  // body: { new_pin, confirm_pin, card_id, fingerPrint }
  Future<ApiResponse<Map<String, dynamic>>> resetCardPin({
    required String cardId,
    required String newPin,
    required String transactionPin,
    bool fingerPrint = false,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/virtual-card/card/reset-pin',
        data: {
          'new_pin': newPin,
          'confirm_pin': newPin,
          'card_id': int.tryParse(cardId) ?? cardId,
          'transaction_pin': transactionPin,
          'fingerPrint': fingerPrint,
        },
      );
      state = state.copyWith(
          isLoading: false,
          error: response.success ? null : response.message);
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── Change virtual card PIN ───────────────────────────────────────────────
  // POST /virtual-card/card/change-pin
  // body: { old_pin, new_pin, confirm_pin, card_id, transaction_pin }
  //
  // Note: live API rejects without transaction_pin
  // ("Please Enter Transaction Pin"), so it's included even though
  // not present in the original sample payload.
  Future<ApiResponse<Map<String, dynamic>>> changeCardPin({
    required String cardId,
    required String oldPin,
    required String newPin,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/virtual-card/card/change-pin',
        data: {
          'old_pin': oldPin,
          'new_pin': newPin,
          'confirm_pin': newPin,
          'card_id': int.tryParse(cardId) ?? cardId,
          'transaction_pin': transactionPin,
        },
      );
      state = state.copyWith(
          isLoading: false,
          error: response.success ? null : response.message);
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

final cardNotifierProvider =
    StateNotifierProvider<CardNotifier, CardState>((ref) {
  return CardNotifier(ApiService.instance, ref);
});

final hasCardsProvider =
    Provider<bool>((ref) => ref.watch(cardNotifierProvider).hasCards);