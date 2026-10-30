// lib/core/interceptors/auth_interceptor.dart
import 'package:dio/dio.dart';
import '../constants/api_contants.dart';
import '../../services/storage/secure_storage_service.dart';
import '../../services/cache/cache_service.dart';
import '../../services/auth/session_events.dart';

class AuthInterceptor extends Interceptor {
  @override
  void onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    // Skip auth for login and register endpoints
    if (options.path.contains('/login') ||
        options.path.contains('/register') ||
        options.path.contains('/reset-password') ||
        options.path.contains('/send-email-otp') ||
        options.path.contains('/verify-email-otp') ||
        options.path.contains('/check-user')) {
      return handler.next(options);
    }

    // Add auth token if available
    final token = await SecureStorageService.getAccessToken();
    if (token != null && token.isNotEmpty) {
      options.headers[ApiConstants.authHeader] =
          '${ApiConstants.bearerPrefix} $token';
    }

    return handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    // Handle 401 errors by clearing tokens and redirecting to login
    if (err.response?.statusCode == 401 &&
        err.requestOptions.headers.containsKey(ApiConstants.authHeader)) {
      try {
        await SecureStorageService.clearAll();
      } catch (_) {
        // Keep the original HTTP error moving through Dio.
      } finally {
        await CacheService().clearAllCache();
        SessionEvents.notifyExpired();
      }
    }

    return handler.next(err);
  }

  // TODO: Implement token refresh logic
  // Future<void> _refreshToken() async {
  //   final refreshToken = await SecureStorageService.getRefreshToken();
  //   if (refreshToken != null) {
  //     // Call refresh token endpoint
  //     // Update stored tokens
  //   }
  // }
}
