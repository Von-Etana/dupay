import 'bill_model.dart';

class BillsResponse {
  final bool success;
  final String? message;
  final List<BillModel>? data;

  BillsResponse({required this.success, this.message, this.data});

  factory BillsResponse.fromJson(Map<String, dynamic> json) {
    return BillsResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data:
          json['data'] != null
              ? (json['data'] as List)
                  .map((bill) => BillModel.fromJson(bill))
                  .toList()
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'message': message,
      'data': data?.map((bill) => bill.toJson()).toList(),
    };
  }
}











