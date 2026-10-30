import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../../core/network/api_service.dart';
import '../../core/exceptions/api_exception.dart';

class ForgotPasswordScreen extends ConsumerStatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  ConsumerState<ForgotPasswordScreen> createState() =>
      _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends ConsumerState<ForgotPasswordScreen> {
  final _emailCtrl = TextEditingController();
  bool _loading = false;
  bool _sent    = false;

  @override
  void dispose() {
    _emailCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final email = _emailCtrl.text.trim();
    if (email.isEmpty) {
      _snack('Please enter your email address', error: true);
      return;
    }

    setState(() => _loading = true);

    try {
      final response = await ApiService.instance.postSimple(
        '/reset-password',
        data: {'email': email},
      );

      if (!mounted) return;
      setState(() => _loading = false);

      if (response.success) {
        setState(() => _sent = true);
        _snack(
          response.message.isNotEmpty
              ? response.message
              : 'A new password has been sent to your email.',
        );
      } else {
        _snack(
          response.message.isNotEmpty
              ? response.message
              : 'Account not found. Please check your email.',
          error: true,
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _loading = false);
        // The network layer throws a ServerException carrying the backend's
        // own message when success == false (HTTP 200 with {success:false}),
        // e.g. "No account is linked to this email address...". Surface that
        // exact message instead of a generic one so the user knows the email
        // wasn't found. Only fall back to a generic string for genuinely
        // unexpected errors that carry no message.
        final msg = (e is ApiException && e.message.trim().isNotEmpty)
            ? e.message
            : 'Something went wrong. Please try again.';
        _snack(msg, error: true);
      }
    }
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: error ? Colors.red : const Color(0xFF34544A),
      behavior: SnackBarBehavior.floating,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        child: Column(children: [
          // Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
            child: Row(children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                onPressed: () => Navigator.of(context).pop(),
              ),
              Expanded(
                child: Text('Forgot Password',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white)),
              ),
              const SizedBox(width: 48),
            ]),
          ),

          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(28),
                      topRight: Radius.circular(28))),
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(28),
                child: _sent ? _successView() : _formView(),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _formView() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 16),

        // Icon
        Center(
          child: Container(
            width: 72, height: 72,
            decoration: BoxDecoration(
              color: const Color(0xFF34544A).withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.lock_reset_rounded,
                size: 36, color: Color(0xFF34544A)),
          ),
        ),
        const SizedBox(height: 24),

        Text('Reset your password',
            style: TextStyle(
                fontSize: 22.sp,
                fontWeight: FontWeight.bold,
                color: Colors.black87)),
        const SizedBox(height: 8),
        Text(
          'Enter the email address linked to your account. '
          'We\'ll send a new password to your inbox.',
          style: TextStyle(fontSize: 14.sp, color: Colors.grey[600],
              height: 1.5),
        ),
        const SizedBox(height: 32),

        // Email field
        Text('Email Address',
            style: TextStyle(
                fontSize: 13.sp,
                fontWeight: FontWeight.w600,
                color: Colors.grey[700])),
        const SizedBox(height: 8),
        TextField(
          controller: _emailCtrl,
          keyboardType: TextInputType.emailAddress,
          textInputAction: TextInputAction.done,
          onSubmitted: (_) => _submit(),
          decoration: InputDecoration(
            hintText: 'yourname@email.com',
            hintStyle: TextStyle(color: Colors.grey[400], fontSize: 14.sp),
            prefixIcon: const Icon(Icons.email_outlined,
                color: Color(0xFF34544A), size: 20),
            filled: true,
            fillColor: const Color(0xFFF8FAF9),
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: BorderSide.none),
            focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(
                    color: Color(0xFF34544A), width: 1.5)),
            contentPadding: const EdgeInsets.symmetric(
                horizontal: 16, vertical: 16),
          ),
        ),
        const SizedBox(height: 32),

        // Submit button
        SizedBox(
          width: double.infinity,
          height: 52,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF34544A),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
            ),
            onPressed: _loading ? null : _submit,
            child: _loading
                ? const SizedBox(
                    width: 22, height: 22,
                    child: CircularProgressIndicator(
                        color: Colors.white, strokeWidth: 2))
                : Text('Send New Password',
                    style: TextStyle(
                        fontSize: 16.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white)),
          ),
        ),
        const SizedBox(height: 20),

        Center(
          child: TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: Text('Back to Login',
                style: TextStyle(
                    color: const Color(0xFF34544A),
                    fontWeight: FontWeight.w600,
                    fontSize: 14.sp)),
          ),
        ),
      ],
    );
  }

  Widget _successView() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        const SizedBox(height: 40),

        Container(
          width: 80, height: 80,
          decoration: const BoxDecoration(
              color: Color(0xFFDDF3E8), shape: BoxShape.circle),
          child: const Icon(Icons.mark_email_read_rounded,
              size: 44, color: Color(0xFF34544A)),
        ),
        const SizedBox(height: 24),

        Text('Check your email',
            style: TextStyle(
                fontSize: 22.sp,
                fontWeight: FontWeight.bold,
                color: Colors.black87)),
        const SizedBox(height: 10),
        Text(
          'A new password has been sent to\n${_emailCtrl.text.trim()}',
          textAlign: TextAlign.center,
          style: TextStyle(
              fontSize: 14.sp, color: Colors.grey[600], height: 1.6),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: const Color(0xFFFFFBEB),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFFFDE68A)),
          ),
          child: Row(children: [
            const Icon(Icons.info_outline_rounded,
                color: Color(0xFFD97706), size: 18),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'Use the new password to log in, then change it in Settings → Security.',
                style: TextStyle(
                    fontSize: 12.sp, color: const Color(0xFF92400E),
                    height: 1.5),
              ),
            ),
          ]),
        ),
        const SizedBox(height: 36),

        SizedBox(
          width: double.infinity,
          height: 52,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF34544A),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
            ),
            onPressed: () => Navigator.of(context).pop(),
            child: Text('Back to Login',
                style: TextStyle(
                    fontSize: 16.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.white)),
          ),
        ),
        const SizedBox(height: 16),
        TextButton(
          onPressed: () => setState(() {
            _sent = false;
            _emailCtrl.clear();
          }),
          child: Text('Try a different email',
              style: TextStyle(
                  color: Colors.grey[500], fontSize: 13.sp)),
        ),
      ],
    );
  }
}