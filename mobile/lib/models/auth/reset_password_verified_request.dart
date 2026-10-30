class ResetPasswordVerifiedRequest {
  final String email;
  final String code;
  final String password;
  final String passwordConfirmation;

  ResetPasswordVerifiedRequest({
    required this.email,
    required this.code,
    required this.password,
    required this.passwordConfirmation,
  });

  Map<String, dynamic> toJson() {
    return {
      'email': email,
      'code': code,
      'password': password,
      'password_confirmation': passwordConfirmation,
    };
  }

  factory ResetPasswordVerifiedRequest.fromJson(Map<String, dynamic> json) {
    return ResetPasswordVerifiedRequest(
      email: json['email'] ?? '',
      code: json['code'] ?? '',
      password: json['password'] ?? '',
      passwordConfirmation: json['password_confirmation'] ?? '',
    );
  }
}
