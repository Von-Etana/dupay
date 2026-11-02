// lib/models/auth/login_response.dart
import 'user_model.dart';

class LoginResponse {
  final bool success;
  final UserModel? user;
  final String accessToken;
  final String? code;
  final String? challengeId;

  LoginResponse({
    required this.success,
    this.user,
    this.accessToken = '',
    this.code,
    this.challengeId,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      success: json['success'] ?? false,
      user:
          json['user'] is Map<String, dynamic>
              ? UserModel.fromJson(json['user'])
              : null,
      accessToken: json['access_token'] ?? '',
      code: json['code'],
      challengeId: json['challenge_id'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      if (user != null) 'user': user!.toJson(),
      'access_token': accessToken,
      if (code != null) 'code': code,
      if (challengeId != null) 'challenge_id': challengeId,
    };
  }
}
