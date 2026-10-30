// lib/models/auth/user_model.dart
import 'user_profile_model.dart';
import 'wallet_verification_model.dart';

/// Safely converts any JSON value (bool/int/double/String/null) into a String.
/// The backend sometimes returns ints (e.g. 1, 0) or booleans (e.g. true) for
/// fields that the model types as String, which causes runtime TypeErrors.
String _asString(dynamic value, [String fallback = '']) {
  if (value == null) return fallback;
  if (value is String) return value;
  if (value is bool) return value ? '1' : '0';
  return value.toString();
}

int _asInt(dynamic value, [int fallback = 0]) {
  if (value == null) return fallback;
  if (value is int) return value;
  if (value is double) return value.toInt();
  if (value is bool) return value ? 1 : 0;
  if (value is String) return int.tryParse(value) ?? fallback;
  return fallback;
}

class UserModel {
  final int id;
  final String email;
  final String? phoneNumber;
  final String balance;
  final String usdBalance;
  final String eurBalance;
  final String bonus;
  final int userLevelId;
  final String? referral;
  final String banStatus;
  final String accountActivation;
  final String transactionLock;
  final String? transactionPin;
  final DateTime? loginTime;
  final String? emailCode;
  final String? emailTime;
  final String emailVerify;
  final DateTime? emailVerifiedAt;
  final String? phoneCode;
  final String? phoneTime;
  final String phoneVerify;
  final String? sellerMode;
  final int receiveSMS;
  final String? strowalletCustomerId;
  final String? payscribeCustomerId;
  final String? graphPersonId;
  final String dailyTransactionLimit;
  final String blockedStatus;
  final String? fcmToken;
  final DateTime? createdAt;
  final DateTime? updatedAt;
  final DateTime? deletedAt;
  final String hasVerifyWallet;
  final UserProfileModel? profile;
  final WalletVerificationModel? walletVerification;

  UserModel({
    required this.id,
    required this.email,
    this.phoneNumber,
    required this.balance,
    this.usdBalance = '0',
    this.eurBalance = '0',
    required this.bonus,
    required this.userLevelId,
    this.referral,
    required this.banStatus,
    required this.accountActivation,
    required this.transactionLock,
    this.transactionPin,
    this.loginTime,
    this.emailCode,
    this.emailTime,
    required this.emailVerify,
    this.emailVerifiedAt,
    this.phoneCode,
    this.phoneTime,
    required this.phoneVerify,
    this.sellerMode,
    required this.receiveSMS,
    this.strowalletCustomerId,
    this.payscribeCustomerId,
    this.graphPersonId,
    this.dailyTransactionLimit = '0',
    this.blockedStatus = '0',
    this.fcmToken,
    this.createdAt,
    this.updatedAt,
    this.deletedAt,
    required this.hasVerifyWallet,
    this.profile,
    this.walletVerification,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: _asInt(json['id']),
      email: _asString(json['email']),
      phoneNumber:
          json['phone_number'] != null ? _asString(json['phone_number']) : null,
      balance: _asString(json['balance'], '0'),
      usdBalance: _asString(json['usd_balance'], '0'),
      eurBalance: _asString(json['eur_balance'], '0'),
      bonus: _asString(json['bonus'], '0'),
      userLevelId: _asInt(json['user_level_id'], 1),
      referral: json['referral'] != null ? _asString(json['referral']) : null,
      banStatus: _asString(json['ban_status'], '0'),
      accountActivation: _asString(json['account_activation'], '0'),
      transactionLock: _asString(json['transaction_lock'], '0'),
      // Preserve only whether a PIN exists; never keep the server's PIN value.
      transactionPin:
          json['has_transaction_pin'] == true ||
                  (json['transaction_pin'] != null &&
                      _asString(json['transaction_pin']).isNotEmpty)
              ? 'set'
              : null,
      loginTime:
          json['login_time'] != null
              ? DateTime.tryParse(json['login_time'].toString())
              : null,
      emailCode:
          json['email_code'] != null ? _asString(json['email_code']) : null,
      emailTime:
          json['email_time'] != null ? _asString(json['email_time']) : null,
      emailVerify: _asString(json['email_verify'], '0'),
      emailVerifiedAt:
          json['email_verified_at'] != null
              ? DateTime.tryParse(json['email_verified_at'].toString())
              : null,
      phoneCode:
          json['phone_code'] != null ? _asString(json['phone_code']) : null,
      phoneTime:
          json['phone_time'] != null ? _asString(json['phone_time']) : null,
      phoneVerify: _asString(json['phone_verify'], '0'),
      sellerMode:
          json['seller_mode'] != null ? _asString(json['seller_mode']) : null,
      // SMS preference is stored in TWO places by the backend and they can
      // disagree: the top-level user.receive_sms is NOT updated by the
      // /update-sms-alerts endpoint, but profile.receive_sms IS. So the
      // profile value is the source of truth — read it first, then fall back
      // to the top-level field, then `receiveSMS`. Default 1 (on) only if
      // nothing is present.
      receiveSMS: _asInt(
        (json['profile'] is Map
                ? (json['profile'] as Map)['receive_sms']
                : null) ??
            json['receive_sms'] ??
            json['receiveSMS'],
        1,
      ),
      strowalletCustomerId:
          json['strowallet_customer_id'] != null
              ? _asString(json['strowallet_customer_id'])
              : null,
      payscribeCustomerId:
          json['payscribe_customer_id'] != null
              ? _asString(json['payscribe_customer_id'])
              : null,
      graphPersonId:
          json['graph_person_id'] != null
              ? _asString(json['graph_person_id'])
              : null,
      dailyTransactionLimit: _asString(json['daily_transaction_limit'], '0'),
      blockedStatus: _asString(json['blocked_status'], '0'),
      fcmToken: json['fcmToken'] != null ? _asString(json['fcmToken']) : null,
      createdAt:
          json['created_at'] != null
              ? DateTime.tryParse(json['created_at'].toString())
              : null,
      updatedAt:
          json['updated_at'] != null
              ? DateTime.tryParse(json['updated_at'].toString())
              : null,
      deletedAt:
          json['deleted_at'] != null
              ? DateTime.tryParse(json['deleted_at'].toString())
              : null,
      hasVerifyWallet: _asString(json['hasVerifyWallet'], '0'),
      profile:
          json['profile'] != null && json['profile'] is Map<String, dynamic>
              ? UserProfileModel.fromJson(json['profile'])
              : null,
      walletVerification:
          json['wallet_verification'] != null &&
                  json['wallet_verification'] is Map<String, dynamic>
              ? WalletVerificationModel.fromJson(json['wallet_verification'])
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'email': email,
      'phone_number': phoneNumber,
      'balance': balance,
      'usd_balance': usdBalance,
      'eur_balance': eurBalance,
      'bonus': bonus,
      'user_level_id': userLevelId,
      'referral': referral,
      'ban_status': banStatus,
      'account_activation': accountActivation,
      'transaction_lock': transactionLock,
      'transaction_pin': hasTransactionPin ? 'set' : null,
      'login_time': loginTime?.toIso8601String(),
      'email_code': emailCode,
      'email_time': emailTime,
      'email_verify': emailVerify,
      'email_verified_at': emailVerifiedAt?.toIso8601String(),
      'phone_code': phoneCode,
      'phone_time': phoneTime,
      'phone_verify': phoneVerify,
      'seller_mode': sellerMode,
      'receive_sms': receiveSMS,
      'strowallet_customer_id': strowalletCustomerId,
      'payscribe_customer_id': payscribeCustomerId,
      'graph_person_id': graphPersonId,
      'daily_transaction_limit': dailyTransactionLimit,
      'blocked_status': blockedStatus,
      'fcmToken': fcmToken,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
      'deleted_at': deletedAt?.toIso8601String(),
      'hasVerifyWallet': hasVerifyWallet,
      'profile': profile?.toJson(),
      'wallet_verification': walletVerification?.toJson(),
    };
  }

  // Computed properties
  String get displayName {
    if (profile != null) {
      return profile!.displayName;
    }
    return email;
  }

  String get fullName {
    if (profile != null) {
      return profile!.fullName;
    }
    return email;
  }

  UserModel copyWith({int? receiveSMS}) {
    return UserModel(
      id: id,
      email: email,
      phoneNumber: phoneNumber,
      balance: balance,
      usdBalance: usdBalance,
      eurBalance: eurBalance,
      bonus: bonus,
      userLevelId: userLevelId,
      referral: referral,
      banStatus: banStatus,
      accountActivation: accountActivation,
      transactionLock: transactionLock,
      transactionPin: transactionPin,
      loginTime: loginTime,
      emailCode: emailCode,
      emailTime: emailTime,
      emailVerify: emailVerify,
      emailVerifiedAt: emailVerifiedAt,
      phoneCode: phoneCode,
      phoneTime: phoneTime,
      phoneVerify: phoneVerify,
      sellerMode: sellerMode,
      receiveSMS: receiveSMS ?? this.receiveSMS,
      strowalletCustomerId: strowalletCustomerId,
      payscribeCustomerId: payscribeCustomerId,
      graphPersonId: graphPersonId,
      dailyTransactionLimit: dailyTransactionLimit,
      blockedStatus: blockedStatus,
      fcmToken: fcmToken,
      createdAt: createdAt,
      updatedAt: updatedAt,
      deletedAt: deletedAt,
      hasVerifyWallet: hasVerifyWallet,
      profile: profile,
      walletVerification: walletVerification,
    );
  }

  bool get isEmailVerified => emailVerify == '1';
  bool get isPhoneVerified => phoneVerify == '1';
  bool get isWalletVerified => hasVerifyWallet == '1';
  bool get isBanned => banStatus == '1';
  bool get isAccountActivated => accountActivation == '1';
  bool get isTransactionLocked => transactionLock == '1';
  bool get hasTransactionPin =>
      transactionPin != null && transactionPin!.isNotEmpty;
  bool get isProfileComplete => profile != null && profile!.fullName.isNotEmpty;

  double get balanceAmount => double.tryParse(balance) ?? 0.0;
  double get bonusAmount => double.tryParse(bonus) ?? 0.0;
  double get usdBalanceAmount => double.tryParse(usdBalance) ?? 0.0;
  double get eurBalanceAmount => double.tryParse(eurBalance) ?? 0.0;
  double get dailyLimitAmount => double.tryParse(dailyTransactionLimit) ?? 0.0;
}
