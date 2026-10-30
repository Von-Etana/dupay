class VerifyCableRequest {
  final String planId;
  final String cableId;
  final String decoderNumber;

  VerifyCableRequest({
    required this.planId,
    required this.cableId,
    required this.decoderNumber,
  });

  Map<String, dynamic> toJson() {
    return {
      'plan_id': planId,
      'cable_id': cableId,
      'decoder_number': decoderNumber,
    };
  }
}










