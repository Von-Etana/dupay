// Matches backend POST /buy/data body:
// { "network_id": 1, "phone_number": "...", "plan_id": 1, "dataType_id": 12, "transaction_pin": "1234" }
class BuyDataRequest {
  final String phoneNumber;
  final String networkId;
  final String planId;
  final String dataTypeId;
  final String transactionPin;

  BuyDataRequest({
    required this.phoneNumber,
    required this.networkId,
    required this.planId,
    required this.dataTypeId,
    required this.transactionPin,
  });

  Map<String, dynamic> toJson() {
    return {
      'network_id': networkId,
      'phone_number': phoneNumber,
      'plan_id': planId,
      'dataType_id': dataTypeId,
      'transaction_pin': transactionPin,
    };
  }
}