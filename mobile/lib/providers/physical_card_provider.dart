// lib/providers/physical_card_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import 'auth_provider.dart';

// ── Model matching real API response ─────────────────────────────────────────
// GET /physical-card →
// { data: { id, card_id, currency, card_pan(last6), masked_pan,
//   cvv, expiry_month, expiry_year, status, atm, pos, web, mobile,
//   blocked, brand, spending_limit_amount, spending_limit_interval } }

class PhysicalCardModel {
  final int? id;
  final String? cardId;               // card_id (used in API calls)
  final String? maskedPan;            // 506136******1531
  final String? cardPan;              // last 6: 121531
  final String? cvv;
  final String? expiryMonth;
  final String? expiryYear;
  final String currency;
  final String? brand;                // Verve | Visa | Mastercard
  final String status;                // active | inactive
  final bool atm;
  final bool pos;
  final bool web;
  final bool mobile;
  final bool blocked;
  final String? blockReason;
  final double spendingLimit;
  final String spendingLimitInterval; // daily | weekly | monthly

  const PhysicalCardModel({
    this.id,
    this.cardId,
    this.maskedPan,
    this.cardPan,
    this.cvv,
    this.expiryMonth,
    this.expiryYear,
    this.currency = 'NGN',
    this.brand,
    this.status = 'active',
    this.atm = true,
    this.pos = true,
    this.web = true,
    this.mobile = true,
    this.blocked = false,
    this.blockReason,
    this.spendingLimit = 500000,
    this.spendingLimitInterval = 'daily',
  });

  /// True once the issuer has actually assigned a card to this account.
  /// Ordering a physical card creates the record immediately, but the PAN /
  /// CVV / expiry only exist after the card is issued and activated. Mirrors
  /// [VirtualCardModel.isProvisioning] on the virtual-card side.
  bool get isAssigned => _hasValue(maskedPan) || _hasValue(cardPan);

  /// Laravel/JSON backends occasionally serialise an absent column as the
  /// string "null" rather than a JSON null, which would otherwise read as a
  /// real card number.
  static bool _hasValue(String? v) {
    final s = (v ?? '').trim();
    return s.isNotEmpty && s.toLowerCase() != 'null' && s != '-';
  }

  /// Ordered but not yet assigned — the "Card on the way / not activated yet"
  /// state. Must never render as Active.
  bool get isProvisioning => !isAssigned;

  /// Only a card that actually exists can be active. Without this guard a
  /// freshly-ordered record (status defaults to 'active' server-side) showed
  /// an "Active" badge even though no card was assigned to the account.
  bool get isActive => isAssigned && status == 'active' && !blocked;

  /// What to print on the card face. Falls back to dots while no card number
  /// has been assigned, so a placeholder record never shows a bogus PAN.
  String get displayPan {
    if (_hasValue(maskedPan)) return maskedPan!.trim();
    if (_hasValue(cardPan)) return cardPan!.trim();
    return '●●●● ●●●● ●●●● ●●●●';
  }

  String get displayExpiry {
    if (expiryMonth == null || expiryYear == null) return '**/**';
    final month = expiryMonth!.padLeft(2, '0');
    final year  = expiryYear!.length == 4
        ? expiryYear!.substring(2)
        : expiryYear!;
    return '$month/$year';
  }

  factory PhysicalCardModel.fromJson(Map<String, dynamic> json) {
    // _handleSingleResponse passes the FULL response to fromJson
    // so we need to unwrap 'data' first
    final dataRaw = json['data'];
    final data = dataRaw is Map<String, dynamic> ? dataRaw : json;
    return PhysicalCardModel(
      id:           data['id'],
      cardId:       data['card_id']?.toString(),
      maskedPan:    data['masked_pan']?.toString(),
      cardPan:      data['card_pan']?.toString(),
      cvv:          data['cvv']?.toString(),
      expiryMonth:  data['expiry_month']?.toString(),
      expiryYear:   data['expiry_year']?.toString(),
      currency:     data['currency']?.toString() ?? 'NGN',
      brand:        data['brand']?.toString(),
      status:       data['status']?.toString() ?? 'active',
      atm:          data['atm'] == 1 || data['atm'] == true,
      pos:          data['pos'] == 1 || data['pos'] == true,
      web:          data['web'] == 1 || data['web'] == true,
      mobile:       data['mobile'] == 1 || data['mobile'] == true,
      blocked:      data['blocked'] == 1 || data['blocked'] == true,
      blockReason:  data['block_reason']?.toString(),
      spendingLimit: double.tryParse(
              data['spending_limit_amount']?.toString() ?? '500000') ??
          500000,
      spendingLimitInterval:
          data['spending_limit_interval']?.toString() ?? 'daily',
    );
  }
}

// ─── State ────────────────────────────────────────────────────────────────────

class PhysicalCardState {
  final bool isLoading;
  final bool hasChecked;
  final String? error;
  final PhysicalCardModel? card;
  // Admin-configurable fee charged when requesting a physical card.
  // null = not yet fetched.
  final double? creationFee;

  const PhysicalCardState({
    this.isLoading = false,
    this.hasChecked = false,
    this.error,
    this.card,
    this.creationFee,
  });

  bool get hasCard => card != null;

  PhysicalCardState copyWith({
    bool? isLoading,
    bool? hasChecked,
    String? error,
    PhysicalCardModel? card,
    double? creationFee,
    bool clearError = false,
    bool clearCard  = false,
  }) {
    return PhysicalCardState(
      isLoading:  isLoading  ?? this.isLoading,
      hasChecked: hasChecked ?? this.hasChecked,
      error:      clearError ? null : (error ?? this.error),
      card:       clearCard  ? null : (card  ?? this.card),
      creationFee: creationFee ?? this.creationFee,
    );
  }
}

// ─── Notifier ─────────────────────────────────────────────────────────────────

class PhysicalCardNotifier extends StateNotifier<PhysicalCardState> {
  final ApiService _api;
  final Ref _ref;
  PhysicalCardNotifier(this._api, this._ref)
      : super(const PhysicalCardState());

  /// Refresh wallet balances into the auth state after a card action that
  /// charges the wallet (order / activation fee) so the home screen updates on
  /// its own. Fire-and-forget — the fee has already moved.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  // ── GET /physical-card  (resource index) ──────────────────────────────────
  Future<void> fetchCard() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.get<PhysicalCardModel>(
        '/physical-card',
        fromJson: PhysicalCardModel.fromJson,
      );

      if (response.success && response.data != null) {
        state = state.copyWith(
          isLoading:  false,
          hasChecked: true,
          card:       response.data,
        );
      } else {
        // success:true but no card yet (new user)
        state = state.copyWith(
          isLoading:  false,
          hasChecked: true,
          clearCard:  true,
        );
      }
    } catch (e) {
      state = state.copyWith(
          isLoading: false, hasChecked: true, error: e.toString());
    }
  }

  // ── GET /fees  ────────────────────────────────────────────────────────────
  // Loads the admin-configured physical card creation fee so the request
  // screen can tell the user exactly what they'll be charged before they
  // enter their PIN. Best-effort: never throws — if it fails the screen just
  // won't show an amount.
  Future<void> fetchFee() async {
    try {
      final response = await _api.get<Map<String, dynamic>>(
        ApiConstants.fees,
        fromJson: (json) => json,
      );
      final body = response.data ?? const <String, dynamic>{};
      final data = body['data'] is Map ? body['data'] as Map : body;
      final fee = double.tryParse(
          data['physical_card_fee']?.toString() ?? '');
      if (fee != null) {
        state = state.copyWith(creationFee: fee);
      }
    } catch (_) {
      // Ignore — the fee line is a nice-to-have, not a blocker.
    }
  }

  // ── POST /physical-card/block  ────────────────────────────────────────────
  // body: { reason: 'lost'|'stolen' (optional), transaction_pin }
  // route: /physical-card/block/{cardId}
  Future<ApiResponse<Map<String, dynamic>>> blockCard({
    required String transactionPin,
    String reason = 'lost',
  }) async {
    if (state.card == null) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'No card loaded');
    }
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/physical-card/block/${state.card!.id}',
        data: {
          'reason':          reason,
          'transaction_pin': transactionPin,
        },
      );
      if (response.success) await fetchCard();
      else state = state.copyWith(isLoading: false, error: response.message);
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── POST /card/change-status  (freeze/unfreeze — not block) ───────────────
  // body: { physical_card_id (DB row id), transaction_pin }
  // Backend auto-toggles: active → inactive → active
  Future<ApiResponse<Map<String, dynamic>>> toggleFreeze({
    required String transactionPin,
  }) async {
    if (state.card == null) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'No card loaded');
    }
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/card/change-status',
        data: {
          'physical_card_id': state.card!.id,
          'transaction_pin':  transactionPin,
        },
      );
      if (response.success) await fetchCard();
      else state = state.copyWith(isLoading: false, error: response.message);
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── POST /update-card-channels  ───────────────────────────────────────────
  // body: { card_id, atm, pos, web, mobile, transaction_pin }
  Future<ApiResponse<Map<String, dynamic>>> updateChannels({
    required bool atm,
    required bool pos,
    required bool web,
    required bool mobile,
    required String transactionPin,
  }) async {
    if (state.card == null) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'No card loaded');
    }
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/update-card-channels',
        data: {
          'card_id':         state.card!.cardId,
          'atm':             atm    ? 1 : 0,
          'pos':             pos    ? 1 : 0,
          'web':             web    ? 1 : 0,
          'mobile':          mobile ? 1 : 0,
          'transaction_pin': transactionPin,
        },
      );
      if (response.success) await fetchCard();
      else state = state.copyWith(isLoading: false, error: response.message);
      return response;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // ── POST /card/change-pin  ────────────────────────────────────────────────
  // body: {
  //   old_pin, new_pin, confirm_pin,
  //   physical_card_id (int DB id),
  //   fingerPrint (bool),
  //   transaction_pin
  // }
  // Response (failure example):
  //   { "success": false, "message": "Old PIN is incorrect." }
  Future<ApiResponse<Map<String, dynamic>>> changePin({
    required String oldPin,
    required String newPin,
    required String confirmPin,
    required String transactionPin,
    bool fingerPrint = false,
    int? physicalCardId, // optional override; defaults to loaded card's id
  }) async {
    final cardDbId = physicalCardId ?? state.card?.id;
    if (cardDbId == null) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'No card loaded');
    }
    if (newPin != confirmPin) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'PINs do not match');
    }

    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/card/change-pin',
        data: {
          'old_pin':          oldPin,
          'new_pin':          newPin,
          'confirm_pin':      confirmPin,
          'physical_card_id': cardDbId,
          'fingerPrint':      fingerPrint,
          'transaction_pin':  transactionPin,
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

  // ── POST /enable2fa  ──────────────────────────────────────────────────────
  // body: { card_id (string UUID, e.g. "697b8335a99c1f9c305f4cde"),
  //         transaction_pin }
  // Response (success):
  //   { success: true, message: "2FA enabled successfully.",
  //     data: { success: true, message: "2FA enrollment Successful." } }
  //
  // Note: card_id here is the EXTERNAL provider id (PhysicalCardModel.cardId),
  //       NOT the integer DB row id.
  Future<ApiResponse<Map<String, dynamic>>> enable2FA({
    required String transactionPin,
    String? cardId, // optional override; defaults to loaded card's cardId
  }) async {
    final externalCardId = cardId ?? state.card?.cardId;
    if (externalCardId == null || externalCardId.isEmpty) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'No card loaded');
    }

    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/enable2fa',
        data: {
          'card_id':         externalCardId,
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

  // ── POST /create-atm-card  ────────────────────────────────────────────────
  // One-time card activation. body: {
  //   card_pan (last 6 digits, string e.g. "121531"),
  //   cvv      (string e.g. "354"),
  //   card_pin (int  e.g. 1111),
  //   physical_card_id (int DB id),
  //   transaction_pin
  // }
  //
  // Response when already activated:
  //   { "status": false,
  //     "message": "This card has already been used and cannot be used again." }
  //
  // Note: this endpoint returns `status` (not `success`) on failure. The
  // ApiResponse handler in api_service maps both keys, so .success is reliable.
  Future<ApiResponse<Map<String, dynamic>>> enableCard({
    required String cardPan,
    required String cvv,
    required String cardPin,
    required String transactionPin,
    int? physicalCardId, // optional override; defaults to loaded card's id
  }) async {
    final cardDbId = physicalCardId ?? state.card?.id;
    if (cardDbId == null) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: 'No card loaded');
    }

    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final response = await _api.postSimple(
        '/create-atm-card',
        data: {
          'card_pan':         cardPan,
          'cvv':              cvv,
          'card_pin':         int.tryParse(cardPin) ?? cardPin,
          'physical_card_id': cardDbId,
          'transaction_pin':  transactionPin,
        },
        // Activation creates the StroWallet customer (if missing) + the card +
        // sets the PIN, all server-side against StroWallet — well beyond the
        // default 30s. Give it headroom so the app doesn't abort mid-activation.
        receiveTimeout: const Duration(seconds: 70),
      );
      if (response.success) {
        await fetchCard(); // refresh card state after activation
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

  // ── POST /setup-card  ─────────────────────────────────────────────────────
  // Request a new physical card (first time)
  Future<ApiResponse<Map<String, dynamic>>> requestCard({
    required String transactionPin,
    String? deliveryAddress,
  }) async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      // Route: POST /physical-card (resource store method)
      // Note: /setup-card route exists but maps to a non-existent method.
      // The resource route POST /physical-card calls store() correctly.
      final response = await _api.postSimple(
        '/physical-card',
        data: {
          'transaction_pin': transactionPin,
          if (deliveryAddress != null) 'delivery_address': deliveryAddress,
        },
        // Ordering now creates the StroWallet customer server-side before
        // charging, which can exceed the default 30s. Give it headroom.
        receiveTimeout: const Duration(seconds: 70),
      );
      if (response.success) {
        await fetchCard();
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

  void clearError() => state = state.copyWith(clearError: true);
}

// ─── Providers ────────────────────────────────────────────────────────────────

final physicalCardNotifierProvider =
    StateNotifierProvider<PhysicalCardNotifier, PhysicalCardState>((ref) {
  return PhysicalCardNotifier(ApiService.instance, ref);
});

final physicalCardProvider = Provider<PhysicalCardModel?>(
    (ref) => ref.watch(physicalCardNotifierProvider).card);