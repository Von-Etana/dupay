class BuyAirtimeRequest {
  final String phoneNumber;
  final String networkId;
  final String typeId;
  final String amount;
  final String transactionPin;

  BuyAirtimeRequest({
    required this.phoneNumber,
    required this.networkId,
    required this.typeId,
    required this.amount,
    required this.transactionPin,
  });

  Map<String, dynamic> toJson() {
    return {
      'phone_number': phoneNumber,
      'network_id': networkId,
      'type_id': typeId,
      'amount': amount,
      'transaction_pin': transactionPin,
    };
  }
}