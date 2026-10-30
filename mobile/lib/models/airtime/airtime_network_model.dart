class AirtimeTypeModel {
  final int? id;
  final String? name;
  final int? networkId;

  AirtimeTypeModel({this.id, this.name, this.networkId});

  factory AirtimeTypeModel.fromJson(Map<String, dynamic> json) {
    return AirtimeTypeModel(
      id: json['id'],
      name: json['name'],
      networkId: json['network_id'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'name': name, 'network_id': networkId};
  }
}

class AirtimeNetworkModel {
  final int? id;
  final String? name;
  final String? otherName;
  final String? extraId;
  final String? image;
  final List<AirtimeTypeModel>? airtimeType;

  AirtimeNetworkModel({
    this.id,
    this.name,
    this.otherName,
    this.extraId,
    this.image,
    this.airtimeType,
  });

  factory AirtimeNetworkModel.fromJson(Map<String, dynamic> json) {
    return AirtimeNetworkModel(
      id: json['id'],
      name: json['name'],
      otherName: json['other_name'],
      extraId: json['extra_id'],
      image: json['image'],
      airtimeType:
          json['airtime_type'] != null
              ? (json['airtime_type'] as List)
                  .map((type) => AirtimeTypeModel.fromJson(type))
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
      'airtime_type': airtimeType?.map((type) => type.toJson()).toList(),
    };
  }
}
