// lib/screen/transaction/transaction_summary_screen.dart
// Replace the existing file with this version.
// Accepts dynamic transaction details and verifies PIN before confirming.
// Uses the device's native numeric keyboard for PIN entry.

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter/services.dart';
import 'transaction_receipt_screen.dart';
import '../../core/utils/formatters.dart';

/// Data class carrying all details needed for the summary + receipt screens.
class TransactionSummaryData {
  final String service;
  final String from;
  final String? bank;
  final String? accountName;
  final String? accountNumber;
  final String amount;
  final String? description;
  // Callback that performs the actual API call; returns success + message.
  final Future<Map<String, dynamic>> Function(String pin) onConfirm;

  const TransactionSummaryData({
    required this.service,
    required this.from,
    this.bank,
    this.accountName,
    this.accountNumber,
    required this.amount,
    this.description,
    required this.onConfirm,
  });
}

class TransactionSummaryScreen extends ConsumerStatefulWidget {
  final TransactionSummaryData data;
  const TransactionSummaryScreen({super.key, required this.data});

  @override
  ConsumerState<TransactionSummaryScreen> createState() =>
      _TransactionSummaryScreenState();
}

class _TransactionSummaryScreenState
    extends ConsumerState<TransactionSummaryScreen> {
  final TextEditingController _pinController = TextEditingController();
  final FocusNode _focusNode = FocusNode();
  bool _isProcessing = false;

  @override
  void initState() {
    super.initState();
    _pinController.addListener(_onPinChanged);
    // Bring up native keyboard once the screen has settled.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _focusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _pinController.removeListener(_onPinChanged);
    _pinController.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  /// Reliably (re-)opens the native keyboard for the PIN field.
  ///
  /// On Android the keyboard can be dismissed (back button / swipe-down)
  /// while the TextField KEEPS focus. In that state a plain requestFocus()
  /// is a no-op — Flutter sees no focus change — and the platform input
  /// connection has already been torn down, so the keyboard never reappears.
  /// Dropping focus first and re-requesting it a moment later forces Flutter
  /// to rebuild the input connection, which brings the keyboard back.
  void _openKeyboard() {
    if (!mounted || _isProcessing) return;
    _focusNode.unfocus();
    Future.delayed(const Duration(milliseconds: 100), () {
      if (mounted) FocusScope.of(context).requestFocus(_focusNode);
    });
  }

  void _onPinChanged() {
    setState(() {});
    if (_pinController.text.length == 4 && !_isProcessing) {
      // Brief delay so the last dot renders before the loader takes over.
      Future.delayed(const Duration(milliseconds: 120), () {
        if (mounted && _pinController.text.length == 4 && !_isProcessing) {
          _confirmTransaction();
        }
      });
    }
  }

  void _clearPin() {
    _pinController.clear();
  }

  Future<void> _confirmTransaction() async {
    if (_isProcessing) return;
    if (_pinController.text.length != 4) return;

    setState(() => _isProcessing = true);
    // Dismiss keyboard while processing.
    FocusScope.of(context).unfocus();

    final enteredPin = _pinController.text;

    try {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF34544A)),
        ),
      );

      final result = await widget.data.onConfirm(enteredPin);
      if (!mounted) return;
      Navigator.of(context).pop(); // Close loader

      final success = result['success'] == true;
      if (success) {
        // Extract session_id separately so the receipt rendered immediately
        // after a successful transaction shows the same "Session ID" row that
        // the receipt opened from transaction history shows.
        final sessionIdRaw = result['session_id']?.toString();
        final sessionId =
            (sessionIdRaw != null && sessionIdRaw.isNotEmpty) ? sessionIdRaw : null;

        final receiptData = TransactionReceiptData(
          service: widget.data.service,
          type: result['type'] ?? widget.data.service,
          description: result['description'] ?? widget.data.description ?? '',
          amount: widget.data.amount,
          status: 'success',
          reference: result['reference'] ?? sessionIdRaw ?? '',
          sessionId: sessionId,
          initialBalance: result['initial_balance']?.toString() ?? '',
          finalBalance: result['final_balance']?.toString() ?? '',
          date: DateTime.now(),
        );
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(
            builder: (_) => TransactionReceiptScreen(data: receiptData),
          ),
        );
      } else {
        _clearPin();
        setState(() => _isProcessing = false);
        // Re-open keyboard so user can retry.
        _openKeyboard();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              result['message'] ?? 'Transaction failed. Please try again.',
            ),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      Navigator.of(context).pop(); // Close loader if open
      _clearPin();
      setState(() => _isProcessing = false);
      _openKeyboard();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final d = widget.data;
    final pinLength = _pinController.text.length;

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            const SizedBox(height: 30),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'Transaction Summary',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 22.sp,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
            const SizedBox(height: 20),
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(top: 20),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20),
                      ),
                    ),
                    child: GestureDetector(
                      // Tap anywhere on the white sheet to refocus PIN field
                      // (re-opens the native keyboard if it was dismissed).
                      behavior: HitTestBehavior.opaque,
                      onTap: _openKeyboard,
                      child: SingleChildScrollView(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            SizedBox(height: 24.h),
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 24.0,
                              ),
                              child: Align(
                                alignment: Alignment.centerLeft,
                                child: Text(
                                  'Transaction Details',
                                  style: TextStyle(
                                    fontSize: 15.sp,
                                    color: Colors.grey[700],
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ),
                            SizedBox(height: 12.h),
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 16.0,
                              ),
                              child: Container(
                                width: double.infinity,
                                padding: const EdgeInsets.all(18),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(16),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.grey.withOpacity(0.08),
                                      blurRadius: 8,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment:
                                      CrossAxisAlignment.start,
                                  children: [
                                    _DetailRow('Service', d.service),
                                    _DetailRow('From', d.from),
                                    if (d.bank != null)
                                      _DetailRow('Bank', d.bank!),
                                    if (d.accountName != null)
                                      _DetailRow(
                                        'Account Name',
                                        d.accountName!,
                                      ),
                                    if (d.accountNumber != null)
                                      _DetailRow(
                                        'Account Number',
                                        d.accountNumber!,
                                      ),
                                    _DetailRow('Amount', formatCurrency(d.amount)),
                                    if (d.description != null &&
                                        d.description!.isNotEmpty)
                                      _DetailRow(
                                        'Description',
                                        d.description!,
                                      ),
                                  ],
                                ),
                              ),
                            ),
                            SizedBox(height: 24.h),
                            Text(
                              'Enter your 4-digit transaction PIN',
                              style: TextStyle(
                                fontSize: 14.sp,
                                color: Colors.grey[600],
                              ),
                            ),
                            SizedBox(height: 16.h),
                            // PIN dots (tap to refocus is handled by outer
                            // GestureDetector above).
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: List.generate(
                                4,
                                (i) => _PinDot(filled: i < pinLength),
                              ),
                            ),
                            // Hidden TextField — drives the native keyboard.
                            Opacity(
                              opacity: 0,
                              child: SizedBox(
                                height: 1,
                                child: TextField(
                                  controller: _pinController,
                                  focusNode: _focusNode,
                                  autofocus: true,
                                  keyboardType: TextInputType.number,
                                  obscureText: true,
                                  enableInteractiveSelection: false,
                                  showCursor: false,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.digitsOnly,
                                    LengthLimitingTextInputFormatter(4),
                                  ],
                                ),
                              ),
                            ),
                            SizedBox(height: 24.h),
                            Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 24,
                              ),
                              child: SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: pinLength == 4
                                        ? const Color(0xFF34544A)
                                        : Colors.grey[300],
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(
                                        32.r,
                                      ),
                                    ),
                                    padding: EdgeInsets.symmetric(
                                      vertical: 16.h,
                                    ),
                                  ),
                                  onPressed: pinLength == 4 && !_isProcessing
                                      ? _confirmTransaction
                                      : null,
                                  child: Text(
                                    'Confirm Payment',
                                    style: TextStyle(
                                      fontSize: 18.sp,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            SizedBox(height: 32.h),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// ─── Helper Widgets ────────────────────────────────────────────────────────────

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _DetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(fontSize: 15.sp, color: Colors.grey[700]),
          ),
          Flexible(
            child: Text(
              value,
              style: TextStyle(fontSize: 15.sp, fontWeight: FontWeight.w600),
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }
}

class _PinDot extends StatelessWidget {
  final bool filled;
  const _PinDot({required this.filled});

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 150),
      margin: const EdgeInsets.symmetric(horizontal: 8),
      width: 55,
      height: 55,
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey[400]!),
        borderRadius: BorderRadius.circular(8),
        color: filled ? const Color(0xFF34544A).withOpacity(0.1) : null,
      ),
      child: Center(
        child: Icon(
          filled ? Icons.circle : Icons.circle_outlined,
          color: const Color(0xFF34544A),
          size: 16,
        ),
      ),
    );
  }
}