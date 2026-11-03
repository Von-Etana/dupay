// lib/models/auth/login_response.dart
import 'user_model.dart';

class LoginResponse {
  final bool success;
  final UserModel user;
  final String accessToken;
  final String? code;
  final String? challengeId;
  final int? sendCount;
  final int? remainingSends;

  LoginResponse({
    required this.success,
    required this.user,
    required this.accessToken, this.code, this.challengeId,
    this.sendCount, this.remainingSends,
  });

  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      success: json['success'] ?? false,
      user: UserModel.fromJson(json['user'] ?? {}),
      accessToken: json['access_token'] ?? '',
      code: json['code'], challengeId: json['challenge_id'],
      sendCount: int.tryParse('${json['send_count'] ?? ''}'),
      remainingSends: int.tryParse('${json['remaining_sends'] ?? ''}'),
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
