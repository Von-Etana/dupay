// lib/core/constants/api_contants.dart
import 'package:flutter/foundation.dart' show kReleaseMode;

// ──────────────────────────────────────────────────────────────────────────────
// REPLACE the existing ApiConstants class with this updated version.
// All previously existing constants are preserved; new ones are added below.
// ──────────────────────────────────────────────────────────────────────────────

class ApiConstants {
  // Base URLs
  static const String baseUrl = 'https://mydupay.com/api/v1';

  // Timeout configurations
  static const int connectTimeout = 30000;
  static const int receiveTimeout = 30000;
  static const int sendTimeout = 30000;

  // Headers
  static const String contentType = 'application/json';
  static const String authHeader = 'Authorization';
  static const String ismart = 'api';
  static const String bearerPrefix = 'Bearer';

  // ── Account ───────────────────────────────────────────────────────────────
  static const String virtualAccount = '/account/virtual';
  static const String bankAccount = '/bank-account';
  static const String storeBankAccount = '/bank-account/add';
  static const String deleteBankAccount = '/bank-account/delete';

  // Virtual Account (fund wallet)
  static const String virtualAccountFetch = '/virtual-account/fetch';
  static const String virtualAccountCreate = '/virtual-account/create';

  // ── Recharge card (ePIN) ──────────────────────────────────────────────────  ← added
  static const String buyEpin    = '/buy-epin';                                
  static const String fetchEpins = '/user/fetch-epins';                          


  // ── Airtime ───────────────────────────────────────────────────────────────
  static const String airtimeNetwork = '/airtime/networks';
  static const String buyAirtime = '/buy/airtime';
  static const String airtimeToCashNetworks = '/airtime-to-cash/networks';
  static const String convertAirtimeToCash = '/convert-airtime-to-cash';

  // ── Auth ──────────────────────────────────────────────────────────────────
  static const String login = '/login';
  static const String logout = '/logout';
  static const String register = '/register';
  static const String resetPassword = '/reset-password';
  static const String sendEmailOtp = '/send-email-otp';
  static const String verifyEmailOtp = '/verify-email-otp';
  static const String resetPasswordVerified = '/reset-password-verified';
  static const String checkUser = '/check-user';
  static const String emailVerificationSubmit = '/email-verification-submit';
  static const String resendVerifySubmit = '/resend-verify-submit';
  static const String getUserInfo = '/get-updated-user-information';

  // ── Basic ─────────────────────────────────────────────────────────────────
  static const String basicSiteSettings = '/basic/site-settings';
  static const String getData = '/basic/data';
  static const String itemType = '/basic/item-type';

  // ── Bill ─────────────────────────────────────────────────────────────────
  static const String getBills = '/bill-list';
  static const String payBill = '/pay-bill';
  static const String verifyBill = '/verify-bill';

  // ── Cable ─────────────────────────────────────────────────────────────────
  static const String getCables = '/cable-list';
  static const String buyCable = '/buy/cable';
  static const String verifyCableNumber = '/verify-cable-number';

  // ── Card ──────────────────────────────────────────────────────────────────
  static const String virtualCardFund = '/card/virtual/fund';
  static const String virtualCardWithdraw = '/card/virtual/withdraw';
  static const String virtualCardUpdateStatus = '/card/virtual/update-status';
  static const String virtualCardTransaction = '/card/virtual/transactions';
  static const String physicalCard = '/card/physical';
  static const String setupCard = '/card/setup';

  // Fees (physical card creation fee, naira/dollar card fees, min deposits)
  static const String fees = '/fees';

  // ── Data ──────────────────────────────────────────────────────────────────
  static const String getDataNetworks = '/data/networks';
  static const String buyData = '/buy/data';
  static const String getDataCardNetworks = '/data-card/networks';
  static const String buyDataCard = '/buy/data-card';

  // ── Exam ──────────────────────────────────────────────────────────────────
  static const String examAmount = '/exam/amount';
  static const String buyExam = '/exam/buy';

  // ── KYC ───────────────────────────────────────────────────────────────────
  // FIXED: every path in this group previously 404'd. They are now verified
  // against routes/api/v1/user.php (lines 116-120) and are appended to
  // baseUrl 'https://mydupay.com/api/v1'.
  //
  // WALLET KYC -> wallet_verificaitons table. This is the flow the admin
  // Verifications page reads, and the one /register already populates on
  // signup. The KYC screen uses these.
  static const String walletKycDetails = '/wallet-kyc-details';
  static const String walletKycVerification = '/verification/wallet-kyc';
  static const String bankVerification = '/verification/bank';

  // PROFILE KYC -> profile_kycs table. Paths are now correct, but note that
  // no admin page reads that table, so anything submitted here cannot be
  // reviewed. Kept so nothing else in the app breaks; the KYC screen no
  // longer uses them.
  static const String profileKycDetails = '/profile-kyc-details';
  static const String profileKycVerification = '/verification/profile-kyc';

  // ── Payment ───────────────────────────────────────────────────────────────
  static const String flutterwaveAtmPayment = '/payment/flutterwave-atm';

  // ── Transaction ───────────────────────────────────────────────────────────
  /// GET  /transactions/{search?}
  static const String transactionSearch = '/transactions';
  static const String createTransactionPin = '/transactions/create-pin';
  static const String changeTransactionPin = '/transactions/change-pin';

  // ── Transfer / Send Money ─────────────────────────────────────────────────
  static const String transferUsdToBank = '/bank-transfer/usd-to-bank';
  /// POST /fund/transfer  – P2P wallet transfer
  static const String fundTransfer = '/fund/transfer';
  /// POST /fund/request   – request money from another user
  static const String fundRequest = '/fund/request';
  /// POST /fund/withdraw  – bank withdrawal
  static const String fundWithdraw = '/fund/withdraw';
  /// POST /fund/manual-funding
  static const String manualFunding = '/fund/manual-funding';
  /// GET  /bank-transfer/banklist
  static const String bankList = '/bank-transfer/banklist';

  // ── Users ─────────────────────────────────────────────────────────────────
  static const String updateProfileField = '/update-profile-field';
  /// POST /update-fcm-token – keeps the device push token current after login.
  static const String updateFcmToken = '/update-fcm-token';
  static const String allUsers = '/all-users';
  static const String searchUsers = '/all-users/search';
  static const String findUser = '/find-user';
  static const String getUser = '/get-user';
  static const String changePassword = '/setting/change-password';
  static const String updateProfile = '/setting/update-profile';
  static const String upgradeAccount = '/setting/account-upgrade';

  // ── POS ───────────────────────────────────────────────────────────────────
  /// GET  /pos-request  – fetch existing request details
  static const String posRequest = '/pos-request';
  /// POST /post-request – submit new POS request
  static const String posPostRequest = '/post-request';

  // ── Foreign Currency ──────────────────────────────────────────────────────
  /// POST /foreign/account/request
  static const String foreignAccountRequest = '/foreign/account/request';
  /// POST /foreign/account/create
  static const String foreignAccountCreate = '/foreign/account/create';
  /// GET  /foreign/account/fetch/{currency}
  static const String foreignAccountFetch = '/foreign/account/fetch';
  /// POST /foreign/account/whitelist/add/{id}
  static const String foreignAccountWhitelistAdd = '/foreign/account/whitelist/add';
  /// POST /foreign/account/whitelist/list/{id}
  static const String foreignAccountWhitelistList = '/foreign/account/whitelist/list';
  /// POST /foreign/account/whitelist/remove/{id}/{item_id}
  static const String foreignAccountWhitelistRemove = '/foreign/account/whitelist/remove';

  // ── Currency Conversion ───────────────────────────────────────────────────
  /// POST /foreign/account/convert-currency  (foreign account convert)
  static const String foreignConvertCurrency = '/foreign/account/convert-currency';
  /// POST /convert-currency  (general NGN↔foreign convert)
  static const String convertCurrency = '/convert-currency';
  /// GET  /exchange-rates
  static const String exchangeRates = '/exchange-rates';

  // ── (Foreign) Send Money ──────────────────────────────────────────────────
  /// Reuse fundWithdraw for international bank transfers via the backend
  static const String foreignAccountTransfer = '/fund/withdraw/two';
}

class EnvironmentConfig {
  static const String _environment = String.fromEnvironment(
    'ENV',
    defaultValue: 'dev',
  );
  static const String _nonProductionBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
  );

  static bool get isProduction =>
      _environment == 'prod' ||
      (kReleaseMode && _environment == 'dev' && _nonProductionBaseUrl.isEmpty);
  static bool get isStaging => _environment == 'staging';
  static bool get isDevelopment => !isProduction && !isStaging;

  static String get baseUrl {
    if (isProduction) return ApiConstants.baseUrl;

    if (!isStaging && !isDevelopment) {
      throw StateError('Unknown app environment: $_environment');
    }

    final url = Uri.tryParse(_nonProductionBaseUrl);
    if (url == null ||
        url.scheme != 'https' ||
        url.host.isEmpty ||
        url.host == 'mydupay.com' ||
        url.host == 'www.mydupay.com') {
      throw StateError(
        'Set API_BASE_URL to a separate HTTPS API for $_environment builds.',
      );
    }

    return url.toString();
  }

}
