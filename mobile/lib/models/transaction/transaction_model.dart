import 'package:dupay/core/utils/formatters.dart';

class TransactionModel {
  final String id;
  final String type;
  final String status;
  final String amount;
  final String? description;
  final String? reference;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final Map<String, dynamic>? metadata;

  TransactionModel({
    required this.id,
    required this.type,
    required this.status,
    required this.amount,
    this.description,
    this.reference,
    required this.createdAt,
    this.updatedAt,
    this.metadata,
  });

  static String _cleanAmount(String raw) {
    // Remove currency symbols, commas, spaces — keep digits and dot
    final cleaned = raw.replaceAll(RegExp(r'[^\d.]'), '');
    if (cleaned.isEmpty) return '0';
    return cleaned;
  }

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    // Merge all root-level fields into metadata for easy access in UI
    final meta = <String, dynamic>{
      ...?(json['metadata'] is Map<String, dynamic>
          ? json['metadata'] as Map<String, dynamic>
          : null),
      if (json['service_type'] != null)    'service_type':    json['service_type'],
      if (json['phone_number'] != null)    'phone_number':    json['phone_number'],
      if (json['session_id'] != null)      'session_id':      json['session_id']?.toString(),
      if (json['initial_balance'] != null) 'initial_balance': _cleanAmount(json['initial_balance'].toString()),
      if (json['final_balance'] != null)   'final_balance':   _cleanAmount(json['final_balance'].toString()),
    };
    return TransactionModel(
      id:          json['id']?.toString() ?? '',
      type:        json['type']?.toString() ?? '',
      status:      json['status']?.toString() ?? '',
      // amount comes as "$8,800,000.00" or "₦5,100.00" — strip symbols/commas
      amount:      _cleanAmount(json['amount']?.toString() ?? '0'),
      description: json['description']?.toString(),
      reference:   json['reference']?.toString() ?? json['paymentReference']?.toString(),
      createdAt:
          DateTime.tryParse(json['created_at']?.toString() ?? '') ??
          DateTime.now(),
      updatedAt:
          json['updated_at'] != null
              ? DateTime.tryParse(json['updated_at'].toString())
              : null,
      metadata: meta,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'type': type,
      'status': status,
      'amount': amount,
      if (description != null) 'description': description,
      if (reference != null) 'reference': reference,
      'created_at': createdAt.toIso8601String(),
      if (updatedAt != null) 'updated_at': updatedAt!.toIso8601String(),
      if (metadata != null) 'metadata': metadata,
    };
  }

  // Helper getters
  bool get isSuccessful => status.toLowerCase() == 'success';
  bool get isPending => status.toLowerCase() == 'pending';
  bool get isFailed => status.toLowerCase() == 'failed';

  double get amountValue => double.tryParse(amount) ?? 0.0;

  String get formattedAmount => formatCurrency(amountValue);

  String get formattedDate =>
      '${createdAt.day}/${createdAt.month}/${createdAt.year}';

  String get formattedTime =>
      '${createdAt.hour.toString().padLeft(2, '0')}:${createdAt.minute.toString().padLeft(2, '0')}';
}