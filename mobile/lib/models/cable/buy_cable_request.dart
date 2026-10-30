class BuyCableRequest {
  final String phoneNumber;
  final String planId;
  final String cableId;
  final String decoderNumber;
  final String? transactionPin;

  BuyCableRequest({
    required this.phoneNumber,
    required this.planId,
    required this.cableId,
    required this.decoderNumber,
    this.transactionPin,
  });

  Map<String, dynamic> toJson() {
    return {
      'phone_number': phoneNumber,
      'plan_id': planId,
      'cable_id': cableId,
      'decoder_number': decoderNumber,
      if (transactionPin != null) 'transaction_pin': transactionPin,
    };
  }
}