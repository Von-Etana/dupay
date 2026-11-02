import 'dart:async';
import 'package:flutter/material.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:dupay/providers/auth_provider.dart';

class LoginOtpScreen extends ConsumerStatefulWidget {
  const LoginOtpScreen({super.key});
  @override State<LoginOtpScreen> createState() => _LoginOtpScreenState();
}

class _LoginOtpScreenState extends ConsumerState<LoginOtpScreen> {
  final controller = TextEditingController();
  Timer? timer;
  int seconds = 60;
  bool busy = false;
  String? challengeId;

  @override void didChangeDependencies() {
    super.didChangeDependencies();
    challengeId ??= (ModalRoute.of(context)?.settings.arguments as String?);
    timer ??= Timer.periodic(const Duration(seconds: 1), (_) {
      if (seconds > 0) setState(() => seconds--);
    });
  }
  @override void dispose() { timer?.cancel(); controller.dispose(); super.dispose(); }

  Future<void> verify() async {
    if (busy || controller.text.trim().length != 6 || challengeId == null) return;
    setState(() => busy = true);
    final r = await ref.read(authNotifierProvider.notifier).verifyLoginOtp(
      challengeId: challengeId!, otp: controller.text.trim());
    if (!mounted) return;
    if (r.success) { Navigator.of(context).pushNamedAndRemoveUntil('/homeNav', (_) => false); }
    else { setState(() => busy = false); ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(r.message.isEmpty ? 'Unable to verify code' : r.message))); }
  }

  Future<void> resend() async {
    if (seconds > 0 || busy || challengeId == null) return;
    final r = await ref.read(authNotifierProvider.notifier).resendLoginOtp(challengeId!);
    if (!mounted) return;
    setState(() => seconds = 60);
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(r.message.isEmpty ? 'Code sent' : r.message)));
  }

  @override Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Verify your login')),
    body: Padding(padding: const EdgeInsets.all(24), child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      const Text('Enter the 6-digit code sent to your email.'),
      const SizedBox(height: 24),
      TextField(controller: controller, keyboardType: TextInputType.number, maxLength: 6, autofocus: true, textAlign: TextAlign.center, decoration: const InputDecoration(labelText: 'OTP code', border: OutlineInputBorder())),
      const SizedBox(height: 12),
      ElevatedButton(onPressed: busy ? null : verify, child: Text(busy ? 'Verifying…' : 'Verify')),
      TextButton(onPressed: seconds == 0 && !busy ? resend : null, child: Text(seconds == 0 ? 'Resend code' : 'Resend in ${seconds}s')),
    ])),
  );
}
