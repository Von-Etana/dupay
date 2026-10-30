import 'package:flutter/material.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../../providers/auth_provider.dart';
import '../../services/notifications/notification_service.dart';

class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuthStatus();
  }

  Future<void> _checkAuthStatus() async {
    // The native splash is still on screen (preserved in main). We do the auth
    // routing underneath it, then remove it to reveal the destination — so the
    // user only ever sees ONE splash.
    await Future.delayed(const Duration(milliseconds: 400));

    if (!mounted) return;

    // Decide where to go.
    String route;
    final isAuthenticated = ref.read(isAuthenticatedProvider);
    if (isAuthenticated) {
      await ref.read(authNotifierProvider.notifier).getUserInfo();
      final user = ref.read(currentUserProvider);
      final hasTransactionPin = user?.hasTransactionPin ?? false;
      route =
          user?.isEmailVerified == false
              ? '/emailVerification'
              : (hasTransactionPin ? '/homeNav' : '/transactionPinSetup');
    } else {
      route = '/login';
    }

    if (!mounted) return;
    Navigator.of(context).pushReplacementNamed(route);

    // If launched cold from a push notification and authenticated, navigate to destination
    if (isAuthenticated && route == '/homeNav' && NotificationService.pendingInitialMessage != null) {
      final message = NotificationService.pendingInitialMessage!;
      NotificationService.pendingInitialMessage = null;
      WidgetsBinding.instance.addPostFrameCallback((_) {
        NotificationService.handleNotificationTap(message);
      });
    } else {
      NotificationService.pendingInitialMessage = null;
    }

    // Reveal the destination once it has painted its first frame, so there's no
    // flash between removing the splash and the screen appearing.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      FlutterNativeSplash.remove();
    });
  }

  @override
  Widget build(BuildContext context) {
    // Rendered UNDERNEATH the preserved native splash, so it's not actually
    // visible — kept as a plain background matching the splash color for the
    // brief moment before removal.
    return const Scaffold(backgroundColor: Colors.white);
  }
}
