// lib/screen/auth/login_otp_screen.dart
//
// Modern, branded Two-Factor Authentication (OTP) screen for Dupay.
// Matches Dupay brand palette (0xFF34544A pine green, 0xFF7CA183 sage accent),
// features a 6-digit split-box PIN input with paste/autofill support,
// resend countdown, top alert banner, and clean route handling.

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:dupay/providers/auth_provider.dart';
import 'package:dupay/widgets/app_alert.dart';

class LoginOtpScreen extends ConsumerStatefulWidget {
  const LoginOtpScreen({super.key});

  @override
  ConsumerState<LoginOtpScreen> createState() => _LoginOtpScreenState();
}

class _LoginOtpScreenState extends ConsumerState<LoginOtpScreen> {
  // Brand colors
  static const Color _brandColor = Color(0xFF34544A);
  static const Color _brandAccent = Color(0xFF7CA183);
  static const Color _fieldSurface = Color(0xFFF8FAF9);
  static const Color _borderColor = Color(0xFFE2E8F0);

  final TextEditingController _otpController = TextEditingController();
  final FocusNode _focusNode = FocusNode();

  Timer? _countdownTimer;
  int _secondsRemaining = 60;
  bool _isVerifying = false;
  bool _isResending = false;
  String? _challengeId;
  int? _remainingSends;

  @override
  void initState() {
    super.initState();
    _startTimer();
    _otpController.addListener(_onOtpInputChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _focusNode.requestFocus();
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _challengeId ??= ModalRoute.of(context)?.settings.arguments as String?;
    final authNotifier = ref.read(authNotifierProvider.notifier);
    _challengeId ??= authNotifier.pendingChallengeId;
    _remainingSends ??= authNotifier.pendingOtpRemainingSends;
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    _otpController.removeListener(_onOtpInputChanged);
    _otpController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _startTimer() {
    _countdownTimer?.cancel();
    setState(() => _secondsRemaining = 60);
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_secondsRemaining > 0) {
        setState(() => _secondsRemaining--);
      } else {
        timer.cancel();
      }
    });
  }

  void _onOtpInputChanged() {
    setState(() {});
    if (_otpController.text.trim().length == 6 && !_isVerifying) {
      Future.delayed(const Duration(milliseconds: 100), () {
        if (mounted &&
            _otpController.text.trim().length == 6 &&
            !_isVerifying) {
          _verifyOtp();
        }
      });
    }
  }

  void _showTopMessage(String message, {bool isError = true}) {
    if (!mounted) return;
    if (isError) {
      AppAlert.showError(context, message);
    } else {
      AppAlert.showSuccess(context, message);
    }
  }

  Future<void> _verifyOtp() async {
    final code = _otpController.text.trim();
    if (_isVerifying || code.length != 6) return;

    if (_challengeId == null || _challengeId!.isEmpty) {
      _showTopMessage('Verification session expired. Please sign in again.');
      return;
    }

    setState(() => _isVerifying = true);
    FocusScope.of(context).unfocus();

    try {
      final response = await ref
          .read(authNotifierProvider.notifier)
          .verifyLoginOtp(challengeId: _challengeId!, otp: code);

      if (!mounted) return;

      if (response.success) {
        _showTopMessage('Verification successful!', isError: false);
        await Future.delayed(const Duration(milliseconds: 300));
        if (!mounted) return;

        final user = ref.read(currentUserProvider);
        final route =
            user?.isEmailVerified == false
                ? '/emailVerification'
                : (user?.hasTransactionPin == true
                    ? '/homeNav'
                    : '/transactionPinSetup');

        Navigator.of(context).pushNamedAndRemoveUntil(route, (_) => false);
      } else {
        setState(() => _isVerifying = false);
        _otpController.clear();

        final errCode = response.data?.code;
        final message =
            errCode == 'otp_expired'
                ? 'This code has expired. Please request a new one.'
                : (errCode == 'otp_retry_limit' || errCode == 'account_locked')
                ? 'Too many incorrect attempts. Your account is temporarily locked.'
                : (response.message.isEmpty
                    ? 'Incorrect verification code. Please check and try again.'
                    : response.message);

        if (errCode == 'otp_retry_limit' ||
            errCode == 'account_locked' ||
            message.toLowerCase().contains('locked')) {
          AppAlert.showAccountLockedDialog(context, message: message);
        } else {
          _focusNode.requestFocus();
          _showTopMessage(message, isError: true);
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isVerifying = false);
        _otpController.clear();
        final rawMsg = e.toString();
        if (rawMsg.toLowerCase().contains('locked') ||
            rawMsg.toLowerCase().contains('blocked')) {
          AppAlert.showAccountLockedDialog(context, message: rawMsg);
        } else {
          _focusNode.requestFocus();
          _showTopMessage(AppAlert.humanizeError(e), isError: true);
        }
      }
    }
  }

  Future<void> _resendOtp() async {
    if (_secondsRemaining > 0 || _isResending) return;

    if (_challengeId == null || _challengeId!.isEmpty) {
      _showTopMessage(
        'Session expired. Please sign in again to receive a code.',
      );
      return;
    }

    setState(() => _isResending = true);

    try {
      final response = await ref
          .read(authNotifierProvider.notifier)
          .resendLoginOtp(_challengeId!);
      if (!mounted) return;

      if (response.data?.challengeId != null) {
        _challengeId = response.data!.challengeId;
      }
      if (response.data?.remainingSends != null) {
        setState(() => _remainingSends = response.data!.remainingSends);
      }

      _startTimer();
      _otpController.clear();
      _focusNode.requestFocus();

      _showTopMessage(
        response.message.isEmpty
            ? 'A fresh verification code has been sent.'
            : response.message,
        isError: false,
      );
    } catch (error) {
      if (mounted) {
        _showTopMessage(AppAlert.humanizeError(error));
      }
    } finally {
      if (mounted) setState(() => _isResending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentText = _otpController.text;

    return Scaffold(
      backgroundColor: _brandColor,
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Top App Bar & Hero Header
            Padding(
              padding: EdgeInsets.symmetric(horizontal: 20.w, vertical: 14.h),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      InkWell(
                        onTap: () => Navigator.of(context).pop(),
                        borderRadius: BorderRadius.circular(10.r),
                        child: Container(
                          padding: EdgeInsets.all(8.w),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(10.r),
                          ),
                          child: Icon(
                            Icons.arrow_back_ios_new_rounded,
                            color: Colors.white,
                            size: 18.sp,
                          ),
                        ),
                      ),
                      const Spacer(),
                      Text(
                        'DuPay Security',
                        style: TextStyle(
                          color: _brandAccent,
                          fontWeight: FontWeight.w600,
                          fontSize: 13.sp,
                          letterSpacing: 0.5,
                        ),
                      ),
                    ],
                  ),
                  SizedBox(height: 18.h),
                  Text(
                    'Security Verification',
                    style: TextStyle(
                      fontSize: 26.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      letterSpacing: -0.3,
                    ),
                  ),
                  SizedBox(height: 6.h),
                  Text(
                    'Enter the 6-digit authentication code sent to your registered email.',
                    style: TextStyle(
                      fontSize: 13.5.sp,
                      color: _brandAccent,
                      height: 1.35,
                    ),
                  ),
                ],
              ),
            ),

            // White curved bottom container
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(30.r),
                    topRight: Radius.circular(30.r),
                  ),
                ),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    return SingleChildScrollView(
                      physics: const BouncingScrollPhysics(),
                      padding: EdgeInsets.symmetric(
                        horizontal: 24.w,
                        vertical: 20.h,
                      ),
                      child: ConstrainedBox(
                        constraints: BoxConstraints(
                          minHeight: constraints.maxHeight - 40.h,
                        ),
                        child: IntrinsicHeight(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              // Badge Icon
                              Container(
                                width: 72.w,
                                height: 72.h,
                                decoration: BoxDecoration(
                                  color: _brandColor.withOpacity(0.08),
                                  shape: BoxShape.circle,
                                ),
                                child: Icon(
                                  Icons.mark_email_read_outlined,
                                  size: 36.sp,
                                  color: _brandColor,
                                ),
                              ),
                              SizedBox(height: 16.h),

                              Text(
                                'Two-Factor Authentication',
                                style: TextStyle(
                                  fontSize: 19.sp,
                                  fontWeight: FontWeight.w700,
                                  color: const Color(0xFF1A1A1A),
                                ),
                              ),
                              SizedBox(height: 6.h),

                              Text(
                                'To protect your account, please enter the security code that was sent to your email address.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 13.sp,
                                  color: Colors.grey[600],
                                  height: 1.4,
                                ),
                              ),

                              SizedBox(height: 32.h),

                              // Hidden input with 6 visual digit cells
                              GestureDetector(
                                onTap: () => _focusNode.requestFocus(),
                                behavior: HitTestBehavior.opaque,
                                child: Stack(
                                  alignment: Alignment.center,
                                  children: [
                                    // Visual Digit Cells
                                    Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: List.generate(6, (index) {
                                        final isFilled =
                                            index < currentText.length;
                                        final isCurrent =
                                            index == currentText.length &&
                                            _focusNode.hasFocus;
                                        final char =
                                            isFilled ? currentText[index] : '';

                                        return AnimatedContainer(
                                          duration: const Duration(
                                            milliseconds: 180,
                                          ),
                                          width: 48.w,
                                          height: 56.h,
                                          decoration: BoxDecoration(
                                            color:
                                                isFilled
                                                    ? _brandColor.withOpacity(
                                                      0.04,
                                                    )
                                                    : _fieldSurface,
                                            borderRadius: BorderRadius.circular(
                                              12.r,
                                            ),
                                            border: Border.all(
                                              color:
                                                  isCurrent
                                                      ? _brandColor
                                                      : (isFilled
                                                          ? _brandColor
                                                              .withOpacity(0.4)
                                                          : _borderColor),
                                              width: isCurrent ? 2.0 : 1.2,
                                            ),
                                            boxShadow:
                                                isCurrent
                                                    ? [
                                                      BoxShadow(
                                                        color: _brandColor
                                                            .withOpacity(0.12),
                                                        blurRadius: 8,
                                                        offset: const Offset(
                                                          0,
                                                          2,
                                                        ),
                                                      ),
                                                    ]
                                                    : null,
                                          ),
                                          alignment: Alignment.center,
                                          child: Text(
                                            char,
                                            style: TextStyle(
                                              fontSize: 22.sp,
                                              fontWeight: FontWeight.bold,
                                              color: _brandColor,
                                            ),
                                          ),
                                        );
                                      }),
                                    ),

                                    // Invisible text field to intercept keyboard & paste
                                    Opacity(
                                      opacity: 0.0,
                                      child: TextField(
                                        controller: _otpController,
                                        focusNode: _focusNode,
                                        keyboardType: TextInputType.number,
                                        textInputAction: TextInputAction.done,
                                        inputFormatters: [
                                          FilteringTextInputFormatter
                                              .digitsOnly,
                                          LengthLimitingTextInputFormatter(6),
                                        ],
                                        enableSuggestions: false,
                                        autocorrect: false,
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                              SizedBox(height: 24.h),

                              // Resend Code Row
                              Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    _secondsRemaining > 0
                                        ? Icons.access_time_rounded
                                        : Icons.refresh_rounded,
                                    size: 16.sp,
                                    color:
                                        _secondsRemaining > 0
                                            ? Colors.grey[500]
                                            : _brandColor,
                                  ),
                                  SizedBox(width: 6.w),
                                  _secondsRemaining > 0
                                      ? Text(
                                        'Resend code in 00:${_secondsRemaining.toString().padLeft(2, '0')}',
                                        style: TextStyle(
                                          fontSize: 13.5.sp,
                                          color: Colors.grey[600],
                                          fontWeight: FontWeight.w500,
                                        ),
                                      )
                                      : InkWell(
                                        onTap: _isResending ? null : _resendOtp,
                                        borderRadius: BorderRadius.circular(
                                          6.r,
                                        ),
                                        child: Padding(
                                          padding: EdgeInsets.symmetric(
                                            horizontal: 4.w,
                                            vertical: 2.h,
                                          ),
                                          child: Text(
                                            _isResending
                                                ? 'Sending...'
                                                : 'Resend Code',
                                            style: TextStyle(
                                              fontSize: 13.5.sp,
                                              color: _brandColor,
                                              fontWeight: FontWeight.w700,
                                              decoration:
                                                  TextDecoration.underline,
                                            ),
                                          ),
                                        ),
                                      ),
                                ],
                              ),

                              if (_remainingSends != null) ...[
                                SizedBox(height: 8.h),
                                Text(
                                  '${_remainingSends!} more ${_remainingSends == 1 ? 'code' : 'codes'} available for this login',
                                  textAlign: TextAlign.center,
                                  style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
                                ),
                              ],

                              SizedBox(height: 36.h),

                              // Verify Button
                              SizedBox(
                                width: double.infinity,
                                height: 52.h,
                                child: ElevatedButton(
                                  onPressed:
                                      (_isVerifying || currentText.length != 6)
                                          ? null
                                          : _verifyOtp,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: _brandColor,
                                    foregroundColor: Colors.white,
                                    disabledBackgroundColor: _brandColor
                                        .withOpacity(0.45),
                                    disabledForegroundColor: Colors.white70,
                                    elevation: 0,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(14.r),
                                    ),
                                  ),
                                  child:
                                      _isVerifying
                                          ? SizedBox(
                                            width: 22.w,
                                            height: 22.h,
                                            child:
                                                const CircularProgressIndicator(
                                                  strokeWidth: 2.5,
                                                  valueColor:
                                                      AlwaysStoppedAnimation<
                                                        Color
                                                      >(Colors.white),
                                                ),
                                          )
                                          : Text(
                                            'Verify & Sign In',
                                            style: TextStyle(
                                              fontSize: 16.sp,
                                              fontWeight: FontWeight.w600,
                                              letterSpacing: 0.3,
                                            ),
                                          ),
                                ),
                              ),

                              SizedBox(height: 20.h),

                              // Contact Support Option
                              InkWell(
                                onTap:
                                    () => Navigator.pushNamed(
                                      context,
                                      '/contactSupport',
                                    ),
                                borderRadius: BorderRadius.circular(8.r),
                                child: Padding(
                                  padding: EdgeInsets.symmetric(
                                    horizontal: 10.w,
                                    vertical: 6.h,
                                  ),
                                  child: Text(
                                    'Having trouble receiving the code? Contact Support',
                                    textAlign: TextAlign.center,
                                    style: TextStyle(
                                      fontSize: 12.5.sp,
                                      color: Colors.grey[600],
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    );
                  },
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
