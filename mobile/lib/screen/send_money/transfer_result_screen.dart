import 'package:flutter/material.dart';

class TransferResultScreen extends StatelessWidget {
  final bool success;
  final String message;
  final String amount;
  final String? reference;
  const TransferResultScreen({
    super.key,
    required this.success,
    required this.message,
    required this.amount,
    this.reference,
  });

  @override
  Widget build(BuildContext context) {
    final color = success ? const Color(0xFF1DBF73) : const Color(0xFFE53935);
    return Scaffold(
      appBar: AppBar(
        title: const Text('Transfer result'),
        automaticallyImplyLeading: false,
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(28),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircleAvatar(
                radius: 42,
                backgroundColor: color.withOpacity(.12),
                child: Icon(
                  success ? Icons.check_rounded : Icons.close_rounded,
                  size: 52,
                  color: color,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                success ? 'Transfer successful' : 'Transfer failed',
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                message,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.black54, fontSize: 16),
              ),
              const SizedBox(height: 24),
              if (amount.isNotEmpty)
                Text(
                  'Amount: ₦$amount',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              if (reference != null && reference!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  'Reference: $reference',
                  style: const TextStyle(color: Colors.black54),
                ),
              ],
              const SizedBox(height: 36),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed:
                      () => Navigator.of(context).popUntil(
                        (route) =>
                            route.isFirst || route.settings.name == '/homeNav',
                      ),
                  child: Text(success ? 'Back to dashboard' : 'Try again'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
