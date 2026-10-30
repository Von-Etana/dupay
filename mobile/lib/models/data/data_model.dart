class DataNetworkModel {
  final int? id;
  final String? name;
  final String? otherName;
  final String? extraId;
  final String? image;

  DataNetworkModel({
    this.id,
    this.name,
    this.otherName,
    this.extraId,
    this.image,
  });

  factory DataNetworkModel.fromJson(Map<String, dynamic> json) {
    return DataNetworkModel(
      id: json['id'],
      name: json['name'],
      otherName: json['other_name'],
      extraId: json['extra_id'],
      image: json['image'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'other_name': otherName,
      'extra_id': extraId,
      'image': image,
    };
  }
}

class DataTypeModel {
  final int? id;
  final int? networkId;
  final String? name;
  final String? networkName;

  DataTypeModel({this.id, this.networkId, this.name, this.networkName});

  factory DataTypeModel.fromJson(Map<String, dynamic> json) {
    return DataTypeModel(
      id: json['id'],
      networkId: json['network_id'],
      name: json['name'],
      networkName: json['networkName'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'network_id': networkId,
      'name': name,
      'networkName': networkName,
    };
  }
}

class DataPlanModel {
  final int? id;
  final int? networkId;
  final int? dataTypeId;
  final String? name;
  final String? code;
  final String? variationCode;
  final String? extra;
  final String? size;
  final int? price;
  final String? promoId;
  final String? status;
  final String? createdAt;
  final String? updatedAt;
  final String? network;
  final String? type;
  final DataNetworkModel? networks;
  final DataTypeModel? dataType;

  DataPlanModel({
    this.id,
    this.networkId,
    this.dataTypeId,
    this.name,
    this.code,
    this.variationCode,
    this.extra,
    this.size,
    this.price,
    this.promoId,
    this.status,
    this.createdAt,
    this.updatedAt,
    this.network,
    this.type,
    this.networks,
    this.dataType,
  });

  factory DataPlanModel.fromJson(Map<String, dynamic> json) {
    return DataPlanModel(
      id: json['id'],
      networkId: json['network_id'],
      dataTypeId: json['data_type_id'],
      name: json['name'],
      code: json['code'],
      variationCode: json['variation_code'],
      extra: json['extra'],
      size: json['size'],
      price: json['price'],
      promoId: json['promo_id'],
      status: json['status'],
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
      network: json['network'],
      type: json['type'],
      networks:
          json['networks'] != null
              ? DataNetworkModel.fromJson(json['networks'])
              : null,
      dataType:
          json['data_type'] != null
              ? DataTypeModel.fromJson(json['data_type'])
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'network_id': networkId,
      'data_type_id': dataTypeId,
      'name': name,
      'code': code,
      'variation_code': variationCode,
      'extra': extra,
      'size': size,
      'price': price,
      'promo_id': promoId,
      'status': status,
      'created_at': createdAt,
      'updated_at': updatedAt,
      'network': network,
      'type': type,
      'networks': networks?.toJson(),
      'data_type': dataType?.toJson(),
    };
  }
}

class DataTypeWithPlansModel {
  final int? id;
  final int? networkId;
  final String? name;
  final String? networkName;
  final List<DataPlanModel>? plan;

  DataTypeWithPlansModel({
    this.id,
    this.networkId,
    this.name,
    this.networkName,
    this.plan,
  });

  factory DataTypeWithPlansModel.fromJson(Map<String, dynamic> json) {
    return DataTypeWithPlansModel(
      id: json['id'],
      networkId: json['network_id'],
      name: json['name'],
      networkName: json['networkName'],
      plan:
          json['plan'] != null
              ? (json['plan'] as List)
                  .map((planData) => DataPlanModel.fromJson(planData))
                  .toList()
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'network_id': networkId,
      'name': name,
      'networkName': networkName,
      'plan': plan?.map((planData) => planData.toJson()).toList(),
    };
  }
}

class DataNetworkWithTypesModel {
  final int? id;
  final String? name;
  final String? otherName;
  final String? extraId;
  final String? image;
  final List<DataTypeWithPlansModel>? dataType;

  DataNetworkWithTypesModel({
    this.id,
    this.name,
    this.otherName,
    this.extraId,
    this.image,
    this.dataType,
  });

  factory DataNetworkWithTypesModel.fromJson(Map<String, dynamic> json) {
    return DataNetworkWithTypesModel(
      id: json['id'],
      name: json['name'],
      otherName: json['other_name'],
      extraId: json['extra_id'],
      image: json['image'],
      dataType:
          json['data_type'] != null
              ? (json['data_type'] as List)
                  .map((typeData) => DataTypeWithPlansModel.fromJson(typeData))
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
      'data_type': dataType?.map((typeData) => typeData.toJson()).toList(),
    };
  }
}