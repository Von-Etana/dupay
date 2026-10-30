// lib/models/auth/wallet_verification_model.dart

/// Safely converts any JSON value (bool/int/double/String/null) into a String.
/// The backend sometimes returns ints (e.g. 1, 0) or booleans (e.g. true) for
/// fields that are typed here as String, which causes runtime TypeErrors
/// like: `type 'bool' is not a subtype of type 'String'`.
String _asString(dynamic value, [String fallback = '']) {
  if (value == null) return fallback;
  if (value is String) return value;
  if (value is bool) return value ? '1' : '0';
  return value.toString();
}

int _asInt(dynamic value, [int fallback = 0]) {
  if (value == null) return fallback;
  if (value is int) return value;
  if (value is double) return value.toInt();
  if (value is bool) return value ? 1 : 0;
  if (value is String) return int.tryParse(value) ?? fallback;
  return fallback;
}

class WalletVerificationModel {
  final int id;
  final String method;
  final int userId;
  final String? bvn;
  final String? name;
  final String? firstName;
  final String? lastName;
  final String? otherNames;
  final String? mobileNo;
  final String? dateOfBirth;
  final String? state;
  final String? city;
  final String? address;
  final String? gender;
  final String? zipCode;
  final String? houseNumber;
  final String? issuedCountry;
  final String? issuedDate;
  final String? expirationDate;
  final String? result;
  final String? idImage;
  final String? userPhoto;
  final String? comment;
  final String verificationType;
  final String status;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  WalletVerificationModel({
    required this.id,
    required this.method,
    required this.userId,
    this.bvn,
    this.name,
    this.firstName,
    this.lastName,
    this.otherNames,
    this.mobileNo,
    this.dateOfBirth,
    this.state,
    this.city,
    this.address,
    this.gender,
    this.zipCode,
    this.houseNumber,
    this.issuedCountry,
    this.issuedDate,
    this.expirationDate,
    this.result,
    this.idImage,
    this.userPhoto,
    this.comment,
    required this.verificationType,
    required this.status,
    this.createdAt,
    this.updatedAt,
  });

  factory WalletVerificationModel.fromJson(Map<String, dynamic> json) {
    return WalletVerificationModel(
      id: _asInt(json['id']),
      method: _asString(json['method']),
      userId: _asInt(json['user_id']),
      bvn: json['bvn'] != null ? _asString(json['bvn']) : null,
      name: json['name'] != null ? _asString(json['name']) : null,
      firstName: json['first_name'] != null ? _asString(json['first_name']) : null,
      lastName: json['last_name'] != null ? _asString(json['last_name']) : null,
      otherNames: json['other_names'] != null ? _asString(json['other_names']) : null,
      mobileNo: json['mobile_no'] != null ? _asString(json['mobile_no']) : null,
      dateOfBirth: json['date_of_birth'] != null ? _asString(json['date_of_birth']) : null,
      state: json['state'] != null ? _asString(json['state']) : null,
      city: json['city'] != null ? _asString(json['city']) : null,
      address: json['address'] != null ? _asString(json['address']) : null,
      gender: json['gender'] != null ? _asString(json['gender']) : null,
      zipCode: json['zip_code'] != null ? _asString(json['zip_code']) : null,
      houseNumber: json['house_number'] != null ? _asString(json['house_number']) : null,
      issuedCountry: json['issued_country'] != null ? _asString(json['issued_country']) : null,
      issuedDate: json['issued_date'] != null ? _asString(json['issued_date']) : null,
      expirationDate: json['expiration_date'] != null ? _asString(json['expiration_date']) : null,
      result: json['result'] != null ? _asString(json['result']) : null,
      idImage: json['id_image'] != null ? _asString(json['id_image']) : null,
      userPhoto: json['user_photo'] != null ? _asString(json['user_photo']) : null,
      comment: json['comment'] != null ? _asString(json['comment']) : null,
      verificationType: _asString(json['verification_type'], '0'),
      // Backend may return bool (true/false) here — coerce safely.
      status: _asString(json['status'], '0'),
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
      updatedAt: json['updated_at'] != null
          ? DateTime.tryParse(json['updated_at'].toString())
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'method': method,
      'user_id': userId,
      'bvn': bvn,
      'name': name,
      'first_name': firstName,
      'last_name': lastName,
      'other_names': otherNames,
      'mobile_no': mobileNo,
      'date_of_birth': dateOfBirth,
      'state': state,
      'city': city,
      'address': address,
      'gender': gender,
      'zip_code': zipCode,
      'house_number': houseNumber,
      'issued_country': issuedCountry,
      'issued_date': issuedDate,
      'expiration_date': expirationDate,
      'result': result,
      'id_image': idImage,
      'user_photo': userPhoto,
      'comment': comment,
      'verification_type': verificationType,
      'status': status,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  bool get isVerified => status == '1' || status.toLowerCase() == 'true';
  bool get isPending => status == '0' || status.toLowerCase() == 'false';
  bool get isRejected => status == '2';
}