// lib/models/fund_wallet/virtual_account_model.dart

class VirtualAccountModel {
  final int? id;
  final String? accountNumber;
  final String? accountName;
  final String? bankName;
  final String? bankCode;
  final String? reference;
  final String? status;
  final String? createdAt;

  VirtualAccountModel({
    this.id,
    this.accountNumber,
    this.accountName,
    this.bankName,
    this.bankCode,
    this.reference,
    this.status,
    this.createdAt,
  });

  factory VirtualAccountModel.fromJson(Map<String, dynamic> json) {
    // Handle nested data (API may return account under 'account_details' or root)
    final detailsRaw = json['account_details'];
    final data = detailsRaw is Map<String, dynamic> ? detailsRaw : json;
    return VirtualAccountModel(
      id: json['id'],
      accountNumber: data['account_number']?.toString() ?? json['account_number']?.toString(),
      accountName: data['account_name']?.toString() ?? json['account_name']?.toString(),
      bankName: data['bank_name']?.toString() ?? json['bank_name']?.toString(),
      bankCode: data['bank_code']?.toString() ?? json['bank_code']?.toString(),
      reference: json['reference']?.toString(),
      status: json['status']?.toString(),
      createdAt: json['created_at']?.toString(),
    );
  }

  bool get hasAccount => accountNumber != null && accountNumber!.isNotEmpty;
}
