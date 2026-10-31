// lib/services/notifications/notification_service.dart
//
// Owns everything Firebase Cloud Messaging: permission, token retrieval,
// token-refresh, and message handlers. The rest of the app reads the current
// token synchronously via NotificationService.fcmToken (e.g. login/register).

import 'dart:async' show unawaited, Timer;
import 'dart:io' show Platform;

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:logger/logger.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

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
  final FlutterLocalNotificationsPlugin _local = FlutterLocalNotificationsPlugin();

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
  static OverlayEntry? _activeTopBanner;
  static Timer? _bannerDismissTimer;

  Future<void> _initLocalNotifications() async {
    const android = AndroidInitializationSettings('@mipmap/launcher_icon');
    const ios = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    await _local.initialize(
      const InitializationSettings(android: android, iOS: ios),
      onDidReceiveNotificationResponse: (response) {
        final payload = response.payload;
        if (payload != null && payload.isNotEmpty) {
          handleNotificationTap(RemoteMessage(data: {'route': payload}));
        }
      },
    );

    // Register high-priority channels for heads-up top bar notifications
    final androidPlugin = _local
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>();
    if (androidPlugin != null) {
      const channelHigh = AndroidNotificationChannel(
        'high_importance',
        'High importance notifications',
        description: 'DuPay transaction and security alerts',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        showBadge: true,
      );
      const channelDefault = AndroidNotificationChannel(
        'dupay_channel',
        'DuPay General Notifications',
        description: 'DuPay updates and notifications',
        importance: Importance.max,
        playSound: true,
        enableVibration: true,
        showBadge: true,
      );
      await androidPlugin.createNotificationChannel(channelHigh);
      await androidPlugin.createNotificationChannel(channelDefault);
    }
  }

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

  /// Displays an in-app drop-down notification banner in the top bar of the screen.
  static void showInAppTopBanner({
    required String title,
    required String body,
    String? route,
  }) {
    final navState = navigatorKey?.currentState;
    final navContext = navState?.context;
    if (navContext == null) return;

    final overlay = navState?.overlay;
    if (overlay == null) return;

    _bannerDismissTimer?.cancel();
    _activeTopBanner?.remove();
    _activeTopBanner = null;

    late OverlayEntry entry;
    entry = OverlayEntry(
      builder: (context) {
        final topPadding = MediaQuery.of(context).padding.top;
        return _InAppTopBannerWidget(
          topPadding: topPadding,
          title: title,
          body: body,
          onTap: () {
            _bannerDismissTimer?.cancel();
            entry.remove();
            _activeTopBanner = null;
            if (route != null && route.isNotEmpty) {
              navigatorKey?.currentState?.pushNamed(route);
            }
          },
          onDismiss: () {
            _bannerDismissTimer?.cancel();
            entry.remove();
            _activeTopBanner = null;
          },
        );
      },
    );

    _activeTopBanner = entry;
    overlay.insert(entry);

    _bannerDismissTimer = Timer(const Duration(seconds: 4), () {
      _activeTopBanner?.remove();
      _activeTopBanner = null;
    });
  }

  /// Call once in main(), AFTER Firebase.initializeApp().
  Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;

    try {
      await _initLocalNotifications();

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

      // 7. Foreground messages (app in use). Triggers both OS top status bar and in-app banner.
      FirebaseMessaging.onMessage.listen((message) {
        final title = message.notification?.title ?? message.data['title']?.toString() ?? 'DuPay';
        final body = message.notification?.body ?? message.data['body']?.toString() ?? message.data['message']?.toString() ?? '';
        final targetRoute = resolveRoute(message.data);

        if (title.isNotEmpty || body.isNotEmpty) {
          // A. Trigger OS Heads-Up Notification (appears in top mobile status bar)
          unawaited(_local.show(
            message.hashCode,
            title,
            body,
            const NotificationDetails(
              android: AndroidNotificationDetails(
                'high_importance',
                'High importance notifications',
                channelDescription: 'DuPay transaction and security alerts',
                importance: Importance.max,
                priority: Priority.max,
                playSound: true,
                enableVibration: true,
                icon: '@mipmap/launcher_icon',
                channelShowBadge: true,
                visibility: NotificationVisibility.public,
                category: AndroidNotificationCategory.message,
                ticker: 'DuPay Notification',
              ),
              iOS: DarwinNotificationDetails(
                presentAlert: true,
                presentBanner: true,
                presentSound: true,
                presentBadge: true,
                presentList: true,
              ),
            ),
            payload: targetRoute,
          ));

          // B. Show in-app drop-down top bar notification banner
          WidgetsBinding.instance.addPostFrameCallback((_) {
            showInAppTopBanner(
              title: title,
              body: body,
              route: targetRoute,
            );
          });
        }

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
      _logger.w('Failed to get FCM token: $e');
    }
  }

  Future<String?> _waitForApnsToken({Duration timeout = const Duration(seconds: 30)}) async {
    final deadline = DateTime.now().add(timeout);
    while (DateTime.now().isBefore(deadline)) {
      try {
        final token = await _messaging.getAPNSToken();
        if (token != null) return token;
      } catch (_) {
        // Plugin throws if APNs registration is still in flight; swallow and retry.
      }
      await Future.delayed(const Duration(seconds: 1));
    }
    return null;
  }

  /// Fetches the latest token directly from FirebaseMessaging, caches it in
  /// [fcmToken], and returns it.
  Future<String?> refreshToken() async {
    try {
      fcmToken = await _messaging.getToken();
      return fcmToken;
    } catch (e) {
      _logger.w('Failed to refresh FCM token: $e');
      return fcmToken;
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

/// Floating in-app notification banner that drops down from the top bar of the screen.
class _InAppTopBannerWidget extends StatefulWidget {
  final double topPadding;
  final String title;
  final String body;
  final VoidCallback onTap;
  final VoidCallback onDismiss;

  const _InAppTopBannerWidget({
    required this.topPadding,
    required this.title,
    required this.body,
    required this.onTap,
    required this.onDismiss,
  });

  @override
  State<_InAppTopBannerWidget> createState() => _InAppTopBannerWidgetState();
}

class _InAppTopBannerWidgetState extends State<_InAppTopBannerWidget>
    with SingleTickerProviderStateMixin {
  late AnimationController _animController;
  late Animation<Offset> _slideAnimation;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 320),
    );
    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, -1),
      end: Offset.zero,
    ).animate(CurvedAnimation(
      parent: _animController,
      curve: Curves.easeOutCubic,
    ));
    _fadeAnimation = Tween<double>(
      begin: 0.0,
      end: 1.0,
    ).animate(CurvedAnimation(
      parent: _animController,
      curve: Curves.easeIn,
    ));

    _animController.forward();
    try {
      HapticFeedback.lightImpact();
    } catch (_) {}
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  void _dismiss() {
    _animController.reverse().then((_) {
      widget.onDismiss();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Positioned(
      top: widget.topPadding + 8,
      left: 14,
      right: 14,
      child: SlideTransition(
        position: _slideAnimation,
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: GestureDetector(
            onTap: widget.onTap,
            onVerticalDragUpdate: (details) {
              if (details.primaryDelta != null && details.primaryDelta! < -4) {
                _dismiss();
              }
            },
            child: Material(
              color: Colors.transparent,
              elevation: 8,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFF1E3A34), // DuPay brand dark green
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x40000000),
                      blurRadius: 16,
                      offset: Offset(0, 6),
                    ),
                  ],
                  border: Border.all(
                    color: Color(0x597CA183),
                    width: 1,
                  ),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Container(
                      width: 40,
                      height: 40,
                      decoration: BoxDecoration(
                        color: const Color(0xFF34544A),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(
                        Icons.notifications_active_rounded,
                        color: Color(0xFFE2F1E8),
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Text(
                                  widget.title,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 14,
                                    fontWeight: FontWeight.w700,
                                    letterSpacing: 0.2,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              const Text(
                                'now',
                                style: TextStyle(
                                  color: Color(0xFFB5C9BD),
                                  fontSize: 11,
                                ),
                              ),
                            ],
                          ),
                          if (widget.body.isNotEmpty) ...[
                            const SizedBox(height: 3),
                            Text(
                              widget.body,
                              style: const TextStyle(
                                color: Color(0xFFE0EBE4),
                                fontSize: 12.5,
                                height: 1.25,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
