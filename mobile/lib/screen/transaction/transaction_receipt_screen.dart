// lib/screen/transaction/transaction_receipt_screen.dart
// Replace the existing file with this version.

import 'dart:io';
import 'dart:typed_data';
import '../../core/utils/formatters.dart';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';

/// A single extra label/value row on a receipt, for service-specific details
/// such as a prepaid meter token, units or VAT.
class ReceiptField {
  final String label;
  final String value;
  final bool isBold;

  const ReceiptField(this.label, this.value, {this.isBold = false});
}

/// Data class carrying receipt details shown after a successful transaction.
class TransactionReceiptData {
  final String service;
  final String type;
  final String description;
  final String amount;
  final String status; // success | pending | unsuccessful | refund
  final String reference;
  final String? sessionId;
  final String initialBalance;
  final String finalBalance;
  final DateTime date;
  final String? phoneNumber;

  const TransactionReceiptData({
    required this.service,
    required this.type,
    required this.description,
    required this.amount,
    required this.status,
    required this.reference,
    this.sessionId,
    required this.initialBalance,
    required this.finalBalance,
    required this.date,
    this.phoneNumber,
  });

  /// Convenience: build from a raw API response map.
  factory TransactionReceiptData.fromApiMap(Map<String, dynamic> m, {
    required String service,
    required String amount,
  }) {
    return TransactionReceiptData(
      service: service,
      type: m['service_type'] ?? m['type'] ?? service,
      description: m['description'] ?? '',
      amount: amount,
      status: m['status'] ?? 'success',
      reference: m['reference'] ?? m['paymentReference'] ?? '',
      sessionId: m['session_id']?.toString(),
      initialBalance: m['initial_balance']?.toString() ?? '',
      finalBalance: m['final_balance']?.toString() ?? '',
      date: DateTime.now(),
      phoneNumber: m['phone_number'],
    );
  }
}

class TransactionReceiptScreen extends StatelessWidget {
  final TransactionReceiptData data;

  TransactionReceiptScreen({super.key, required this.data});

  final GlobalKey _receiptKey = GlobalKey();

  /// Leave the receipt. Normally there's a screen beneath (the service form or
  /// transaction history) so we just pop. In the rare case the receipt is the
  /// only route on the stack, popping would do nothing and the button would
  /// look broken — so fall back to sending the user to the home dashboard.
  void _goBack(BuildContext context) {
    final nav = Navigator.of(context);
    if (nav.canPop()) {
      nav.pop();
    } else {
      nav.pushNamedAndRemoveUntil('/homeNav', (route) => false);
    }
  }

  String get _formattedDate {
    final d = data.date;
    final hour = d.hour > 12 ? d.hour - 12 : (d.hour == 0 ? 12 : d.hour);
    final ampm = d.hour >= 12 ? 'PM' : 'AM';
    final min = d.minute.toString().padLeft(2, '0');
    const months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    return '$hour:$min $ampm - ${d.day} ${months[d.month - 1]} ${d.year}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: false,
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
                  onPressed: () => _goBack(context),
                ),
                Expanded(
                  child: Text(
                    'Transaction Receipt',
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
                    child: SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          SizedBox(height: 16.h),
                          RepaintBoundary(
                            key: _receiptKey,
                            child: Container(
                              color: Colors.white,
                              child: Padding(
                                padding: const EdgeInsets.all(16.0),
                                child: Container(
                                  width: double.infinity,
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 18,
                                    horizontal: 12,
                                  ),
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
                                    children: [
                                      Text(
                                        'Transaction Receipt',
                                        style: TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16.sp,
                                          color: Colors.black87,
                                        ),
                                      ),
                                      const SizedBox(height: 10),
                                      CircleAvatar(
                                        backgroundColor: Colors.grey[200],
                                        radius: 32,
                                        child: Padding(
                                          padding: const EdgeInsets.all(6.0),
                                          child: Image.asset(
                                            'assets/images/applogo.jpeg',
                                            fit: BoxFit.contain,
                                            width: 40,
                                            height: 40,
                                          ),
                                        ),
                                      ),
                                      const SizedBox(height: 10),
                                      _DashedDivider(),

                                      // ── 1. Reference & Session ID first ──────
                                      if (data.reference.isNotEmpty) ...[
                                        _ReceiptRow('Reference', data.reference, isBold: true),
                                        _DashedDivider(),
                                      ],
                                      if (data.sessionId != null && data.sessionId!.isNotEmpty) ...[
                                        _ReceiptRow('Session ID', data.sessionId!, isBold: true),
                                        _DashedDivider(),
                                      ],

                                      _ReceiptRow('Service', data.service),
                                      _DashedDivider(),
                                      // _ReceiptRow('Type', data.type),
                                      _DashedDivider(),
                                      if (data.description.isNotEmpty) ...[
                                        _ReceiptRow('Description', data.description),
                                        _DashedDivider(),
                                      ],
                                      if (data.phoneNumber != null &&
                                          data.phoneNumber!.isNotEmpty) ...[
                                        _ReceiptRow('Phone Number', data.phoneNumber!),
                                        _DashedDivider(),
                                      ],
                                      // _ReceiptRow('Amount', data.amount),
                                      _ReceiptRow('Amount', formatCurrency(data.amount)),
                                      _DashedDivider(),

                                      _StatusRow('Status', data.status),
                                      _DashedDivider(),

                                      // ── 2. Date moved to the end ─────────────
                                      _ReceiptRow('Date', _formattedDate, isBold: true),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                          SizedBox(height: 32.h),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 24),
                            child: Column(
                              children: [
                                _ActionButton(
                                  icon: Icons.arrow_back,
                                  label: 'Go Back',
                                  color: const Color(0xFF1DBF73),
                                  onTap: () => _goBack(context),
                                ),
                                _ActionButton(
                                  icon: Icons.share,
                                  label: 'Share Receipt',
                                  color: const Color(0xFF34544A),
                                  onTap: () => _shareReceipt(context),
                                ),
                                _ActionButton(
                                  icon: Icons.report,
                                  label: 'Report Transaction',
                                  color: Colors.red,
                                  // ── 3. Redirect to live support ─────────────
                                  onTap: () {
                                    Navigator.pushNamed(context, '/liveChat');
                                  },
                                ),
                              ],
                            ),
                          ),
                          SizedBox(height: 42.h),
                        ],
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

  /// Crash-proof share-sheet anchor for iOS. Never throws, so it can't
  /// accidentally send us into the clipboard fallback.
  Rect _shareAnchor(BuildContext context) {
    try {
      final box = context.findRenderObject() as RenderBox?;
      if (box != null && box.hasSize) {
        return box.localToGlobal(Offset.zero) & box.size;
      }
    } catch (_) {/* fall through */}
    final size = MediaQuery.maybeOf(context)?.size ?? const Size(400, 800);
    return Rect.fromLTWH(0, 0, size.width, size.height / 2);
  }

  Future<void> _shareReceipt(BuildContext context) async {
    // Ensure the receipt has been laid out & painted before we try to capture.
    await WidgetsBinding.instance.endOfFrame;
    if (!context.mounted) return;

    // Capture the anchor up front, while the context is definitely valid.
    final Rect sharePosition = _shareAnchor(context);

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );

    try {
      final boundaryContext = _receiptKey.currentContext;
      if (boundaryContext == null) {
        throw StateError('Receipt not ready');
      }

      final boundary =
          boundaryContext.findRenderObject() as RenderRepaintBoundary;

      // pixelRatio 2.5 keeps the image crisp on retina displays without
      // ballooning memory for long receipts (3.0 occasionally OOM'd).
      final image = await boundary.toImage(pixelRatio: 2.5);
      final byteData =
          await image.toByteData(format: ui.ImageByteFormat.png);
      if (byteData == null) {
        throw StateError('Failed to encode receipt image');
      }
      final pngBytes = byteData.buffer.asUint8List();

      final directory = await getTemporaryDirectory();
      final file = File(
        '${directory.path}/receipt_${DateTime.now().millisecondsSinceEpoch}.png',
      );
      await file.writeAsBytes(pngBytes);

      // Close the loader BEFORE invoking the share sheet so the share sheet
      // is presented over the receipt, not over the spinner.
      if (context.mounted) Navigator.of(context).pop();

      await Share.shareXFiles(
        [XFile(file.path, mimeType: 'image/png', name: 'receipt.png')],
        text: 'Transaction Receipt - DuPay',
        subject: 'Transaction Receipt',
        sharePositionOrigin: sharePosition,
      );
    } catch (e, st) {
      // Surface the REAL reason the image share failed instead of hiding it.
      debugPrint('Receipt image share failed: $e\n$st');
      if (context.mounted) Navigator.of(context).pop();

      // Fallback 1: share plain text so the user still gets something useful.
      try {
        await Share.share(
          'DuPay Receipt\n'
          'Service: ${data.service}\n'
          'Amount: ${data.amount}\n'
          'Status: ${data.status}\n'
          'Ref: ${data.reference}'
          '${data.sessionId != null && data.sessionId!.isNotEmpty ? '\nSession ID: ${data.sessionId}' : ''}',
          subject: 'Transaction Receipt',
          sharePositionOrigin: sharePosition,
        );
      } catch (e2, st2) {
        debugPrint('Receipt text share failed: $e2\n$st2');
        // Last resort: clipboard.
        await Clipboard.setData(
          ClipboardData(
            text:
                'DuPay Receipt\nService: ${data.service}\nAmount: ${data.amount}\nStatus: ${data.status}\nRef: ${data.reference}',
          ),
        );
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Receipt details copied to clipboard!')),
          );
        }
      }
    }
  }
}

// ─── Widget helpers (unchanged visual style) ──────────────────────────────────

class _DashedDivider extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: LayoutBuilder(
        builder: (context, constraints) => Row(
          children: List.generate(
            (constraints.maxWidth / 8).floor(),
            (i) => Expanded(
              child: Container(
                height: 1,
                color: i % 2 == 0 ? Colors.grey[400] : Colors.transparent,
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ReceiptRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isBold;
  final bool isBlue;
  const _ReceiptRow(this.label, this.value, {this.isBold = false, this.isBlue = false});

  /// Breaks [value] into chunks of at most [maxLen] characters, joined by
  /// newlines, so long unbroken strings (refs, session IDs, etc.) wrap onto
  /// new lines instead of overflowing or shrinking the layout.
  String _wrapValue(String value, int maxLen) {
    if (value.length <= maxLen) return value;
    final lines = <String>[];
    for (int i = 0; i < value.length; i += maxLen) {
      final end = (i + maxLen > value.length) ? value.length : i + maxLen;
      lines.add(value.substring(i, end));
    }
    return lines.join('\n');
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: isBold ? FontWeight.bold : FontWeight.w600,
              color: isBlue ? const Color(0xFF1A237E) : Colors.black87,
              fontSize: 15.sp,
            ),
          ),
          Flexible(
            child: Text(
              _wrapValue(value, 24),
              textAlign: TextAlign.right,
              style: TextStyle(
                fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
                color: isBlue ? const Color(0xFF1A237E) : Colors.black87,
                fontSize: 14.sp,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusRow extends StatelessWidget {
  final String label;
  final String status;
  const _StatusRow(this.label, this.status);

  @override
  Widget build(BuildContext context) {
    Color badgeColor = status == 'refund'
        ? const Color(0xFF2196F3)
        : status == 'success'
            ? const Color(0xFF1DBF73)
            : status == 'unsuccessful' || status == 'failed'
                ? Colors.red
                : Colors.orange;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(fontWeight: FontWeight.w600, color: Colors.black87, fontSize: 15.sp),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: badgeColor.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              status,
              style: TextStyle(
                color: badgeColor,
                fontWeight: FontWeight.bold,
                fontSize: 14.sp,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  final VoidCallback onTap;
  const _ActionButton({
    required this.icon,
    required this.label,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
        margin: const EdgeInsets.only(bottom: 8),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.grey.withOpacity(0.05),
              blurRadius: 2,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Row(
          children: [
            Icon(icon, color: color, size: 24),
            const SizedBox(width: 16),
            Text(
              label,
              style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 16.sp),
            ),
          ],
        ),
      ),
    );
  }
}