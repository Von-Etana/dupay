class PayBillRequest {
  final String phoneNumber;
  final String discoId;
  final String meterNumber;
  final String meterType;
  final String amount;
  final String transactionPin;

  PayBillRequest({
    required this.phoneNumber,
    required this.discoId,
    required this.meterNumber,
    required this.meterType,
    required this.amount,
     required this.transactionPin,
  });

  Map<String, dynamic> toJson() {
    return {
      'phone_number': phoneNumber,
      'disco_id': discoId,
      'meter_number': meterNumber,
      'meter_type': meterType,
      'amount': amount,
      'transaction_pin': transactionPin,
    };
  }
}











