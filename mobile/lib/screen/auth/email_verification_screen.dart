// lib/screen/auth/email_verification_screen.dart
//
// Modern, branded Email Verification (OTP) screen for Dupay onboarding.
// Matches Dupay brand palette (0xFF34544A pine green, 0xFF7CA183 sage accent),
// features a 6-digit split-box PIN input with paste/autofill support,
// countdown timer, top alert banner, and clean routing to transaction PIN setup.

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:dupay/providers/auth_provider.dart';
import 'package:dupay/widgets/app_alert.dart';
import 'package:dupay/core/network/api_service.dart';

class EmailVerificationScreen extends ConsumerStatefulWidget {
  const EmailVerificationScreen({super.key, this.email});

  /// Set when verification happens before login has issued a session token.
  final String? email;

  @override
  ConsumerState<EmailVerificationScreen> createState() =>
      _EmailVerificationScreenState();
}

class _EmailVerificationScreenState
    extends ConsumerState<EmailVerificationScreen> {
  // Brand colors
  static const Color _brandColor = Color(0xFF34544A);
  static const Color _brandAccent = Color(0xFF7CA183);
  static const Color _fieldSurface = Color(0xFFF8FAF9);
  static const Color _borderColor = Color(0xFFE2E8F0);

  final TextEditingController _otpController = TextEditingController();
  final FocusNode _focusNode = FocusNode();

  bool _isProcessing = false;
  bool _canResend = false;
  int _countdown = 30;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startCountdown();
    _otpController.addListener(_onOtpChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _focusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _otpController.removeListener(_onOtpChanged);
    _otpController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _startCountdown() {
    _timer?.cancel();
    setState(() {
      _canResend = false;
      _countdown = widget.email == null ? 30 : 60;
    });
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      if (_countdown > 0) {
        setState(() => _countdown--);
      } else {
        setState(() => _canResend = true);
        timer.cancel();
      }
    });
  }

  void _onOtpChanged() {
    setState(() {});
    if (_otpController.text.trim().length == 6 && !_isProcessing) {
      Future.delayed(const Duration(milliseconds: 100), () {
        if (mounted &&
            _otpController.text.trim().length == 6 &&
            !_isProcessing) {
          _handleSubmit();
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

  Future<void> _handleSubmit() async {
    final code = _otpController.text.trim();
    if (code.length != 6 || _isProcessing) return;

    final email = widget.email ?? ref.read(currentUserProvider)?.email;
    if (email == null || email.isEmpty) {
      _showTopMessage('Please sign in again to verify your email address.');
      return;
    }

    setState(() => _isProcessing = true);
    FocusScope.of(context).unfocus();

    try {
      final success = await ref
          .read(authNotifierProvider.notifier)
          .verifyEmailOtp(email: email, code: code);

      if (!mounted) return;

      if (success) {
        _showTopMessage('Email verified successfully!', isError: false);
        await Future.delayed(const Duration(milliseconds: 300));
        if (!mounted) return;
        if (widget.email != null) {
          Navigator.of(context).pushNamedAndRemoveUntil('/login', (_) => false);
          return;
        }
        final user = ref.read(currentUserProvider);
        final route =
            user?.hasTransactionPin == true
                ? '/homeNav'
                : '/transactionPinSetup';
        Navigator.of(context).pushReplacementNamed(route);
      } else {
        setState(() => _isProcessing = false);
        _otpController.clear();
        _focusNode.requestFocus();
        _showTopMessage(
          ref.read(authErrorProvider) ??
              'Invalid verification code. Please check and try again.',
        );
      }
    } catch (_) {
      if (mounted) {
        setState(() => _isProcessing = false);
        _focusNode.requestFocus();
        _showTopMessage(
          'Could not verify your email. Please check your network and try again.',
        );
      }
    }
  }

  Future<void> _handleResend() async {
    if (!_canResend || _isProcessing) return;
    setState(() => _canResend = false);

    try {
      final response =
          widget.email == null
              ? await ApiService.instance.postSimple('/resend-verify-submit')
              : await ApiService.instance.postSimple(
                '/send-email-otp',
                data: {'email': widget.email},
                allowFailureResponse: true,
              );
      if (!mounted) return;

      if (!response.success) {
        _showTopMessage(
          response.message.isNotEmpty
              ? response.message
              : 'Could not resend the code.',
        );
        setState(() => _canResend = true);
        return;
      }

      _startCountdown();
      _otpController.clear();
      _focusNode.requestFocus();

      _showTopMessage('Verification code resent successfully.', isError: false);
    } catch (_) {
      if (mounted) {
        _showTopMessage('Could not resend the code. Check your connection.');
        setState(() => _canResend = true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final email = widget.email ?? ref.watch(currentUserProvider)?.email;
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
                        widget.email == null ? 'Onboarding' : 'Account Security',
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
                    'Email Verification',
                    style: TextStyle(
                      fontSize: 26.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                      letterSpacing: -0.3,
                    ),
                  ),
                  SizedBox(height: 6.h),
                  Text(
                    widget.email == null
                        ? 'Confirm your email to complete your registration and activate your wallet.'
                        : 'Enter the code sent to your email. After verification, sign in again.',
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
                                  Icons.mark_email_read_rounded,
                                  size: 36.sp,
                                  color: _brandColor,
                                ),
                              ),
                              SizedBox(height: 16.h),

                              Text(
                                'Check Your Inbox',
                                style: TextStyle(
                                  fontSize: 19.sp,
                                  fontWeight: FontWeight.w700,
                                  color: const Color(0xFF1A1A1A),
                                ),
                              ),
                              SizedBox(height: 8.h),

                              Text(
                                'We sent a 6-digit confirmation code to:',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 13.sp,
                                  color: Colors.grey[600],
                                ),
                              ),
                              SizedBox(height: 4.h),

                              Container(
                                padding: EdgeInsets.symmetric(
                                  horizontal: 14.w,
                                  vertical: 6.h,
                                ),
                                decoration: BoxDecoration(
                                  color: _fieldSurface,
                                  borderRadius: BorderRadius.circular(20.r),
                                  border: Border.all(color: _borderColor),
                                ),
                                child: Text(
                                  email ?? 'your email address',
                                  style: TextStyle(
                                    fontSize: 13.5.sp,
                                    fontWeight: FontWeight.w600,
                                    color: _brandColor,
                                  ),
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
                                    !_canResend
                                        ? Icons.access_time_rounded
                                        : Icons.refresh_rounded,
                                    size: 16.sp,
                                    color:
                                        !_canResend
                                            ? Colors.grey[500]
                                            : _brandColor,
                                  ),
                                  SizedBox(width: 6.w),
                                  !_canResend
                                      ? Text(
                                        'Resend code in 00:${_countdown.toString().padLeft(2, '0')}',
                                        style: TextStyle(
                                          fontSize: 13.5.sp,
                                          color: Colors.grey[600],
                                          fontWeight: FontWeight.w500,
                                        ),
                                      )
                                      : InkWell(
                                        onTap: _handleResend,
                                        borderRadius: BorderRadius.circular(
                                          6.r,
                                        ),
                                        child: Padding(
                                          padding: EdgeInsets.symmetric(
                                            horizontal: 4.w,
                                            vertical: 2.h,
                                          ),
                                          child: Text(
                                            'Resend OTP Code',
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

                              SizedBox(height: 36.h),

                              // Submit Button
                              SizedBox(
                                width: double.infinity,
                                height: 52.h,
                                child: ElevatedButton(
                                  onPressed:
                                      (_isProcessing || currentText.length != 6)
                                          ? null
                                          : _handleSubmit,
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
                                      _isProcessing
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
                                            'Verify & Activate',
                                            style: TextStyle(
                                              fontSize: 16.sp,
                                              fontWeight: FontWeight.w600,
                                              letterSpacing: 0.3,
                                            ),
                                          ),
                                ),
                              ),

                              SizedBox(height: 20.h),

                              // Support Notice
                              Text(
                                'Didn\'t receive the code? Check your spam folder or wait for the timer to request a new code.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 12.sp,
                                  color: Colors.grey[500],
                                  height: 1.4,
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
