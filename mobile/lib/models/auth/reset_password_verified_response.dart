// lib/models/auth/reset_password_verified_response.dart
import 'user_model.dart';

class ResetPasswordVerifiedResponse {
  final bool success;
  final String message;
  final UserModel user;

  ResetPasswordVerifiedResponse({
    required this.success,
    required this.message,
    required this.user,
  });

  factory ResetPasswordVerifiedResponse.fromJson(Map<String, dynamic> json) {
    return ResetPasswordVerifiedResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      user: UserModel.fromJson(json['user'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {'success': success, 'message': message, 'user': user.toJson()};
  }
}
