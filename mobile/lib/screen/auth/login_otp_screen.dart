import 'dart:async';
import 'package:flutter/material.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../../providers/auth_provider.dart';
import '../../repositories/auth_repository.dart';

class LoginOtpScreen extends ConsumerStatefulWidget {
  final String challengeId;
  final AuthRepository? repository;
  const LoginOtpScreen({super.key, required this.challengeId, this.repository});
  @override
  ConsumerState<LoginOtpScreen> createState() => _LoginOtpScreenState();
}

class _LoginOtpScreenState extends ConsumerState<LoginOtpScreen> {
  final _otp = TextEditingController();
  Timer? _timer;
  int _remaining = 60;
  bool _busy = false;
  @override
  void initState() {
    super.initState();
    _send(false);
  }

  @override
  void dispose() {
    _timer?.cancel();
    _otp.dispose();
    super.dispose();
  }

  Future<void> _send(bool resend) async {
    final result = await (widget.repository ?? AuthRepository()).sendLoginOtp(
      widget.challengeId,
      resend: resend,
    );
    if (!mounted) return;
    if (!result.success) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(result.message)));
      return;
    }
    _timer?.cancel();
    setState(() => _remaining = 60);
    _timer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return;
      if (_remaining <= 1) {
        t.cancel();
        setState(() => _remaining = 0);
      } else {
        setState(() => _remaining--);
      }
    });
  }

  Future<void> _verify() async {
    if (_busy || !RegExp(r'^\d{6}$').hasMatch(_otp.text.trim())) {
      if (!_busy)
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Enter the six-digit code.')),
        );
      return;
    }
    setState(() => _busy = true);
    final ok = await ref
        .read(authNotifierProvider.notifier)
        .verifyLoginOtp(widget.challengeId, _otp.text.trim());
    if (!mounted) return;
    setState(() => _busy = false);
    if (ok) {
      Navigator.of(context).pushNamedAndRemoveUntil('/homeNav', (_) => false);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(authErrorProvider) ?? 'Unable to verify code.',
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Verify login')),
    body: Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text('Enter the six-digit code sent to your email.'),
          const SizedBox(height: 24),
          TextField(
            controller: _otp,
            keyboardType: TextInputType.number,
            maxLength: 6,
            autofocus: true,
            decoration: const InputDecoration(labelText: 'Verification code'),
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _busy ? null : _verify,
            child: Text(_busy ? 'Verifying…' : 'Verify'),
          ),
          TextButton(
            onPressed: _remaining == 0 ? () => _send(true) : null,
            child: Text(
              _remaining == 0 ? 'Resend code' : 'Resend in ${_remaining}s',
            ),
          ),
        ],
      ),
    ),
  );
}
