// lib/models/auth/register_response.dart
import 'user_model.dart';

class RegisterResponse {
  final bool success;
  final String message;
  final UserModel user;
  final String code;
  final String accessToken;

  RegisterResponse({
    required this.success,
    required this.message,
    required this.user,
    required this.code,
    required this.accessToken,
  });

  factory RegisterResponse.fromJson(Map<String, dynamic> json) {
    return RegisterResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      user: UserModel.fromJson(json['user'] ?? {}),
      code: json['code'] ?? '',
      accessToken: json['access_token'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'message': message,
      'user': user.toJson(),
      'code': code,
      'access_token': accessToken,
    };
  }
}
