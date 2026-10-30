// lib/models/auth/login_response.dart
import 'user_model.dart';

class LoginResponse {
  final bool success;
  final UserModel user;
  final String accessToken;

  LoginResponse({
    required this.success,
    required this.user,
    required this.accessToken,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      success: json['success'] ?? false,
      user: UserModel.fromJson(json['user'] ?? {}),
      accessToken: json['access_token'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'user': user.toJson(),
      'access_token': accessToken,
    };
  }
}
