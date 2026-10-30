import 'airtime_network_model.dart';

class AirtimeCashTypeModel {
  final int? id;
  final String? name;
  final int? networkId;

  AirtimeCashTypeModel({this.id, this.name, this.networkId});

  factory AirtimeCashTypeModel.fromJson(Map<String, dynamic> json) {
    return AirtimeCashTypeModel(
      id: json['id'],
      name: json['name'],
      networkId: json['network_id'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'name': name, 'network_id': networkId};
  }
}

class AirtimeToCashNetworkModel {
  final int? id;
  final String? name;
  final String? otherName;
  final String? extraId;
  final String? image;
  final List<AirtimeCashTypeModel>? airtimeCashType;

  AirtimeToCashNetworkModel({
    this.id,
    this.name,
    this.otherName,
    this.extraId,
    this.image,
    this.airtimeCashType,
  });

  factory AirtimeToCashNetworkModel.fromJson(Map<String, dynamic> json) {
    return AirtimeToCashNetworkModel(
      id: json['id'],
      name: json['name'],
      otherName: json['other_name'],
      extraId: json['extra_id'],
      image: json['image'],
      airtimeCashType:
          json['airtime_cash_type'] != null
              ? (json['airtime_cash_type'] as List)
                  .map((type) => AirtimeCashTypeModel.fromJson(type))
                  .toList()
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'other_name': otherName,
      'extra_id': extraId,
      'image': image,
      'airtime_cash_type':
          airtimeCashType?.map((type) => type.toJson()).toList(),
    };
  }
}

class AirtimeToCashNetworksResponse {
  final bool success;
  final String? message;
  final List<AirtimeToCashNetworkModel>? data;

  AirtimeToCashNetworksResponse({
    required this.success,
    this.message,
    this.data,
  });

  factory AirtimeToCashNetworksResponse.fromJson(Map<String, dynamic> json) {
    return AirtimeToCashNetworksResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data:
          json['data'] != null
              ? (json['data'] as List)
                  .map((network) => AirtimeToCashNetworkModel.fromJson(network))
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











