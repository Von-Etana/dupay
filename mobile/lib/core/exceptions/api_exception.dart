// lib/core/exceptions/api_exception.dart
abstract class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  const ApiException({
    required this.message,
    this.statusCode,
    this.errors,
  });

  @override
  String toString() => message;
}

class NetworkException extends ApiException {
  const NetworkException({required String message}) : super(message: message);
}

class AuthenticationException extends ApiException {
  const AuthenticationException({required String message})
      : super(message: message);
}

class AuthorizationException extends ApiException {
  const AuthorizationException({required String message})
      : super(message: message);
}

class ValidationException extends ApiException {
  const ValidationException({
    required String message,
    Map<String, dynamic>? errors,
  }) : super(message: message, errors: errors);
}

class ServerException extends ApiException {
  const ServerException({
    required String message,
    int? statusCode,
    Map<String, dynamic>? errors,
  }) : super(message: message, statusCode: statusCode, errors: errors);
}
