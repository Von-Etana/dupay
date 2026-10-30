// lib/services/notifications/notification_service.dart
//
// Owns everything Firebase Cloud Messaging: permission, token retrieval,
// token-refresh, and message handlers. The rest of the app reads the current
// token synchronously via NotificationService.fcmToken (e.g. login/register).

import 'dart:async' show unawaited;
import 'dart:io' show Platform;

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:logger/logger.dart';

/// Must be a top-level function (not a class method) and annotated so the
/// Flutter engine can find it when the app is killed/in the background.
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {
    // Firebase already initialized or init not required
  }
}

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final Logger _logger = Logger();
  final FirebaseMessaging _messaging = FirebaseMessaging.instance;

  /// Global navigator key reference for routing upon notification taps.
  static GlobalKey<NavigatorState>? navigatorKey;

  /// Holds cold-start notification message if app was launched via notification.
  static RemoteMessage? pendingInitialMessage;

  /// The latest FCM token. Read this when building a LoginRequest /
  /// RegisterRequest. Null until initialize() has run successfully.
  static String? fcmToken;

  /// Called whenever the token rotates (or is first obtained while the user is
  /// already logged in). Hook this to your authenticated "update fcm token"
  /// endpoint so the server stays current without forcing a re-login.
  Future<void> Function(String token)? onTokenChanged;
  void Function(RemoteMessage message)? onForegroundMessage;

  bool _initialized = false;

  /// Resolves the destination route from notification data payload.
  static String? resolveRoute(Map<String, dynamic> data) {
    if (data.containsKey('route') && data['route'] is String) {
      final r = data['route'] as String;
      return r.startsWith('/') ? r : '/$r';
    }
    if (data.containsKey('screen') && data['screen'] is String) {
      final s = data['screen'] as String;
      return s.startsWith('/') ? s : '/$s';
    }
    // Default fallback to user notifications screen
    return '/notification';
  }

  /// Handles user tapping on a notification banner or notification list item.
  static void handleNotificationTap(RemoteMessage message) {
    final targetRoute = resolveRoute(message.data);
    if (targetRoute == null) return;

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final nav = navigatorKey?.currentState;
      if (nav != null) {
        nav.pushNamed(targetRoute);
      }
    });
  }

  /// Call once in main(), AFTER Firebase.initializeApp().
  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;

    try {
      // 1. Background/terminated handler (must be registered before runApp).
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

      // 2. Ask the user for permission. On Android 13+ this shows the runtime
      //    POST_NOTIFICATIONS prompt; on older Android it's auto-granted.
      final settings = await _messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );
      _logger.i('🔔 Notification permission: ${settings.authorizationStatus}');

      // 3. Show heads-up notifications while the app is in the foreground (iOS).
      await _messaging.setForegroundNotificationPresentationOptions(
        alert: true,
        badge: true,
        sound: true,
      );

      // 4. Check if the app was opened from a terminated state via notification tap.
      try {
        final initialMessage = await _messaging.getInitialMessage();
        if (initialMessage != null) {
          _logger.i('🚀 Cold-start launch via notification: ${initialMessage.messageId}');
          pendingInitialMessage = initialMessage;
        }
      } catch (e) {
        _logger.w('Failed checking initial notification message: $e');
      }

      // 5. Fetch the token in the background. On iOS this has to wait for the
      //    APNs device token to arrive, which can take several seconds.
      unawaited(_bootstrapToken());

      // 6. Keep token fresh. Tokens rotate on reinstall, restore, app data clear, etc.
      _messaging.onTokenRefresh.listen((newToken) async {
        fcmToken = newToken;
        _logger.i('🔁 FCM token refreshed');
        if (onTokenChanged != null) {
          try {
            await onTokenChanged!(newToken);
          } catch (e) {
            _logger.e('Error syncing refreshed token: $e');
          }
        }
      });

      // 7. Foreground messages (app open). Wire to local notifications / UI banner.
      FirebaseMessaging.onMessage.listen((message) {
        onForegroundMessage?.call(message);
      });

      // 8. User tapped a notification that opened the app from background.
      FirebaseMessaging.onMessageOpenedApp.listen((message) {
        _logger.i('📲 Notification tapped from background: ${message.messageId}');
        handleNotificationTap(message);
      });
    } catch (e) {
      _logger.e('❌ NotificationService init failed: $e');
    }
  }

  /// Obtains the initial FCM token, waiting for the APNs device token first on
  /// iOS (FCM throws `apns-token-not-set` if asked too early). Best-effort —
  /// failures are logged, never thrown, so a device without push still runs.
  Future<void> _bootstrapToken() async {
    try {
      if (Platform.isIOS) {
        String? apnsToken;
        for (var attempt = 0; attempt < 3 && apnsToken == null; attempt++) {
          apnsToken = await _waitForApnsToken();
          if (apnsToken == null && attempt < 2) {
            await Future.delayed(const Duration(seconds: 15));
          }
        }
        if (apnsToken == null) {
          _logger.w(
            '⚠️ No APNs token — push will not work on this device. '
            'Check the Push Notifications capability, the provisioning '
            'profile, and that this is a physical device (the simulator '
            'only supports push on iOS 16+ with a paired Mac).',
          );
          return;
        }
        _logger.i('🍎 APNs token acquired');
      }

      fcmToken = await _messaging.getToken();

      // If the user is already signed in when the token finally lands, push it
      // to the server the same way a refreshed token would be.
      final token = fcmToken;
      if (token != null && onTokenChanged != null) {
        await onTokenChanged!(token);
      }
    } catch (e) {
      _logger.e('Error obtaining FCM token: $e');
    }
  }

  /// Polls for the APNs device token, which arrives asynchronously after
  /// `registerForRemoteNotifications()`. Returns null if it never shows up
  /// within [timeout].
  Future<String?> _waitForApnsToken({
    Duration timeout = const Duration(seconds: 10),
  }) async {
    final deadline = DateTime.now().add(timeout);
    while (DateTime.now().isBefore(deadline)) {
      try {
        final token = await _messaging.getAPNSToken();
        if (token != null && token.isNotEmpty) return token;
      } catch (e) {
        _logger.w('APNs token lookup failed: $e');
      }
      await Future.delayed(const Duration(milliseconds: 500));
    }
    return null;
  }

  /// Force-refresh the token (e.g. after login, to be safe).
  Future<String?> refreshToken() async {
    try {
      if (Platform.isIOS) await _waitForApnsToken();
      fcmToken = await _messaging.getToken();
      return fcmToken;
    } catch (e) {
      _logger.e('Error refreshing token: $e');
      return null;
    }
  }

  /// Clears the device token on logout so a logged-out device stops receiving
  /// pushes for the previous user.
  Future<void> deleteToken() async {
    try {
      await _messaging.deleteToken();
      fcmToken = null;
    } catch (e) {
      _logger.e('Error deleting token: $e');
    }
  }
}
