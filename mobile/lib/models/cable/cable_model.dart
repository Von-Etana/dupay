class CableModel {
  final int? id;
  final String? name;

  CableModel({this.id, this.name});

  factory CableModel.fromJson(Map<String, dynamic> json) {
    return CableModel(id: json['id'], name: json['name']);
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'name': name};
  }
}

class CableListModel {
  final int? id;
  final String? name;
  final int? amount;
  final CableModel? cable;

  CableListModel({this.id, this.name, this.amount, this.cable});

  factory CableListModel.fromJson(Map<String, dynamic> json) {
    return CableListModel(
      id: json['id'],
      name: json['name'],
      amount: json['amount'],
      cable: json['cable'] != null ? CableModel.fromJson(json['cable']) : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'name': name, 'amount': amount, 'cable': cable?.toJson()};
  }
}

class CableProviderModel {
  final int? id;
  final String? name;
  final String? image;
  final List<CableListModel>? cableList;

  CableProviderModel({this.id, this.name, this.image, this.cableList});

  factory CableProviderModel.fromJson(Map<String, dynamic> json) {
    return CableProviderModel(
      id: json['id'],
      name: json['name'],
      image: json['image'],
      cableList:
          json['cable_list'] != null
              ? (json['cable_list'] as List)
                  .map((plan) => CableListModel.fromJson(plan))
                  .toList()
              : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'image': image,
      'cable_list': cableList?.map((plan) => plan.toJson()).toList(),
    };
  }
}










