import 'cable_model.dart';

class CablesResponse {
  final bool success;
  final String? message;
  final List<CableProviderModel>? data;

  CablesResponse({required this.success, this.message, this.data});

  factory CablesResponse.fromJson(Map<String, dynamic> json) {
    return CablesResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data:
          json['data'] != null
              ? (json['data'] as List)
                  .map((provider) => CableProviderModel.fromJson(provider))
                  .toList()
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'message': message,
      'data': data?.map((provider) => provider.toJson()).toList(),
    };
  }
}










