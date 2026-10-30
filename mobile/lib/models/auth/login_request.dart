// lib/models/auth/login_request.dart
class LoginRequest {
  final String? email;
  final String? phoneNumber;
  final String password;
  final String? fcmToken;
  final String? deviceId;

  LoginRequest({
    this.email,
    this.phoneNumber,
    required this.password,
    this.fcmToken,
    this.deviceId,
  }) : assert(email != null || phoneNumber != null,
            'Either email or phone number is required');

  Map<String, dynamic> toJson() {
    return {
      if (email != null && email!.isNotEmpty) 'email': email,
      if (phoneNumber != null && phoneNumber!.isNotEmpty)
        'phone_number': phoneNumber,
      'password': password,
      if (fcmToken != null && fcmToken!.isNotEmpty) 'fcmToken': fcmToken,
      if (deviceId != null && deviceId!.isNotEmpty) 'device_id': deviceId,
    };
  }

  factory LoginRequest.withEmail({
    required String email,
    required String password,
    String? fcmToken,
    String? deviceId,
  }) {
    return LoginRequest(
      email: email,
      password: password,
      fcmToken: fcmToken,
      deviceId: deviceId,
    );
  }

  factory LoginRequest.withPhone({
    required String phoneNumber,
    required String password,
    String? fcmToken,
    String? deviceId,
  }) {
    return LoginRequest(
      phoneNumber: phoneNumber,
      password: password,
      fcmToken: fcmToken,
      deviceId: deviceId,
    );
  }
}
