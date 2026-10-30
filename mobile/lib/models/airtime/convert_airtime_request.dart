class ConvertAirtimeRequest {
  final String network;
  final String mode;
  final String amount;
  final String airtimeDetails;

  ConvertAirtimeRequest({
    required this.network,
    required this.mode,
    required this.amount,
    required this.airtimeDetails,
  });

  Map<String, dynamic> toJson() {
    return {
      'network': network,
      'mode': mode,
      'amount': amount,
      'airtimeDetails': airtimeDetails,
    };
  }
}











