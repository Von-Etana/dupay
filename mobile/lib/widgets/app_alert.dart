// lib/widgets/app_alert.dart
//
// Centralized, premium Error and Notification feedback system for Dupay.
// Provides elegant top dynamic island banners, actionable modal error dialogs,
// and automatic error message humanization.

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:dupay/main.dart' show notificationMessengerKey, navigatorKey;

class AppAlert {
  static const Color _brandColor = Color(0xFF34544A);
  static const Color _errorColor = Color(0xFFD32F2F);
  static const Color _successColor = Color(0xFF1DBF73);
  static const Color _warningColor = Color(0xFFE65100);

  static OverlayEntry? _currentBanner;
  static Timer? _dismissTimer;

  /// Translates raw technical / backend / Dio error strings into clean,
  /// user-friendly, actionable copy.
  static String humanizeError(dynamic error) {
    if (error == null) return 'An unexpected error occurred. Please try again.';
    final text = error.toString().trim();
    if (text.isEmpty) return 'An unexpected error occurred. Please try again.';

    final lower = text.toLowerCase();

    if (lower.contains('otp_delivery_failed') ||
        lower.contains('unable to deliver login code') ||
        lower.contains('failed to send otp') ||
        lower.contains('email delivery failed')) {
      return 'We could not send your verification code. Check your email address and connection, then try again or contact support.';
    }

    if (lower.contains('otp_expired') || lower.contains('code has expired')) {
      return 'This verification code has expired. Request a new code and try again.';
    }

    if (lower.contains('otp_retry_limit')) {
      return 'You have used all verification attempts. Your account is temporarily locked. Please try again later or contact support.';
    }

    if (lower.contains('otp_resend_cooldown') ||
        lower.contains('resend cooldown')) {
      return 'Please wait before requesting another verification code.';
    }

    if (lower.contains('network error')) {
      return 'We could not connect to Dupay. Check your internet connection and try again. If this continues, contact support.';
    }

    if (lower.contains('socketexception') ||
        lower.contains('connection refused') ||
        lower.contains('failed host lookup') ||
        lower.contains('network is unreachable') ||
        lower.contains('no internet')) {
      return 'No internet connection. Please check your Wi-Fi or mobile data.';
    }

    if (lower.contains('timeout') ||
        lower.contains('timed out') ||
        lower.contains('took too long')) {
      return 'The request timed out. Please check your connection and try again.';
    }

    if (lower.contains('unauthenticated') ||
        lower.contains('unauthorized') ||
        lower.contains('session expired') ||
        lower.contains('token has expired') ||
        lower.contains('401')) {
      return 'Your session has expired. Please sign in again to continue.';
    }

    if (lower.contains('account_locked') ||
        lower.contains('temporarily locked') ||
        lower.contains('account is locked') ||
        lower.contains('account locked') ||
        lower.contains('otp_retry_limit') ||
        lower.contains('six incorrect')) {
      return 'Your account is temporarily locked for 30 minutes due to multiple failed verification attempts. Please check your email or contact support.';
    }

    if (lower.contains('blocked') ||
        lower.contains('restricted') ||
        lower.contains('cannot carry out transactions')) {
      return 'Your account has been restricted. Please contact Dupay Support for assistance.';
    }

    if (lower.contains('invalid login') ||
        lower.contains('invalid credentials') ||
        lower.contains('bad credentials') ||
        lower.contains('incorrect password') ||
        lower.contains('user not found')) {
      return 'Incorrect email or password. Please check your details and try again.';
    }

    if (lower.contains('insufficient') &&
        (lower.contains('fund') || lower.contains('balance'))) {
      return 'Insufficient wallet balance to complete this transaction. Please fund your wallet and try again.';
    }

    if (lower.contains('invalid pin') ||
        lower.contains('incorrect pin') ||
        lower.contains('wrong pin')) {
      return 'Incorrect transaction PIN. Please double-check and try again.';
    }

    if (lower.contains('daily limit') ||
        lower.contains('daily transaction limit') ||
        lower.contains('transfer limit')) {
      return text;
    }

    if (lower.contains('sqlstate') ||
        lower.contains('syntax error') ||
        lower.contains('internal server') ||
        lower.contains('500')) {
      return 'A temporary service issue occurred. Our team is on it — please try again in a moment.';
    }

    var cleaned = text;
    if (cleaned.startsWith('Exception: ')) {
      cleaned = cleaned.substring(11).trim();
    } else if (cleaned.startsWith('Error: ')) {
      cleaned = cleaned.substring(7).trim();
    }

    return cleaned.isNotEmpty
        ? cleaned
        : 'Unable to complete request. Please try again.';
  }

  /// Displays an elegant top error banner.
  static void showError(
    BuildContext context,
    dynamic message, {
    String? title,
    VoidCallback? onRetry,
    Duration duration = const Duration(seconds: 4),
  }) {
    final cleanMessage = humanizeError(message);
    final rawText = message?.toString().toLowerCase() ?? '';
    if (rawText.contains('account_locked') ||
        rawText.contains('temporarily locked') ||
        rawText.contains('account is locked') ||
        rawText.contains('otp_retry_limit') ||
        rawText.contains('six incorrect') ||
        cleanMessage.toLowerCase().contains('temporarily locked')) {
      showAccountLockedDialog(context, message: cleanMessage);
      return;
    }
    _showBanner(
      context,
      message: cleanMessage,
      // The message already explains what happened (for example, "Invalid
      // email or password" or "Too many attempts"). A generic "Error"
      // heading is redundant and makes the iOS banner read awkwardly.
      title: title ?? 'Unable to continue',
      icon: Icons.error_outline_rounded,
      backgroundColor: _errorColor,
      duration: duration,
      onRetry: onRetry,
    );
  }

  /// Displays an elegant top success banner in brand palette.
  static void showSuccess(
    BuildContext context,
    String message, {
    String? title,
    Duration duration = const Duration(seconds: 3),
  }) {
    _showBanner(
      context,
      message: message,
      title: title ?? 'Success',
      icon: Icons.check_circle_outline_rounded,
      backgroundColor: _brandColor,
      accentColor: _successColor,
      duration: duration,
    );
  }

  /// Displays an info/announcement top banner.
  static void showInfo(
    BuildContext context,
    String message, {
    String? title,
    Duration duration = const Duration(seconds: 3),
  }) {
    _showBanner(
      context,
      message: message,
      title: title ?? 'Notice',
      icon: Icons.info_outline_rounded,
      backgroundColor: _brandColor,
      duration: duration,
    );
  }

  /// Displays a warning top banner.
  static void showWarning(
    BuildContext context,
    String message, {
    String? title,
    Duration duration = const Duration(seconds: 4),
  }) {
    _showBanner(
      context,
      message: message,
      title: title ?? 'Warning',
      icon: Icons.warning_amber_rounded,
      backgroundColor: _warningColor,
      duration: duration,
    );
  }

  /// Displays a top banner using Overlay first (sliding down from top of screen),
  /// falling back to ScaffoldMessenger floating SnackBar if Overlay is unavailable.
  static void _showBanner(
    BuildContext context, {
    required String message,
    required String title,
    required IconData icon,
    required Color backgroundColor,
    Color? accentColor,
    Duration duration = const Duration(seconds: 4),
    VoidCallback? onRetry,
  }) {
    try {
      HapticFeedback.mediumImpact();
    } catch (_) {}

    final effectiveContext =
        (context.mounted ? context : null) ??
        navigatorKey.currentContext ??
        context;

    // 1. Try Overlay-based Top Banner (Dynamic Island style, immune to keyboards)
    try {
      final overlay =
          Overlay.maybeOf(effectiveContext, rootOverlay: true) ??
          navigatorKey.currentState?.overlay;

      if (overlay != null) {
        _dismissTimer?.cancel();
        if (_currentBanner != null) {
          try {
            if (_currentBanner!.mounted) {
              _currentBanner!.remove();
            }
          } catch (_) {}
          _currentBanner = null;
        }

        late OverlayEntry entry;
        entry = OverlayEntry(
          builder:
              (ctx) => _AnimatedTopBanner(
                message: message,
                title: title,
                icon: icon,
                backgroundColor: backgroundColor,
                accentColor: accentColor,
                duration: duration,
                onRetry: onRetry,
                onDismiss: () {
                  try {
                    if (entry.mounted) {
                      entry.remove();
                    }
                  } catch (_) {}
                  if (_currentBanner == entry) _currentBanner = null;
                },
              ),
        );

        _currentBanner = entry;
        overlay.insert(entry);
        return;
      }
    } catch (e) {
      debugPrint('AppAlert Overlay failed, using ScaffoldMessenger: ');
    }

    // 2. Fallback to ScaffoldMessenger
    final messenger =
        ScaffoldMessenger.maybeOf(effectiveContext) ??
        notificationMessengerKey.currentState;
    if (messenger != null) {
      try {
        messenger.hideCurrentSnackBar();
        messenger.showSnackBar(
          SnackBar(
            behavior: SnackBarBehavior.floating,
            elevation: 0,
            backgroundColor: Colors.transparent,
            duration: duration,
            margin: EdgeInsets.only(bottom: 24.h, left: 16.w, right: 16.w),
            padding: EdgeInsets.zero,
            content: Container(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
              decoration: BoxDecoration(
                color: backgroundColor,
                borderRadius: BorderRadius.circular(16.r),
                boxShadow: const [
                  BoxShadow(
                    color: Color(0x40000000),
                    blurRadius: 16,
                    offset: Offset(0, 6),
                  ),
                ],
                border: Border.all(
                  color: (accentColor ?? Colors.white).withValues(alpha: 0.25),
                  width: 1,
                ),
              ),
              child: Row(
                children: [
                  Container(
                    padding: EdgeInsets.all(7.w),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.18),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(icon, color: Colors.white, size: 20.sp),
                  ),
                  SizedBox(width: 12.w),
                  Expanded(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        if (title.isNotEmpty) ...[
                          Text(
                            title,
                            style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 14.sp,
                              letterSpacing: 0.2,
                            ),
                          ),
                          SizedBox(height: 3.h),
                        ],
                        Text(
                          message,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.95),
                            fontSize: 13.sp,
                            height: 1.3,
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (onRetry != null) ...[
                    SizedBox(width: 8.w),
                    TextButton(
                      onPressed: () {
                        messenger.hideCurrentSnackBar();
                        onRetry();
                      },
                      style: TextButton.styleFrom(
                        foregroundColor: Colors.white,
                        backgroundColor: Colors.white.withValues(alpha: 0.2),
                        padding: EdgeInsets.symmetric(
                          horizontal: 10.w,
                          vertical: 4.h,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8.r),
                        ),
                      ),
                      child: Text(
                        'Retry',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 12.sp,
                        ),
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        );
      } catch (e) {
        debugPrint('AppAlert ScaffoldMessenger error: ');
      }
    }
  }

  /// Displays a dedicated on-screen security notification modal and top banner
  /// when an account is locked due to security rules (failed OTP, password attempts, etc.).
  static Future<void> showAccountLockedDialog(
    BuildContext context, {
    String? message,
    VoidCallback? onDismiss,
  }) async {
    final effectiveContext =
        (context.mounted ? context : null) ??
        navigatorKey.currentContext ??
        context;

    try {
      HapticFeedback.heavyImpact();
    } catch (_) {}

    final resolvedMessage =
        message != null && message.trim().isNotEmpty
            ? humanizeError(message)
            : 'Your Dupay account is temporarily locked for 30 minutes due to multiple failed verification attempts. Please check your registered email for unlock guidance, or reach out to customer support.';

    // Show top banner simultaneously so user has continuous visibility
    _showBanner(
      effectiveContext,
      message: resolvedMessage,
      title: 'Security Alert: Account Locked',
      icon: Icons.lock_person_rounded,
      backgroundColor: _errorColor,
      duration: const Duration(seconds: 6),
    );

    return showDialog<void>(
      context: effectiveContext,
      barrierDismissible: false,
      builder:
          (ctx) => PopScope(
            canPop: true,
            child: Dialog(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(22.r),
              ),
              elevation: 8,
              backgroundColor: Colors.white,
              child: Padding(
                padding: EdgeInsets.fromLTRB(22.w, 24.h, 22.w, 20.h),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 64.w,
                      height: 64.h,
                      decoration: BoxDecoration(
                        color: _errorColor.withValues(alpha: 0.12),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        Icons.lock_person_rounded,
                        color: _errorColor,
                        size: 34.sp,
                      ),
                    ),
                    SizedBox(height: 18.h),
                    Text(
                      'Account Temporarily Locked',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 18.sp,
                        fontWeight: FontWeight.bold,
                        color: const Color(0xFF1A1A1A),
                      ),
                    ),
                    SizedBox(height: 10.h),
                    Container(
                      padding: EdgeInsets.symmetric(
                        horizontal: 12.w,
                        vertical: 8.h,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF3F2),
                        borderRadius: BorderRadius.circular(10.r),
                        border: Border.all(
                          color: _errorColor.withValues(alpha: 0.2),
                        ),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.shield_outlined,
                            color: _errorColor,
                            size: 18.sp,
                          ),
                          SizedBox(width: 8.w),
                          Expanded(
                            child: Text(
                              'Account lock: 30 minutes',
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w600,
                                color: _errorColor,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 12.h),
                    Text(
                      resolvedMessage,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 13.5.sp,
                        color: Colors.grey[700],
                        height: 1.45,
                      ),
                    ),
                    SizedBox(height: 22.h),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        SizedBox(
                          height: 48.h,
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Navigator.of(ctx).pop();
                              onDismiss?.call();
                              Navigator.of(
                                effectiveContext,
                              ).pushNamed('/contactSupport');
                            },
                            icon: const Icon(
                              Icons.support_agent_rounded,
                              size: 18,
                            ),
                            label: const Text('Contact Support'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: _brandColor,
                              foregroundColor: Colors.white,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12.r),
                              ),
                              textStyle: TextStyle(
                                fontSize: 14.5.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 10.h),
                        SizedBox(
                          height: 44.h,
                          child: OutlinedButton(
                            onPressed: () {
                              Navigator.of(ctx).pop();
                              onDismiss?.call();
                            },
                            style: OutlinedButton.styleFrom(
                              side: BorderSide(color: Colors.grey[300]!),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12.r),
                              ),
                            ),
                            child: Text(
                              'I Understand',
                              style: TextStyle(
                                fontSize: 14.sp,
                                color: Colors.grey[700],
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
    );
  }

  /// Displays a structured modal dialog for critical errors or important notices.
  static Future<void> showAlertDialog(
    BuildContext context, {
    required String title,
    required String message,
    String confirmText = 'OK',
    String? cancelText,
    VoidCallback? onConfirm,
    bool isError = true,
  }) async {
    final effectiveContext =
        (context.mounted ? context : null) ??
        navigatorKey.currentContext ??
        context;

    try {
      HapticFeedback.mediumImpact();
    } catch (_) {}

    return showDialog<void>(
      context: effectiveContext,
      builder:
          (ctx) => Dialog(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(20.r),
            ),
            elevation: 0,
            backgroundColor: Colors.white,
            child: Padding(
              padding: EdgeInsets.fromLTRB(22.w, 24.h, 22.w, 18.h),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 58.w,
                    height: 58.h,
                    decoration: BoxDecoration(
                      color: (isError ? _errorColor : _brandColor).withValues(
                        alpha: 0.10,
                      ),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      isError
                          ? Icons.error_outline_rounded
                          : Icons.info_outline_rounded,
                      color: isError ? _errorColor : _brandColor,
                      size: 30.sp,
                    ),
                  ),
                  SizedBox(height: 16.h),
                  Text(
                    title,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.bold,
                      color: const Color(0xFF1A1A1A),
                    ),
                  ),
                  SizedBox(height: 8.h),
                  Text(
                    humanizeError(message),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 13.5.sp,
                      color: Colors.grey[700],
                      height: 1.45,
                    ),
                  ),
                  SizedBox(height: 22.h),
                  Row(
                    children: [
                      if (cancelText != null) ...[
                        Expanded(
                          child: SizedBox(
                            height: 46.h,
                            child: OutlinedButton(
                              onPressed: () => Navigator.of(ctx).pop(),
                              style: OutlinedButton.styleFrom(
                                side: BorderSide(color: Colors.grey[300]!),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12.r),
                                ),
                              ),
                              child: Text(
                                cancelText,
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  color: Colors.grey[700],
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                        ),
                        SizedBox(width: 12.w),
                      ],
                      Expanded(
                        child: SizedBox(
                          height: 46.h,
                          child: ElevatedButton(
                            onPressed: () {
                              Navigator.of(ctx).pop();
                              onConfirm?.call();
                            },
                            style: ElevatedButton.styleFrom(
                              backgroundColor:
                                  isError ? _errorColor : _brandColor,
                              foregroundColor: Colors.white,
                              elevation: 0,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(12.r),
                              ),
                            ),
                            child: Text(
                              confirmText,
                              style: TextStyle(
                                fontSize: 14.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
    );
  }
}

/// Animated Top Banner Widget that slides smoothly into view under the status bar,
/// supports swipe-up to dismiss, manual close button, and automatic timeout.
class _AnimatedTopBanner extends StatefulWidget {
  final String message;
  final String title;
  final IconData icon;
  final Color backgroundColor;
  final Color? accentColor;
  final Duration duration;
  final VoidCallback? onRetry;
  final VoidCallback onDismiss;

  const _AnimatedTopBanner({
    required this.message,
    required this.title,
    required this.icon,
    required this.backgroundColor,
    this.accentColor,
    required this.duration,
    this.onRetry,
    required this.onDismiss,
  });

  @override
  State<_AnimatedTopBanner> createState() => _AnimatedTopBannerState();
}

class _AnimatedTopBannerState extends State<_AnimatedTopBanner>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<Offset> _slideAnimation;
  late final Animation<double> _fadeAnimation;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, -1.2),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _controller, curve: Curves.easeOutCubic));

    _fadeAnimation = CurvedAnimation(
      parent: _controller,
      curve: Curves.easeOut,
    );

    _controller.forward();

    _timer = Timer(widget.duration, _dismissWithAnimation);
  }

  void _dismissWithAnimation() {
    _timer?.cancel();
    if (!mounted) {
      widget.onDismiss();
      return;
    }
    _controller.reverse().then((_) {
      if (mounted) widget.onDismiss();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final topPadding = MediaQuery.of(context).padding.top;

    return Positioned(
      top: topPadding + 10.h,
      left: 16.w,
      right: 16.w,
      child: SlideTransition(
        position: _slideAnimation,
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: Material(
            color: Colors.transparent,
            child: Dismissible(
              key: UniqueKey(),
              direction: DismissDirection.up,
              onDismissed: (_) => widget.onDismiss(),
              child: Container(
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 13.h),
                decoration: BoxDecoration(
                  color: widget.backgroundColor,
                  borderRadius: BorderRadius.circular(16.r),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x45000000),
                      blurRadius: 18,
                      offset: Offset(0, 8),
                    ),
                  ],
                  border: Border.all(
                    color: (widget.accentColor ?? Colors.white).withValues(
                      alpha: 0.25,
                    ),
                    width: 1,
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: EdgeInsets.all(7.w),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.18),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        widget.icon,
                        color: Colors.white,
                        size: 20.sp,
                      ),
                    ),
                    SizedBox(width: 12.w),
                    Expanded(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (widget.title.isNotEmpty) ...[
                            Text(
                              widget.title,
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 14.sp,
                                letterSpacing: 0.2,
                              ),
                            ),
                            SizedBox(height: 2.h),
                          ],
                          Text(
                            widget.message,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.95),
                              fontSize: 13.sp,
                              height: 1.3,
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (widget.onRetry != null) ...[
                      SizedBox(width: 8.w),
                      TextButton(
                        onPressed: () {
                          _dismissWithAnimation();
                          widget.onRetry!();
                        },
                        style: TextButton.styleFrom(
                          foregroundColor: Colors.white,
                          backgroundColor: Colors.white.withValues(alpha: 0.2),
                          padding: EdgeInsets.symmetric(
                            horizontal: 10.w,
                            vertical: 4.h,
                          ),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8.r),
                          ),
                        ),
                        child: Text(
                          'Retry',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 12.sp,
                          ),
                        ),
                      ),
                    ] else ...[
                      SizedBox(width: 6.w),
                      GestureDetector(
                        onTap: _dismissWithAnimation,
                        behavior: HitTestBehavior.opaque,
                        child: Padding(
                          padding: EdgeInsets.all(4.w),
                          child: Icon(
                            Icons.close_rounded,
                            color: Colors.white.withValues(alpha: 0.8),
                            size: 18.sp,
                          ),
                        ),
                      ),
                    ],
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
