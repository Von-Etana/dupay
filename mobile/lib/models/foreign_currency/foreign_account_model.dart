// lib/models/foreign_currency/foreign_account_model.dart

class ForeignAccountModel {
  final int? id;
  final int? userId;
  final String currency;
  final String status; // pending | active | rejected
  final bool active;
  final String? accountNumber;
  final String? accountName;
  final String? bankName;
  final String? bankCode;
  final String? routingNumber;
  final String? swiftCode;
  final String? iban;
  final String? bankAddress;
  final String? beneficiaryAddress;
  final double balance;
  final String? accountId;
  final String? createdAt;
  final String? updatedAt;

  ForeignAccountModel({
    this.id,
    this.userId,
    required this.currency,
    required this.status,
    required this.active,
    this.accountNumber,
    this.accountName,
    this.bankName,
    this.bankCode,
    this.routingNumber,
    this.swiftCode,
    this.iban,
    this.bankAddress,
    this.beneficiaryAddress,
    this.balance = 0.0,
    this.accountId,
    this.createdAt,
    this.updatedAt,
  });

  factory ForeignAccountModel.fromJson(Map<String, dynamic> json) {
    return ForeignAccountModel(
      id: json['id'],
      userId: json['user_id'],
      currency: json['currency'] ?? '',
      status: json['status'] ?? 'inactive', // default to inactive NOT pending
      active: json['active'] == true || json['active'] == 1,
      accountNumber: json['account_number'],
      accountName: json['account_name'],
      bankName: json['bank_name'],
      bankCode: json['bank_code'],
      routingNumber: json['routing_number'],
      swiftCode: json['swift_code'],
      iban: json['iban'],
      bankAddress: json['bank_address'],
      beneficiaryAddress: json['beneficiary_address'],
      balance: double.tryParse(json['balance']?.toString() ?? '0') ?? 0.0,
      accountId: json['account_id'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() => {
        'currency': currency,
        'status': status,
        'active': active,
        if (accountNumber != null) 'account_number': accountNumber,
        if (accountName != null) 'account_name': accountName,
        if (bankName != null) 'bank_name': bankName,
      };

  bool get isPending => status == 'pending';
  bool get isActive => status == 'active';
  bool get isRejected => status == 'rejected';
}

class ForeignAccountRequestPayload {
  final String currency;
  final String bvn;           // required — 11 digits
  final String addressLine1;  // required
  final String? addressLine2; // optional
  final String city;          // required
  final String state;         // required
  final String country;       // required
  final String postalCode;    // required
  final String employmentStatus;
  final String occupation;
  final String primaryPurpose;
  final String sourceOfFunds;
  final double expectedMonthlyInflow;
  final String document; // base64
  final String documentType;
  final String? documentIssueDate;
  final String? documentExpiryDate;
  final String document2; // base64
  final String document2Type;
  final String? document2IssueDate;
  final String? document2ExpiryDate;
  final String? transactionPin;

  ForeignAccountRequestPayload({
    required this.currency,
    required this.bvn,
    required this.addressLine1,
    this.addressLine2,
    required this.city,
    required this.state,
    required this.country,
    required this.postalCode,
    required this.employmentStatus,
    required this.occupation,
    required this.primaryPurpose,
    required this.sourceOfFunds,
    required this.expectedMonthlyInflow,
    required this.document,
    required this.documentType,
    this.documentIssueDate,
    this.documentExpiryDate,
    required this.document2,
    required this.document2Type,
    this.document2IssueDate,
    this.document2ExpiryDate,
    this.transactionPin,
  });

  Map<String, dynamic> toJson() => {
        'currency': currency,
        'bvn': bvn,
        'address_line1': addressLine1,
        if (addressLine2 != null && addressLine2!.isNotEmpty) 'address_line2': addressLine2,
        'city': city,
        'state': state,
        'country': country,
        'postal_code': postalCode,
        'employment_status': employmentStatus,
        'occupation': occupation,
        'primary_purpose': primaryPurpose,
        'source_of_funds': sourceOfFunds,
        'expected_monthly_inflow': expectedMonthlyInflow,
        'document': document,
        'document_type': documentType,
        if (documentIssueDate != null) 'document_issue_date': documentIssueDate,
        if (documentExpiryDate != null) 'document_expiry_date': documentExpiryDate,
        'document2': document2,
        'document2_type': document2Type,
        if (document2IssueDate != null) 'document2_issue_date': document2IssueDate,
        if (document2ExpiryDate != null) 'document2_expiry_date': document2ExpiryDate,
        if (transactionPin != null) 'transaction_pin': transactionPin,
      };
}

class CurrencyConversionResult {
  final String fromCurrency;
  final String toCurrency;
  final double exchangeRate;
  final double feeAmount;
  final double originalAmount;
  final double convertedAmount;
  final double finalAmountReceived;
  final double totalDeducted;
  final Map<String, double> balances;

  CurrencyConversionResult({
    required this.fromCurrency,
    required this.toCurrency,
    required this.exchangeRate,
    required this.feeAmount,
    required this.originalAmount,
    required this.convertedAmount,
    required this.finalAmountReceived,
    required this.totalDeducted,
    required this.balances,
  });

  factory CurrencyConversionResult.fromJson(Map<String, dynamic> json) {
    final balancesRaw = json['balances'];
    final balancesJson = balancesRaw is Map<String, dynamic> ? balancesRaw : <String, dynamic>{};
    return CurrencyConversionResult(
      fromCurrency: json['from_currency'] ?? '',
      toCurrency: json['to_currency'] ?? '',
      exchangeRate: double.tryParse(json['exchange_rate']?.toString() ?? '0') ?? 0,
      feeAmount: double.tryParse(json['fee_amount']?.toString() ?? '0') ?? 0,
      originalAmount: double.tryParse(json['original_amount']?.toString() ?? '0') ?? 0,
      convertedAmount: double.tryParse(json['converted_amount']?.toString() ?? '0') ?? 0,
      finalAmountReceived: double.tryParse(json['final_amount_received']?.toString() ?? '0') ?? 0,
      totalDeducted: double.tryParse(json['total_deducted']?.toString() ?? '0') ?? 0,
      balances: balancesJson.map((k, v) => MapEntry(k, double.tryParse(v.toString()) ?? 0)),
    );
  }
}

class ExchangeRate {
  final String fromCurrency;
  final String toCurrency;
  final double rate;

  ExchangeRate({
    required this.fromCurrency,
    required this.toCurrency,
    required this.rate,
  });

  factory ExchangeRate.fromJson(Map<String, dynamic> json) {
    return ExchangeRate(
      fromCurrency: json['from'] ?? '',
      toCurrency: json['to'] ?? '',
      rate: double.tryParse(json['rate']?.toString() ?? '0') ?? 0,
    );
  }
}