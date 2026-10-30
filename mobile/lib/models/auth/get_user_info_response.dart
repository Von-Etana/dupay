// lib/models/auth/get_user_info_response.dart
import 'user_model.dart';
import 'user_stats_model.dart';

class GetUserInfoResponse {
  final bool success;
  final String message;
  final UserStatsModel stats;
  final UserModel user;

  GetUserInfoResponse({
    required this.success,
    required this.message,
    required this.stats,
    required this.user,
  });

  factory GetUserInfoResponse.fromJson(Map<String, dynamic> json) {
    return GetUserInfoResponse(
      success: json['success'] ?? false,
      message: json['message'] ?? '',
      stats: UserStatsModel.fromJson(json['stats'] ?? {}),
      user: UserModel.fromJson(json['user'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'success': success,
      'message': message,
      'stats': stats.toJson(),
      'user': user.toJson(),
    };
  }
}
