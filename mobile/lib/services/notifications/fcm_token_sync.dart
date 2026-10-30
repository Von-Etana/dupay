// lib/services/notifications/fcm_token_sync.dart

import 'package:logger/logger.dart';

import '../../core/constants/api_contants.dart';
import '../../core/network/api_service.dart';
import '../storage/secure_storage_service.dart';
import 'notification_service.dart';

/// Keeps the server's copy of this device's FCM token up to date.
///
/// The token used to reach the backend only inside the login and register
/// payloads. That is too early on iOS: Firebase can't mint an FCM token until
/// APNs returns a device token, which takes a few seconds. A user who taps
/// Login straight away sent `null`, and that device then received no
/// notifications at all until their next sign-in. The same gap silently loses
/// tokens that rotate after a reinstall, a restore, or clearing app data.
///
/// Wiring this to [NotificationService.onTokenChanged] closes both cases: the
/// token is pushed whenever it first arrives and whenever it rotates.
class FcmTokenSync {
  static final Logger _logger = Logger();

  /// Installs the hook. Safe to call once at startup — nothing is sent while
  /// the user is signed out.
  static void install() {
    NotificationService().onTokenChanged = sync;
  }

  /// Sends [token] to the backend if the user is signed in.
  ///
  /// Best-effort: a failure here must never surface to the user or block a
  /// flow. The token is retried on the next refresh or the next sign-in.
  static Future<void> sync(String token) async {
    if (token.isEmpty) return;

    final accessToken = await SecureStorageService.getAccessToken();
    if (accessToken == null || accessToken.isEmpty) {
      // Signed out — login will carry the token in its own payload.
      return;
    }

    try {
      final response = await ApiService.instance.postSimple(
        ApiConstants.updateFcmToken,
        data: {'fcmToken': token},
      );
      if (response.success) {
        _logger.i('🔁 FCM token synced to server');
      } else {
        _logger.w('FCM token sync rejected: ${response.message}');
      }
    } catch (e) {
      _logger.w('FCM token sync failed: $e');
    }
  }

  /// Pushes the token the app already holds. Call right after a successful
  /// login: on iOS the token often lands *after* the login request was built,
  /// so the payload carried null and the server still has nothing.
  static Future<void> syncCurrentToken() async {
    final token = NotificationService.fcmToken ??
        await NotificationService().refreshToken();
    if (token != null && token.isNotEmpty) await sync(token);
  }
}
