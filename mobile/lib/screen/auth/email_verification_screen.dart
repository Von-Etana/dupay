import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:dupay/providers/auth_provider.dart';
import 'package:dupay/core/network/api_service.dart';
import 'dart:async';

class EmailVerificationScreen extends ConsumerStatefulWidget {
  const EmailVerificationScreen({super.key});

  @override
  ConsumerState<EmailVerificationScreen> createState() =>
      _EmailVerificationScreenState();
}

class _EmailVerificationScreenState
    extends ConsumerState<EmailVerificationScreen> {
  final List<TextEditingController> _otpControllers = List.generate(
    6,
    (index) => TextEditingController(),
  );
  final List<FocusNode> _otpFocusNodes = List.generate(
    6,
    (index) => FocusNode(),
  );

  bool _isProcessing = false;
  bool _canResend = false;
  int _countdown = 25;
  Timer? _timer;
  String _otpCode = '';

  @override
  void initState() {
    super.initState();
    _startCountdown();
  }

  @override
  void dispose() {
    _timer?.cancel();
    for (var controller in _otpControllers) {
      controller.dispose();
    }
    for (var focusNode in _otpFocusNodes) {
      focusNode.dispose();
    }
    super.dispose();
  }

  void _startCountdown() {
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_countdown > 0) {
        setState(() {
          _countdown--;
        });
      } else {
        setState(() {
          _canResend = true;
        });
        timer.cancel();
      }
    });
  }

  void _onOtpChanged(String value, int index) {
    setState(() {
      _otpCode = _otpControllers.map((controller) => controller.text).join();
    });

    if (value.isNotEmpty && index < 5) {
      _otpFocusNodes[index + 1].requestFocus();
    } else if (value.isEmpty && index > 0) {
      _otpFocusNodes[index - 1].requestFocus();
    }

    // Auto submit when all fields are filled
    if (_otpCode.length == 6) {
      _handleSubmit();
    }
  }

  void _handleSubmit() async {
    if (_otpCode.length != 6 || _isProcessing) return;
    final email = ref.read(currentUserProvider)?.email;
    if (email == null || email.isEmpty) {
      _showError('Sign in again to verify your email.');
      return;
    }

    setState(() {
      _isProcessing = true;
    });

    try {
      final success = await ref
          .read(authNotifierProvider.notifier)
          .verifyEmailOtp(email: email, code: _otpCode);
      if (!mounted) return;
      if (success) {
        Navigator.of(context).pushReplacementNamed('/transactionPinSetup');
      } else {
        _showError(ref.read(authErrorProvider) ?? 'Invalid verification code.');
      }
    } catch (_) {
      if (mounted) _showError('Could not verify your email. Try again.');
    } finally {
      if (mounted) setState(() => _isProcessing = false);
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _handleResend() async {
    if (!_canResend) return;
    setState(() => _canResend = false);
    try {
      final response = await ApiService.instance.postSimple(
        '/resend-verify-submit',
      );
      if (!mounted) return;
      if (!response.success) {
        _showError(
          response.message.isNotEmpty
              ? response.message
              : 'Could not resend the code.',
        );
        setState(() => _canResend = true);
        return;
      }
    } catch (_) {
      if (mounted) {
        _showError('Could not resend the code. Check your connection.');
        setState(() => _canResend = true);
      }
      return;
    }
    if (!mounted) return;

    setState(() {
      _canResend = false;
      _countdown = 25;
      // Clear OTP fields
      for (var controller in _otpControllers) {
        controller.clear();
      }
      _otpCode = '';
    });

    _timer?.cancel();
    _startCountdown();

    // Show success message
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('OTP code resent successfully'),
        backgroundColor: Colors.green,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final email = ref.watch(currentUserProvider)?.email;
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.grey),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: Padding(
        padding: EdgeInsets.symmetric(horizontal: 24.w),
        child: Column(
          children: [
            SizedBox(height: 40.h),

            SizedBox(
              height: 150,
              width: 150,
              child: Image.asset('assets/images/applogo.jpeg'),
            ),

            // Title
            Text(
              'Email Verification',
              style: TextStyle(
                fontSize: 28.sp,
                fontWeight: FontWeight.w700,
                color: Colors.grey[800],
              ),
            ),

            SizedBox(height: 12.h),

            // Description
            Column(
              children: [
                Text(
                  'We sent your code to ${email ?? 'your email'}',
                  style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                  textAlign: TextAlign.center,
                ),
                SizedBox(height: 4.h),
                Text(
                  'Kindly Enter the code sent to your mail below ${_countdown > 0 ? '00:${_countdown.toString().padLeft(2, '0')}' : '00:0'}',
                  style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                  textAlign: TextAlign.center,
                ),
              ],
            ),

            SizedBox(height: 50.h),

            // OTP Input Fields
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: List.generate(6, (index) {
                return SizedBox(
                  width: 45.w,
                  height: 45.h,
                  child: TextField(
                    controller: _otpControllers[index],
                    focusNode: _otpFocusNodes[index],
                    textAlign: TextAlign.center,
                    keyboardType: TextInputType.number,
                    maxLength: 1,
                    obscureText: true,
                    onChanged: (value) => _onOtpChanged(value, index),
                    decoration: InputDecoration(
                      counterText: '',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8.r),
                        borderSide: BorderSide(
                          color:
                              _otpControllers[index].text.isNotEmpty
                                  ? Colors.grey[800]!
                                  : Colors.red[300]!,
                          width: 2,
                        ),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8.r),
                        borderSide: BorderSide(
                          color:
                              _otpControllers[index].text.isNotEmpty
                                  ? Colors.grey[800]!
                                  : Colors.red[300]!,
                          width: 2,
                        ),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(8.r),
                        borderSide: BorderSide(
                          color:
                              _otpControllers[index].text.isNotEmpty
                                  ? Colors.grey[800]!
                                  : Colors.red[400]!,
                          width: 2,
                        ),
                      ),
                      filled: true,
                      fillColor:
                          _otpControllers[index].text.isNotEmpty
                              ? Colors.grey[800]
                              : Colors.transparent,
                      contentPadding: EdgeInsets.zero,
                    ),
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.bold,
                      color:
                          _otpControllers[index].text.isNotEmpty
                              ? Colors.white
                              : Colors.grey[800],
                    ),
                  ),
                );
              }),
            ),

            SizedBox(height: 40.h),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 56.h,
              child: ElevatedButton(
                onPressed:
                    _otpCode.length == 6 && !_isProcessing
                        ? _handleSubmit
                        : null,
                style: ElevatedButton.styleFrom(
                  backgroundColor:
                      _otpCode.length == 6
                          ? (_isProcessing
                              ? Colors.grey[700]
                              : Colors.grey[800])
                          : Colors.grey[400],
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12.r),
                  ),
                  elevation: 0,
                ),
                child:
                    _isProcessing
                        ? Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            SizedBox(
                              width: 20.w,
                              height: 20.h,
                              child: const CircularProgressIndicator(
                                strokeWidth: 2,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white,
                                ),
                              ),
                            ),
                            SizedBox(width: 12.w),
                            Text(
                              'Processing...',
                              style: TextStyle(
                                fontSize: 16.sp,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        )
                        : Text(
                          'Submit',
                          style: TextStyle(
                            fontSize: 16.sp,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
              ),
            ),

            SizedBox(height: 30.h),

            // Resend OTP
            GestureDetector(
              onTap: _canResend ? _handleResend : null,
              child: Text(
                'Resend OTP Code',
                style: TextStyle(
                  fontSize: 14.sp,
                  color: _canResend ? Colors.grey[800] : Colors.grey[400],
                  fontWeight: FontWeight.w500,
                  decoration: _canResend ? TextDecoration.underline : null,
                ),
              ),
            ),

            const Spacer(),
          ],
        ),
      ),
    );
  }
}
