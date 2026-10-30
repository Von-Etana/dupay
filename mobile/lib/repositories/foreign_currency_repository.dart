// lib/repositories/foreign_currency_repository.dart

import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/foreign_currency/foreign_account_model.dart';

class ForeignCurrencyRepository {
  final ApiService _apiService;
  ForeignCurrencyRepository(this._apiService);

  // ── GET /foreign/account/fetch/{currency} ──────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> getForeignAccount(String currency) async {
    try {
      print('💱 ForeignAccountRepository: Fetching $currency foreign account');
      return await _apiService.get<Map<String, dynamic>>(
        '${ApiConstants.foreignAccountFetch}/$currency',
        fromJson: (json) => json,
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── POST /foreign/account/request ──────────────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> requestForeignAccount(
    ForeignAccountRequestPayload payload,
  ) async {
    try {
      return await _apiService.postSimple(
        ApiConstants.foreignAccountRequest,
        data: payload.toJson(),
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── PREVIEW: POST /convert-currency (apply_fee=true, no wallet change) ────
  // We preview WITH the fee applied so the quote the user sees (fee_amount and
  // converted_amount) matches exactly what the execute call will charge and
  // deliver. apply_fee=false returned fee_amount:0 and the gross amount, which
  // over-promised the received amount and hid the fee.
  Future<ApiResponse<Map<String, dynamic>>> previewConversion({
    required String fromCurrency,
    required String toCurrency,
    required double amount,
  }) async {
    try {
      print('💱 ForeignAccountRepository: Previewing conversion $amount $fromCurrency to $toCurrency');
      return await _apiService.postSimple(
        ApiConstants.convertCurrency, // POST /convert-currency
        data: {
          'from_currency': fromCurrency,
          'to_currency':   toCurrency,
          'amount':        amount,
          'apply_fee':     true,
        },
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── EXECUTE: POST /foreign/account/convert-currency (debits wallet) ────────
  Future<ApiResponse<Map<String, dynamic>>> executeConversion({
    required String fromCurrency,
    required String toCurrency,
    required double amount,
    required String transactionPin,
  }) async {
    try {
      print('💱 ForeignAccountRepository: Converting $amount $fromCurrency to $toCurrency');
      return await _apiService.postSimple(
        ApiConstants.foreignConvertCurrency, // POST /foreign/account/convert-currency
        data: {
          'from_currency':   fromCurrency,
          'to_currency':     toCurrency,
          'amount':          amount,
          'transaction_pin': transactionPin,
        },
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── GET /exchange-rates ─────────────────────────────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> getExchangeRates() async {
    try {
      print('💱 ForeignAccountRepository: Fetching exchange rates');
      return await _apiService.get<Map<String, dynamic>>(
        ApiConstants.exchangeRates,
        fromJson: (json) => json,
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // ── POST /fund/withdraw/two (international send) ────────────────────────────
  Future<ApiResponse<Map<String, dynamic>>> sendForeignMoney({
    required String foreignAccountId,
    required String recipientName,
    required String recipientAccount,
    required String currency,
    required double amount,
    String? transactionPin,
  }) async {
    try {
      return await _apiService.postSimple(
        ApiConstants.foreignAccountTransfer,
        data: {
          'foreign_account_id': foreignAccountId,
          'recipient_name':     recipientName,
          'recipient_account':  recipientAccount,
          'currency':           currency,
          'amount':             amount,
          if (transactionPin != null) 'transaction_pin': transactionPin,
        },
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }
}