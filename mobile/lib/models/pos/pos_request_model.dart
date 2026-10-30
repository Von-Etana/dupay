class PosRequestModel {
  final int? id;
  final int? userId;
  final String businessAddress;
  final String numberOfTerminal;
  final String transactionVolume;
  final String? fileOne;
  final String? fileTwo;
  final String? comment;
  final String status;
  final String? createdAt;
  final String? updatedAt;

  PosRequestModel({
    this.id,
    this.userId,
    required this.businessAddress,
    required this.numberOfTerminal,
    required this.transactionVolume,
    this.fileOne,
    this.fileTwo,
    this.comment,
    this.status = '0',
    this.createdAt,
    this.updatedAt,
  });

  factory PosRequestModel.fromJson(Map<String, dynamic> json) {
    return PosRequestModel(
      id: json['id'],
      userId: json['user_id'],
      businessAddress: json['business_address'] ?? '',
      numberOfTerminal: json['number_of_terminal'] ?? '',
      transactionVolume: json['transaction_volume'] ?? '',
      fileOne: json['file_one'],
      fileTwo: json['file_two'],
      comment: json['comment'],
      status: json['status'] ?? '0',
      createdAt: json['created_at'],
      updatedAt: json['updated_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'business_address': businessAddress,
      'number_of_terminal': numberOfTerminal,
      'transaction_volume': transactionVolume,
    };
  }
}

class PosRequestResponse {
  final bool success;
  final String? message;
  final Map<String, dynamic>? data;

  PosRequestResponse({required this.success, this.message, this.data});

  factory PosRequestResponse.fromJson(Map<String, dynamic> json) {
    return PosRequestResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'success': success, 'message': message, 'data': data};
  }
}
