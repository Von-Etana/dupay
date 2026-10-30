// lib/models/auth/reset_password_request.dart
class ResetPasswordRequest {
  final String email;

  ResetPasswordRequest({required this.email});

  Map<String, dynamic> toJson() {
    return {'email': email};
  }

  factory ResetPasswordRequest.fromJson(Map<String, dynamic> json) {
    return ResetPasswordRequest(email: json['email'] ?? '');
  }
}
