// lib/models/auth/login_request.dart
class LoginRequest {
  final String? email;
  final String? phoneNumber;
  final String password;
  final String? fcmToken;

  LoginRequest({
    this.email,
    this.phoneNumber,
    required this.password,
    this.fcmToken,
  }) : assert(email != null || phoneNumber != null,
            'Either email or phone number is required');

  Map<String, dynamic> toJson() {
    return {
      if (email != null && email!.isNotEmpty) 'email': email,
      if (phoneNumber != null && phoneNumber!.isNotEmpty)
        'phone_number': phoneNumber,
      'password': password,
      if (fcmToken != null && fcmToken!.isNotEmpty) 'fcmToken': fcmToken,
    };
  }

  factory LoginRequest.withEmail({
    required String email,
    required String password,
    String? fcmToken,
  }) {
    return LoginRequest(
      email: email,
      password: password,
      fcmToken: fcmToken,
    );
  }

  factory LoginRequest.withPhone({
    required String phoneNumber,
    required String password,
    String? fcmToken,
  }) {
    return LoginRequest(
      phoneNumber: phoneNumber,
      password: password,
      fcmToken: fcmToken,
    );
  }
}
