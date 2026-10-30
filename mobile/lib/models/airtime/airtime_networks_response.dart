import 'airtime_network_model.dart';

class AirtimeNetworksResponse {
  final bool success;
  final String? message;
  final List<AirtimeNetworkModel>? data;

  AirtimeNetworksResponse({required this.success, this.message, this.data});

  factory AirtimeNetworksResponse.fromJson(Map<String, dynamic> json) {
    return AirtimeNetworksResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data:
          json['data'] != null
              ? (json['data'] as List)
                  .map((network) => AirtimeNetworkModel.fromJson(network))
                  .toList()
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'message': message,
      'data': data?.map((network) => network.toJson()).toList(),
    };
  }
}
