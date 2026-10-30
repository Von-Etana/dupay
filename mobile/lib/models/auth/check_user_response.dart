// lib/models/auth/check_user_response.dart
import 'user_profile_model.dart';

class CheckUserResponse {
  final UserProfileModel data;
  final String type;

  CheckUserResponse({required this.data, required this.type});

  factory CheckUserResponse.fromJson(Map<String, dynamic> json) {
    return CheckUserResponse(
      data: UserProfileModel.fromJson(json['data'] ?? {}),
      type: json['type'] ?? '',
    );
  }

  Map<String, dynamic> toJson() {
    return {'data': data.toJson(), 'type': type};
  }
}
