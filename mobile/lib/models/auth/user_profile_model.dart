// lib/models/auth/user_profile_model.dart

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

class UserProfileModel {
  final int id;
  final int userId;
  final String username;
  final String? firstName;
  final String? lastName;
  final String refId;
  final String? profileImg;
  final String? profileBg;
  final String? commission;
  final String? strowalletCustomerId;
  final DateTime? createdAt;
  final DateTime? updatedAt;

  UserProfileModel({
    required this.id,
    required this.userId,
    required this.username,
    this.firstName,
    this.lastName,
    required this.refId,
    this.profileImg,
    this.profileBg,
    this.commission,
    this.strowalletCustomerId,
    this.createdAt,
    this.updatedAt,
  });

  factory UserProfileModel.fromJson(Map<String, dynamic> json) {
    return UserProfileModel(
      id: _asInt(json['id']),
      userId: _asInt(json['user_id']),
      username: _asString(json['username']),
      firstName: json['first_name'] != null
          ? _asString(json['first_name'])
          : null,
      lastName: json['last_name'] != null ? _asString(json['last_name']) : null,
      refId: _asString(json['ref_id']),
      profileImg: json['profile_img'] != null
          ? _asString(json['profile_img'])
          : null,
      profileBg: json['profile_bg'] != null
          ? _asString(json['profile_bg'])
          : null,
      // Backend returns numeric (e.g. 298.4) — coerce to String to be safe.
      commission: json['commission'] != null
          ? _asString(json['commission'])
          : null,
      strowalletCustomerId: json['strowallet_customer_id'] != null
          ? _asString(json['strowallet_customer_id'])
          : null,
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
      'user_id': userId,
      'username': username,
      'first_name': firstName,
      'last_name': lastName,
      'ref_id': refId,
      'profile_img': profileImg,
      'profile_bg': profileBg,
      'commission': commission,
      'strowallet_customer_id': strowalletCustomerId,
      'created_at': createdAt?.toIso8601String(),
      'updated_at': updatedAt?.toIso8601String(),
    };
  }

  String get fullName {
    final firstName = this.firstName ?? '';
    final lastName = this.lastName ?? '';
    return '$firstName $lastName'.trim();
  }

  String get displayName {
    return fullName.isNotEmpty ? fullName : username;
  }
}
