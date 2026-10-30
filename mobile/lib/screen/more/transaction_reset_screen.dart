// lib/screen/more/transaction_reset_screen.dart
//
// Flow:
//  Step 1 — Enter email  → POST /send-email-otp  {email}
//  Step 2 — Enter OTP    → POST /verify-email-otp {email, code, action:'ResetTransactionPin'}
//  Step 3 — Set new PIN  → POST /transactions/create-pin {pin}   (existing create-pin endpoint)

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../core/network/api_service.dart';
import '../../core/exceptions/api_exception.dart';
import '../../providers/auth_provider.dart';

class TransactionPinResetScreen extends ConsumerStatefulWidget {
  const TransactionPinResetScreen({super.key});

  @override
  ConsumerState<TransactionPinResetScreen> createState() =>
      _TransactionPinResetScreenState();
}

class _TransactionPinResetScreenState
    extends ConsumerState<TransactionPinResetScreen> {
  // ── State ──────────────────────────────────────────────────────────────────
  int _step = 1; // 1 = email, 2 = otp, 3 = new pin
  bool _loading = false;
  String _email = '';

  final _emailCtrl = TextEditingController();
  final _otpCtrl = TextEditingController();
  final _pin1Ctrl = TextEditingController();
  final _pin2Ctrl = TextEditingController();

  @override
  void dispose() {
    _emailCtrl.dispose();
    _otpCtrl.dispose();
    _pin1Ctrl.dispose();
    _pin2Ctrl.dispose();
    super.dispose();
  }

  // ── Step 1: Send OTP ───────────────────────────────────────────────────────
  Future<void> _sendOtp() async {
    if (_loading) return;
    final email = _emailCtrl.text.trim();
    if (!RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$').hasMatch(email)) {
      _snack('Enter a valid email address', error: true);
      return;
    }

    setState(() => _loading = true);
    try {
      final res = await ApiService.instance.postSimple(
        '/send-email-otp',
        data: {'email': email, 'subject': 'Reset Transaction PIN'},
      );
      if (!mounted) return;
      if (res.success) {
        _email = email;
        setState(() => _step = 2);
        _snack(res.message.isNotEmpty ? res.message : 'OTP sent to your email');
      } else {
        _snack(
          res.message.isNotEmpty ? res.message : 'Incorrect email address',
          error: true,
        );
      }
    } on ApiException catch (e) {
      if (mounted) _snack(e.message, error: true);
    } catch (_) {
      if (mounted)
        _snack(
          'Could not send the code. Check your connection and try again.',
          error: true,
        );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ── Step 2: Verify OTP ─────────────────────────────────────────────────────
  Future<void> _verifyOtp() async {
    if (_loading) return;
    final code = _otpCtrl.text.trim();
    if (code.length < 6) {
      _snack('Enter the 6-digit OTP', error: true);
      return;
    }

    setState(() => _loading = true);
    try {
      final res = await ApiService.instance.postSimple(
        '/verify-email-otp',
        data: {
          'email': _email,
          'code': code,
          'action': 'ResetTransactionPin', // clears the old PIN
        },
      );
      if (!mounted) return;
      if (res.success) {
        setState(() => _step = 3);
        _snack('OTP verified! Now set your new PIN');
      } else {
        _snack(
          res.message.isNotEmpty ? res.message : 'Invalid OTP',
          error: true,
        );
      }
    } on ApiException catch (e) {
      if (mounted) _snack(e.message, error: true);
    } catch (_) {
      if (mounted) _snack('Could not verify the code. Try again.', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  // ── Step 3: Set new PIN ────────────────────────────────────────────────────
  Future<void> _setNewPin() async {
    if (_loading) return;
    final pin = _pin1Ctrl.text.trim();
    final pin2 = _pin2Ctrl.text.trim();

    if (pin.length < 4) {
      _snack('PIN must be 4 digits', error: true);
      return;
    }
    if (pin != pin2) {
      _snack('PINs do not match', error: true);
      return;
    }

    setState(() => _loading = true);
    try {
      final res = await ApiService.instance.postSimple(
        '/transactions/create-pin',
        data: {'pin': pin},
      );
      if (!mounted) return;
      if (res.success) {
        await ref.read(authNotifierProvider.notifier).refreshUser();
        _showSuccess();
      } else {
        _snack(
          res.message.isNotEmpty ? res.message : 'Failed to set PIN',
          error: true,
        );
      }
    } catch (_) {
      if (mounted) _snack('Could not set the PIN. Try again.', error: true);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _showSuccess() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder:
          (_) => Dialog(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(20),
            ),
            child: Padding(
              padding: const EdgeInsets.all(28),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 72,
                    height: 72,
                    decoration: const BoxDecoration(
                      color: Color(0xFFDDF3E8),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_rounded,
                      size: 40,
                      color: Color(0xFF34544A),
                    ),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    'PIN Reset Successful',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Your transaction PIN has been updated.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey[600], fontSize: 14),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF34544A),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: () {
                        Navigator.of(context).pop(); // close dialog
                        Navigator.of(context).pop(); // back to settings
                      },
                      child: const Text(
                        'Done',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
    );
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: error ? Colors.red : const Color(0xFF34544A),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  // ── Build ──────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Header
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                    onPressed: () {
                      if (_step > 1) {
                        setState(() {
                          _step--;
                        });
                      } else {
                        Navigator.of(context).pop();
                      }
                    },
                  ),
                  Expanded(
                    child: Text(
                      'Reset Transaction PIN',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 18.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ),

            // Progress indicator
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 28),
              child: Row(
                children: List.generate(3, (i) {
                  final active = i + 1 <= _step;
                  final current = i + 1 == _step;
                  return Expanded(
                    child: Container(
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      height: 4,
                      decoration: BoxDecoration(
                        color:
                            active
                                ? Colors.white
                                : Colors.white.withOpacity(0.25),
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                  );
                }),
              ),
            ),
            const SizedBox(height: 4),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 28),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  for (final label in ['Email', 'Verify OTP', 'New PIN'])
                    Text(
                      label,
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.7),
                        fontSize: 10.sp,
                      ),
                    ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Content
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(28),
                    topRight: Radius.circular(28),
                  ),
                ),
                child: SingleChildScrollView(
                  padding: const EdgeInsets.all(28),
                  child:
                      _step == 1
                          ? _emailStep()
                          : _step == 2
                          ? _otpStep()
                          : _pinStep(),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ── Step 1 UI ──────────────────────────────────────────────────────────────
  Widget _emailStep() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const SizedBox(height: 8),
      Center(
        child: Container(
          width: 68,
          height: 68,
          decoration: BoxDecoration(
            color: const Color(0xFF34544A).withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.email_outlined,
            size: 32,
            color: Color(0xFF34544A),
          ),
        ),
      ),
      const SizedBox(height: 22),
      Text(
        'Enter your email',
        style: TextStyle(
          fontSize: 20.sp,
          fontWeight: FontWeight.bold,
          color: Colors.black87,
        ),
      ),
      const SizedBox(height: 6),
      Text(
        'We\'ll send a verification code to your registered email address.',
        style: TextStyle(fontSize: 13.sp, color: Colors.grey[600], height: 1.5),
      ),
      const SizedBox(height: 28),
      _label('Email Address'),
      const SizedBox(height: 8),
      _inputField(
        controller: _emailCtrl,
        hint: 'yourname@email.com',
        icon: Icons.email_outlined,
        keyboardType: TextInputType.emailAddress,
        onSubmit: (_) => _sendOtp(),
      ),
      const SizedBox(height: 32),
      _submitBtn('Send OTP', _sendOtp),
    ],
  );

  // ── Step 2 UI ──────────────────────────────────────────────────────────────
  Widget _otpStep() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const SizedBox(height: 8),
      Center(
        child: Container(
          width: 68,
          height: 68,
          decoration: BoxDecoration(
            color: const Color(0xFF34544A).withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.mark_email_read_outlined,
            size: 32,
            color: Color(0xFF34544A),
          ),
        ),
      ),
      const SizedBox(height: 22),
      Text(
        'Check your email',
        style: TextStyle(
          fontSize: 20.sp,
          fontWeight: FontWeight.bold,
          color: Colors.black87,
        ),
      ),
      const SizedBox(height: 6),
      RichText(
        text: TextSpan(
          style: TextStyle(
            fontSize: 13.sp,
            color: Colors.grey[600],
            height: 1.5,
          ),
          children: [
            const TextSpan(text: 'We sent a 6-digit code to '),
            TextSpan(
              text: _email,
              style: const TextStyle(
                fontWeight: FontWeight.bold,
                color: Color(0xFF34544A),
              ),
            ),
          ],
        ),
      ),
      const SizedBox(height: 28),
      _label('Verification Code'),
      const SizedBox(height: 8),
      _inputField(
        controller: _otpCtrl,
        hint: '000000',
        icon: Icons.lock_outline,
        keyboardType: TextInputType.number,
        maxLength: 6,
        formatters: [FilteringTextInputFormatter.digitsOnly],
        onSubmit: (_) => _verifyOtp(),
      ),
      const SizedBox(height: 12),
      GestureDetector(
        onTap: _loading ? null : _sendOtp,
        child: Text(
          'Resend code',
          style: TextStyle(
            color: const Color(0xFF34544A),
            fontSize: 13.sp,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
      const SizedBox(height: 32),
      _submitBtn('Verify OTP', _verifyOtp),
    ],
  );

  // ── Step 3 UI ──────────────────────────────────────────────────────────────
  Widget _pinStep() => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      const SizedBox(height: 8),
      Center(
        child: Container(
          width: 68,
          height: 68,
          decoration: BoxDecoration(
            color: const Color(0xFF34544A).withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.pin_outlined,
            size: 32,
            color: Color(0xFF34544A),
          ),
        ),
      ),
      const SizedBox(height: 22),
      Text(
        'Set new PIN',
        style: TextStyle(
          fontSize: 20.sp,
          fontWeight: FontWeight.bold,
          color: Colors.black87,
        ),
      ),
      const SizedBox(height: 6),
      Text(
        'Choose a new 4-digit transaction PIN.',
        style: TextStyle(fontSize: 13.sp, color: Colors.grey[600], height: 1.5),
      ),
      const SizedBox(height: 28),
      _label('New PIN'),
      const SizedBox(height: 8),
      _inputField(
        controller: _pin1Ctrl,
        hint: '• • • •',
        icon: Icons.lock_outline,
        keyboardType: TextInputType.number,
        maxLength: 4,
        obscure: true,
        formatters: [FilteringTextInputFormatter.digitsOnly],
      ),
      const SizedBox(height: 16),
      _label('Confirm PIN'),
      const SizedBox(height: 8),
      _inputField(
        controller: _pin2Ctrl,
        hint: '• • • •',
        icon: Icons.lock_outline,
        keyboardType: TextInputType.number,
        maxLength: 4,
        obscure: true,
        formatters: [FilteringTextInputFormatter.digitsOnly],
        onSubmit: (_) => _setNewPin(),
      ),
      const SizedBox(height: 32),
      _submitBtn('Set New PIN', _setNewPin),
    ],
  );

  // ── Reusable Widgets ───────────────────────────────────────────────────────
  Widget _label(String text) => Text(
    text,
    style: TextStyle(
      fontSize: 13.sp,
      fontWeight: FontWeight.w600,
      color: Colors.grey[700],
    ),
  );

  Widget _inputField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType keyboardType = TextInputType.text,
    int? maxLength,
    bool obscure = false,
    List<TextInputFormatter>? formatters,
    ValueChanged<String>? onSubmit,
  }) => TextField(
    controller: controller,
    keyboardType: keyboardType,
    maxLength: maxLength,
    obscureText: obscure,
    inputFormatters: formatters,
    textInputAction: TextInputAction.done,
    onSubmitted: onSubmit,
    decoration: InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: Colors.grey[400], fontSize: 14.sp),
      prefixIcon: Icon(icon, color: const Color(0xFF34544A), size: 20),
      counterText: '',
      filled: true,
      fillColor: const Color(0xFFF8FAF9),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Color(0xFF34544A), width: 1.5),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
    ),
  );

  Widget _submitBtn(String label, VoidCallback onTap) => SizedBox(
    width: double.infinity,
    height: 52,
    child: ElevatedButton(
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF34544A),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(32)),
      ),
      onPressed: _loading ? null : onTap,
      child:
          _loading
              ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2,
                ),
              )
              : Text(
                label,
                style: TextStyle(
                  fontSize: 15.sp,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
    ),
  );
}
