class TransferModel {
  final String? walletId;
  final String? receiverId;
  final String amount;
  final String? comment;
  final String? transactionPin;

  TransferModel({
    this.walletId,
    this.receiverId,
    required this.amount,
    this.comment,
    this.transactionPin,
  });

  factory TransferModel.fromJson(Map<String, dynamic> json) {
    return TransferModel(
      walletId: json['wallet_id'],
      receiverId: json['reciever_id'] ?? json['receiver_id'],
      amount: json['amount'] ?? '',
      comment: json['comment'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (walletId != null) 'wallet_id': walletId,
      // Backend has typo: 'reciever_id' (missing 'e')
      if (receiverId != null) 'reciever_id': receiverId,
      'amount': amount,
      if (comment != null) 'comment': comment,
      if (transactionPin != null) 'transaction_pin': transactionPin,
    };
  }
}

class ManualFundingModel {
  final String amount;
  final String? proof;

  ManualFundingModel({required this.amount, this.proof});

  factory ManualFundingModel.fromJson(Map<String, dynamic> json) {
    return ManualFundingModel(
      amount: json['amount'] ?? '',
      proof: json['proof'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'amount': amount, if (proof != null) 'proof': proof};
  }
}

class TransferResponse {
  final bool success;
  final String? message;
  final Map<String, dynamic>? data;

  TransferResponse({required this.success, this.message, this.data});

  factory TransferResponse.fromJson(Map<String, dynamic> json) {
    return TransferResponse(
      success: json['success'] ?? false,
      message: json['message'],
      data: json['data'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'success': success, 'message': message, 'data': data};
  }
}