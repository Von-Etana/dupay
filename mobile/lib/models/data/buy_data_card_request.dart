// Matches backend POST /buy/data-card body:
// { "network_id": 1, "plan_id": 1, "name": "John Doe", "quantity": 1, "transaction_pin": "1234" }
class BuyDataCardRequest {
  final String name;
  final String quantity;
  final String networkId;
  final String planId;
  final String transactionPin;

  BuyDataCardRequest({
    required this.name,
    required this.quantity,
    required this.networkId,
    required this.planId,
    required this.transactionPin,
  });

  Map<String, dynamic> toJson() {
    return {
      'network_id': networkId,
      'plan_id': planId,
      'name': name,
      'quantity': quantity,
      'transaction_pin': transactionPin,
    };
  }
}