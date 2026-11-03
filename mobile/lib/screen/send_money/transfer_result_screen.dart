import 'package:flutter/material.dart';
import '../transaction/transaction_receipt_screen.dart';

/// Compatibility wrapper so the direct-transfer flow uses the same branded
/// receipt screen as bank and P2P transfers.
class TransferResultScreen extends StatelessWidget {
  final bool success;
  final bool pending;
  final String message;
  final String amount;
  final String? reference;

  const TransferResultScreen({
    super.key,
    required this.success,
    this.pending = false,
    required this.message,
    required this.amount,
    this.reference,
  });

  @override
  Widget build(BuildContext context) {
    final status = pending ? 'pending' : (success ? 'success' : 'failed');
    return TransactionReceiptScreen(
      data: TransactionReceiptData(
        service: 'Transfer',
        type: 'Transfer',
        description: message,
        amount: amount,
        status: status,
        statusMessage: message,
        reference: reference ?? '',
        initialBalance: '',
        finalBalance: '',
        date: DateTime.now(),
      ),
    );
  }
}
