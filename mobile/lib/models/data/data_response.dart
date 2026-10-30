import 'data_model.dart';

class DataResponse {
  final bool success;
  final String? message;
  final List<DataNetworkWithTypesModel>? data;

  DataResponse({required this.success, this.message, this.data});

  factory DataResponse.fromJson(Map<String, dynamic> json) {
    return DataResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data:
          json['data'] != null
              ? (json['data'] as List)
                  .map((network) => DataNetworkWithTypesModel.fromJson(network))
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

class DataCardNetworksResponse {
  final bool success;
  final String? message;
  final List<DataNetworkModel>? data;

  DataCardNetworksResponse({required this.success, this.message, this.data});

  factory DataCardNetworksResponse.fromJson(Map<String, dynamic> json) {
    return DataCardNetworksResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data:
          json['data'] != null
              ? (json['data'] as List)
                  .map((network) => DataNetworkModel.fromJson(network))
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










