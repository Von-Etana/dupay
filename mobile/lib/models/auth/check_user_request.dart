// lib/models/auth/check_user_request.dart
class CheckUserRequest {
  final String? email;
  final String? phoneNumber;
  final String? username;

  CheckUserRequest({this.email, this.phoneNumber, this.username})
    : assert(
        email != null || phoneNumber != null || username != null,
        'At least one of email, phone number, or username is required',
      );

  Map<String, dynamic> toJson() {
    return {
      if (email != null && email!.isNotEmpty) 'email': email,
      if (phoneNumber != null && phoneNumber!.isNotEmpty)
        'phone_number': phoneNumber,
      if (username != null && username!.isNotEmpty) 'username': username,
    };
  }

  factory CheckUserRequest.fromJson(Map<String, dynamic> json) {
    return CheckUserRequest(
      email: json['email'],
      phoneNumber: json['phone_number'],
      username: json['username'],
    );
  }
}







