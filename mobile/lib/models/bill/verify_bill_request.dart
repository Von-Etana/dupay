class VerifyBillRequest {
  final String discoId;
  final String meterNumber;
  final String meterType;

  VerifyBillRequest({
    required this.discoId,
    required this.meterNumber,
    required this.meterType,
  });

  Map<String, dynamic> toJson() {
    return {
      'disco_id': discoId,
      'meter_number': meterNumber,
      'meter_type': meterType,
    };
  }
}











