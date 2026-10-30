class BillModel {
  final int? id;
  final String? disco;
  final String? name;
  final int? priceRate;

  BillModel({this.id, this.disco, this.name, this.priceRate});

  factory BillModel.fromJson(Map<String, dynamic> json) {
    return BillModel(
      id: json['id'],
      disco: json['disco'],
      name: json['name'],
      priceRate: json['price_rate'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'disco': disco, 'name': name, 'price_rate': priceRate};
  }
}











