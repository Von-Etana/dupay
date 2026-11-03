// lib/screen/transaction/transaction_receipt_screen.dart
//
// Modern, standard Payment Successful & Transaction Receipt screen for Dupay.
// Matches Dupay brand palette (0xFF34544A pine green, 0xFF7CA183 sage accent),
// features a celebratory success hero, high-contrast digital receipt card,
// one-tap copy for reference/tokens, crisp receipt image sharing, and clean
// dashboard return navigation.

import 'dart:io';
import 'dart:ui' as ui;
import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import '../../core/utils/formatters.dart';

/// A single extra label/value row on a receipt, for service-specific details
/// such as a prepaid meter token, units or VAT.
class ReceiptField {
  final String label;
  final String value;
  final bool isBold;

  const ReceiptField(this.label, this.value, {this.isBold = false});
}

/// Data class carrying receipt details shown after a transaction.
class TransactionReceiptData {
  final String service;
  final String type;
  final String description;
  final String amount;
  final String status; // success | pending | unsuccessful | failed | refund
  final String? statusMessage;
  final String reference;
  final String? sessionId;
  final String initialBalance;
  final String finalBalance;
  final DateTime date;
  final String? phoneNumber;
  final List<ReceiptField>? extraFields;

  const TransactionReceiptData({
    required this.service,
    required this.type,
    required this.description,
    required this.amount,
    required this.status,
    this.statusMessage,
    required this.reference,
    this.sessionId,
    required this.initialBalance,
    required this.finalBalance,
    required this.date,
    this.phoneNumber,
    this.extraFields,
  });

  /// Convenience factory from a raw API response map.
  factory TransactionReceiptData.fromApiMap(
    Map<String, dynamic> m, {
    required String service,
    required String amount,
    List<ReceiptField>? extraFields,
  }) {
    return TransactionReceiptData(
      service: service,
      type: m['service_type'] ?? m['type'] ?? service,
      description: m['description'] ?? '',
      amount: amount,
      status: m['status'] ?? 'success',
      statusMessage: m['message']?.toString(),
      reference: m['reference'] ?? m['paymentReference'] ?? '',
      sessionId: m['session_id']?.toString(),
      initialBalance: m['initial_balance']?.toString() ?? '',
      finalBalance: m['final_balance']?.toString() ?? '',
      date: DateTime.now(),
      phoneNumber: m['phone_number'],
      extraFields: extraFields,
    );
  }
}

class TransactionReceiptScreen extends StatefulWidget {
  final TransactionReceiptData data;
  final bool isNewTransaction;

  const TransactionReceiptScreen({
    super.key,
    required this.data,
    this.isNewTransaction = true,
  });

  @override
  State<TransactionReceiptScreen> createState() =>
      _TransactionReceiptScreenState();
}

class _TransactionReceiptScreenState extends State<TransactionReceiptScreen> {
  // Brand colors
  static const Color _brandColor = Color(0xFF34544A);
  static const Color _brandAccent = Color(0xFF7CA183);
  static const Color _successGreen = Color(0xFF1DBF73);

  final GlobalKey _receiptKey = GlobalKey();
  bool _isSharing = false;

  void _goHome(BuildContext context) {
    Navigator.of(context).pushNamedAndRemoveUntil('/homeNav', (route) => false);
  }

  void _goBack(BuildContext context) {
    final nav = Navigator.of(context);
    if (nav.canPop()) {
      nav.pop();
    } else {
      _goHome(context);
    }
  }

  String get _formattedDate {
    final d = widget.data.date;
    final hour = d.hour > 12 ? d.hour - 12 : (d.hour == 0 ? 12 : d.hour);
    final ampm = d.hour >= 12 ? 'PM' : 'AM';
    final min = d.minute.toString().padLeft(2, '0');
    const months = [
      'Jan',
      'Feb',
      'Mar',
      'Apr',
      'May',
      'Jun',
      'Jul',
      'Aug',
      'Sep',
      'Oct',
      'Nov',
      'Dec',
    ];
    return '$hour:$min $ampm · ${d.day} ${months[d.month - 1]} ${d.year}';
  }

  String _displayAmount(String raw) {
    final trimmed = raw.trim();
    if (trimmed.isEmpty) return '₦0.00';
    if (trimmed.startsWith('₦') ||
        trimmed.startsWith('\$') ||
        trimmed.startsWith('€') ||
        trimmed.startsWith('£') ||
        RegExp(r'^[A-Za-z]{3}\b').hasMatch(trimmed)) {
      final currencyCode = RegExp(r'^([A-Za-z]{3})\s*').firstMatch(trimmed);
      if (currencyCode != null) {
        final number = trimmed.substring(currencyCode.end);
        return '${currencyCode.group(1)} ${formatNumber(number)}';
      }
      final symbol = trimmed[0];
      final number = trimmed.substring(1);
      return '$symbol${formatNumber(number)}';
    }
    return formatCurrency(trimmed);
  }

  void _copyToClipboard(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    HapticFeedback.lightImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(
              Icons.check_circle_rounded,
              color: Colors.white,
              size: 18,
            ),
            const SizedBox(width: 8),
            Text('$label copied to clipboard!'),
          ],
        ),
        backgroundColor: _brandColor,
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10.r),
        ),
      ),
    );
  }

  Rect _shareAnchor(BuildContext context) {
    try {
      final box = context.findRenderObject() as RenderBox?;
      if (box != null && box.hasSize) {
        return box.localToGlobal(Offset.zero) & box.size;
      }
    } catch (_) {}
    final size = MediaQuery.maybeOf(context)?.size ?? const Size(400, 800);
    return Rect.fromLTWH(0, 0, size.width, size.height / 2);
  }

  Future<void> _shareReceipt(BuildContext context) async {
    if (_isSharing) return;
    setState(() => _isSharing = true);
    final Rect sharePosition = _shareAnchor(context);

    try {
      final boundaryContext = _receiptKey.currentContext;
      if (boundaryContext == null) throw StateError('Receipt not ready');

      final boundary =
          boundaryContext.findRenderObject() as RenderRepaintBoundary?;
      if (boundary == null) throw StateError('Receipt boundary not found');

      if (boundary.debugNeedsPaint) {
        await WidgetsBinding.instance.endOfFrame;
      }

      final image = await boundary.toImage(pixelRatio: 2.0);
      final byteData = await image.toByteData(format: ui.ImageByteFormat.png);
      if (byteData == null) throw StateError('Failed to encode receipt image');

      final pngBytes = byteData.buffer.asUint8List();
      final directory = await getTemporaryDirectory();
      final file = File(
        '${directory.path}/dupay_receipt_${DateTime.now().millisecondsSinceEpoch}.png',
      );
      await file.writeAsBytes(pngBytes);

      if (!context.mounted) return;

      await Share.shareXFiles(
        [XFile(file.path, mimeType: 'image/png', name: 'dupay_receipt.png')],
        text:
            'Dupay Transaction Receipt\nAmount: ${_displayAmount(widget.data.amount)}\nRef: ${widget.data.reference}',
        subject: 'Dupay Transaction Receipt',
        sharePositionOrigin: sharePosition,
      );
    } catch (e) {
      if (!context.mounted) return;
      try {
        await Share.share(
          'Dupay Transaction Receipt\n'
          'Service: ${widget.data.service}\n'
          'Amount: ${_displayAmount(widget.data.amount)}\n'
          'Status: ${widget.data.status}\n'
          'Ref: ${widget.data.reference}'
          '${widget.data.sessionId != null && widget.data.sessionId!.isNotEmpty ? '\nSession ID: ${widget.data.sessionId}' : ''}\n'
          'Date: $_formattedDate',
          subject: 'Dupay Transaction Receipt',
          sharePositionOrigin: sharePosition,
        );
      } catch (_) {
        _copyToClipboard(
          'Dupay Receipt\nService: ${widget.data.service}\nAmount: ${_displayAmount(widget.data.amount)}\nRef: ${widget.data.reference}',
          'Receipt details',
        );
      }
    } finally {
      if (mounted) setState(() => _isSharing = false);
    }
  }

  String get _heroTitle {
    final s = widget.data.status.trim().toLowerCase();
    if (s == 'success' || s == 'successful' || s == '1') {
      final service = widget.data.service.toLowerCase();
      if (service.contains('transfer') || service.contains('send')) {
        return 'Transfer Successful!';
      } else if (service.contains('airtime') || service.contains('recharge')) {
        return 'Recharge Successful!';
      } else if (service.contains('data')) {
        return 'Data Purchase Successful!';
      } else if (service.contains('bill') ||
          service.contains('cable') ||
          service.contains('electric')) {
        return 'Payment Successful!';
      }
      return 'Payment Successful!';
    } else if (s == 'pending') {
      return 'Payment Processing';
    } else if (s == 'refund') {
      return 'Transaction Refunded';
    } else {
      return 'Transaction Failed';
    }
  }

  Color get _statusColor {
    final s = widget.data.status.trim().toLowerCase();
    if (s == 'success' || s == 'successful' || s == '1') return _successGreen;
    if (s == 'refund') return const Color(0xFF2196F3);
    if (s == 'pending') return Colors.orange;
    return const Color(0xFFE53935);
  }

  IconData get _heroIcon {
    final s = widget.data.status.trim().toLowerCase();
    if (s == 'success' || s == 'successful' || s == '1')
      return Icons.check_rounded;
    if (s == 'refund') return Icons.replay_rounded;
    if (s == 'pending') return Icons.access_time_rounded;
    return Icons.close_rounded;
  }

  @override
  Widget build(BuildContext context) {
    final d = widget.data;
    final isSuccess =
        d.status.trim().toLowerCase() == 'success' ||
        d.status.trim().toLowerCase() == 'successful' ||
        d.status.trim() == '1';

    return Scaffold(
      backgroundColor: _brandColor,
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Top App Bar
            Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 10.h),
              child: Row(
                children: [
                  InkWell(
                    onTap: () => _goBack(context),
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
                  Expanded(
                    child: Text(
                      widget.isNewTransaction
                          ? 'Payment Confirmation'
                          : 'Transaction Receipt',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 18.sp,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  InkWell(
                    onTap: () => _shareReceipt(context),
                    borderRadius: BorderRadius.circular(10.r),
                    child: Container(
                      padding: EdgeInsets.all(8.w),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(10.r),
                      ),
                      child: Icon(
                        Icons.share_rounded,
                        color: Colors.white,
                        size: 18.sp,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Main Content Area with curved sheet
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: BoxDecoration(
                  color: const Color(0xFFF6F8F7),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(30.r),
                    topRight: Radius.circular(30.r),
                  ),
                ),
                child: SingleChildScrollView(
                  physics: const BouncingScrollPhysics(),
                  padding: EdgeInsets.fromLTRB(20.w, 20.h, 20.w, 36.h),
                  child: Column(
                    children: [
                      // Celebratory Hero Section
                      Container(
                        width: 80.w,
                        height: 80.h,
                        decoration: BoxDecoration(
                          color: _statusColor.withOpacity(0.12),
                          shape: BoxShape.circle,
                        ),
                        child: Center(
                          child: Container(
                            width: 60.w,
                            height: 60.h,
                            decoration: BoxDecoration(
                              color: _statusColor,
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(
                                  color: _statusColor.withOpacity(0.35),
                                  blurRadius: 16,
                                  offset: const Offset(0, 6),
                                ),
                              ],
                            ),
                            child: Icon(
                              _heroIcon,
                              color: Colors.white,
                              size: 34.sp,
                            ),
                          ),
                        ),
                      ),
                      SizedBox(height: 14.h),

                      Text(
                        _heroTitle,
                        style: TextStyle(
                          fontSize: 22.sp,
                          fontWeight: FontWeight.bold,
                          color: const Color(0xFF1A1A1A),
                          letterSpacing: -0.2,
                        ),
                      ),
                      SizedBox(height: 4.h),

                      Text(
                        _displayAmount(d.amount),
                        style: TextStyle(
                          fontSize: 28.sp,
                          fontWeight: FontWeight.w800,
                          color: _brandColor,
                          letterSpacing: -0.5,
                        ),
                      ),
                      SizedBox(height: 6.h),

                      Text(
                        isSuccess
                            ? (d.statusMessage?.isNotEmpty == true
                                ? d.statusMessage!
                                : 'Your transaction was completed successfully')
                            : (d.statusMessage?.isNotEmpty == true
                                ? d.statusMessage!
                                : 'Status: ${d.status}'),
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 13.sp,
                          color: Colors.grey[600],
                        ),
                      ),

                      SizedBox(height: 24.h),

                      // Sharable Digital Receipt Card
                      RepaintBoundary(
                        key: _receiptKey,
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(20.r),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.04),
                                blurRadius: 14,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          padding: EdgeInsets.all(20.w),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              // Receipt Header
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: EdgeInsets.all(6.w),
                                        decoration: BoxDecoration(
                                          color: _brandColor.withOpacity(0.08),
                                          borderRadius: BorderRadius.circular(
                                            8.r,
                                          ),
                                        ),
                                        child: Image.asset(
                                          'assets/images/applogo.jpeg',
                                          width: 22.w,
                                          height: 22.h,
                                          errorBuilder:
                                              (_, __, ___) => Icon(
                                                Icons
                                                    .account_balance_wallet_rounded,
                                                size: 20.sp,
                                                color: _brandColor,
                                              ),
                                        ),
                                      ),
                                      SizedBox(width: 8.w),
                                      Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            'Dupay Receipt',
                                            style: TextStyle(
                                              fontSize: 14.sp,
                                              fontWeight: FontWeight.bold,
                                              color: const Color(0xFF1A1A1A),
                                            ),
                                          ),
                                          Text(
                                            'Official Confirmation',
                                            style: TextStyle(
                                              fontSize: 11.sp,
                                              color: _brandAccent,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                  // Status Badge
                                  Container(
                                    padding: EdgeInsets.symmetric(
                                      horizontal: 10.w,
                                      vertical: 5.h,
                                    ),
                                    decoration: BoxDecoration(
                                      color: _statusColor.withOpacity(0.12),
                                      borderRadius: BorderRadius.circular(20.r),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(
                                          _heroIcon,
                                          size: 12.sp,
                                          color: _statusColor,
                                        ),
                                        SizedBox(width: 4.w),
                                        Text(
                                          d.status.toUpperCase(),
                                          style: TextStyle(
                                            fontSize: 10.5.sp,
                                            fontWeight: FontWeight.bold,
                                            color: _statusColor,
                                            letterSpacing: 0.4,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),

                              Padding(
                                padding: EdgeInsets.symmetric(vertical: 14.h),
                                child: const _DashedDivider(),
                              ),

                              // Key details rows
                              _ReceiptItem(label: 'Service', value: d.service),

                              if (d.type.isNotEmpty && d.type != d.service) ...[
                                SizedBox(height: 10.h),
                                _ReceiptItem(label: 'Category', value: d.type),
                              ],

                              if (d.description.isNotEmpty) ...[
                                SizedBox(height: 10.h),
                                _ReceiptItem(
                                  label: 'Description',
                                  value: d.description,
                                ),
                              ],

                              if (d.phoneNumber != null &&
                                  d.phoneNumber!.isNotEmpty) ...[
                                SizedBox(height: 10.h),
                                _ReceiptItem(
                                  label: 'Phone Number',
                                  value: d.phoneNumber!,
                                ),
                              ],

                              // Special extra fields (e.g. meter tokens, units)
                              if (d.extraFields != null &&
                                  d.extraFields!.isNotEmpty) ...[
                                SizedBox(height: 12.h),
                                for (final f in d.extraFields!) ...[
                                  if (f.label.toLowerCase().contains(
                                    'token',
                                  )) ...[
                                    // Highlighted token callout box
                                    Container(
                                      margin: EdgeInsets.symmetric(
                                        vertical: 6.h,
                                      ),
                                      padding: EdgeInsets.all(12.w),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFFE8F5E9),
                                        borderRadius: BorderRadius.circular(
                                          12.r,
                                        ),
                                        border: Border.all(
                                          color: _successGreen.withOpacity(0.4),
                                        ),
                                      ),
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            mainAxisAlignment:
                                                MainAxisAlignment.spaceBetween,
                                            children: [
                                              Text(
                                                'PREPAID TOKEN',
                                                style: TextStyle(
                                                  fontSize: 11.sp,
                                                  fontWeight: FontWeight.w700,
                                                  color: _brandColor,
                                                  letterSpacing: 0.5,
                                                ),
                                              ),
                                              InkWell(
                                                onTap:
                                                    () => _copyToClipboard(
                                                      f.value,
                                                      'Token',
                                                    ),
                                                child: Row(
                                                  children: [
                                                    Icon(
                                                      Icons.copy_rounded,
                                                      size: 14.sp,
                                                      color: _brandColor,
                                                    ),
                                                    SizedBox(width: 4.w),
                                                    Text(
                                                      'Copy',
                                                      style: TextStyle(
                                                        fontSize: 11.5.sp,
                                                        fontWeight:
                                                            FontWeight.bold,
                                                        color: _brandColor,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                            ],
                                          ),
                                          SizedBox(height: 6.h),
                                          SelectableText(
                                            f.value,
                                            style: TextStyle(
                                              fontSize: 17.sp,
                                              fontWeight: FontWeight.w800,
                                              letterSpacing: 1.5,
                                              color: const Color(0xFF1B5E20),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ] else ...[
                                    _ReceiptItem(
                                      label: f.label,
                                      value: _formatReceiptField(f),
                                      isBold: f.isBold,
                                    ),
                                    SizedBox(height: 8.h),
                                  ],
                                ],
                              ],

                              SizedBox(height: 10.h),
                              _ReceiptItem(
                                label: 'Amount Paid',
                                value: _displayAmount(d.amount),
                                isBold: true,
                                valueColor: _brandColor,
                              ),

                              Padding(
                                padding: EdgeInsets.symmetric(vertical: 14.h),
                                child: const _DashedDivider(),
                              ),

                              // Reference with Copy Button
                              if (d.reference.isNotEmpty) ...[
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Text(
                                      'Reference',
                                      style: TextStyle(
                                        fontSize: 13.sp,
                                        color: Colors.grey[600],
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    InkWell(
                                      onTap:
                                          () => _copyToClipboard(
                                            d.reference,
                                            'Reference',
                                          ),
                                      borderRadius: BorderRadius.circular(6.r),
                                      child: Padding(
                                        padding: EdgeInsets.symmetric(
                                          horizontal: 4.w,
                                          vertical: 2.h,
                                        ),
                                        child: Row(
                                          children: [
                                            Text(
                                              d.reference,
                                              style: TextStyle(
                                                fontSize: 13.sp,
                                                fontWeight: FontWeight.w600,
                                                color: const Color(0xFF1A1A1A),
                                              ),
                                            ),
                                            SizedBox(width: 6.w),
                                            Icon(
                                              Icons.copy_rounded,
                                              size: 14.sp,
                                              color: _brandColor,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                SizedBox(height: 10.h),
                              ],

                              // Session ID with Copy Button (if present)
                              if (d.sessionId != null &&
                                  d.sessionId!.isNotEmpty) ...[
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  crossAxisAlignment: CrossAxisAlignment.center,
                                  children: [
                                    Text(
                                      'Session ID',
                                      style: TextStyle(
                                        fontSize: 13.sp,
                                        color: Colors.grey[600],
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    InkWell(
                                      onTap:
                                          () => _copyToClipboard(
                                            d.sessionId!,
                                            'Session ID',
                                          ),
                                      borderRadius: BorderRadius.circular(6.r),
                                      child: Padding(
                                        padding: EdgeInsets.symmetric(
                                          horizontal: 4.w,
                                          vertical: 2.h,
                                        ),
                                        child: Row(
                                          children: [
                                            Text(
                                              d.sessionId!,
                                              style: TextStyle(
                                                fontSize: 13.sp,
                                                fontWeight: FontWeight.w600,
                                                color: const Color(0xFF1A1A1A),
                                              ),
                                            ),
                                            SizedBox(width: 6.w),
                                            Icon(
                                              Icons.copy_rounded,
                                              size: 14.sp,
                                              color: _brandColor,
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                SizedBox(height: 10.h),
                              ],

                              // Date & Time
                              _ReceiptItem(
                                label: 'Date & Time',
                                value: _formattedDate,
                              ),
                            ],
                          ),
                        ),
                      ),

                      SizedBox(height: 32.h),

                      // Action Buttons
                      Column(
                        children: [
                          // Primary Done / Return Home Button
                          SizedBox(
                            width: double.infinity,
                            height: 52.h,
                            child: ElevatedButton(
                              onPressed: () => _goHome(context),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _brandColor,
                                foregroundColor: Colors.white,
                                elevation: 0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14.r),
                                ),
                              ),
                              child: Text(
                                widget.isNewTransaction
                                    ? 'Back to Home'
                                    : 'Done',
                                style: TextStyle(
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.w600,
                                  letterSpacing: 0.3,
                                ),
                              ),
                            ),
                          ),

                          SizedBox(height: 12.h),

                          // Secondary Share Receipt Button
                          SizedBox(
                            width: double.infinity,
                            height: 50.h,
                            child: OutlinedButton.icon(
                              onPressed:
                                  _isSharing
                                      ? null
                                      : () => _shareReceipt(context),
                              icon:
                                  _isSharing
                                      ? SizedBox(
                                        width: 18.w,
                                        height: 18.h,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: _brandColor,
                                        ),
                                      )
                                      : Icon(
                                        Icons.share_rounded,
                                        size: 18.sp,
                                        color: _brandColor,
                                      ),
                              label: Text(
                                'Share Receipt',
                                style: TextStyle(
                                  fontSize: 15.sp,
                                  fontWeight: FontWeight.w600,
                                  color: _brandColor,
                                ),
                              ),
                              style: OutlinedButton.styleFrom(
                                backgroundColor: Colors.white,
                                side: BorderSide(
                                  color: _brandColor.withOpacity(0.35),
                                  width: 1.2,
                                ),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(14.r),
                                ),
                              ),
                            ),
                          ),

                          SizedBox(height: 16.h),

                          // Report Issue / Live Chat
                          InkWell(
                            onTap:
                                () => Navigator.pushNamed(context, '/liveChat'),
                            borderRadius: BorderRadius.circular(8.r),
                            child: Padding(
                              padding: EdgeInsets.symmetric(
                                horizontal: 12.w,
                                vertical: 6.h,
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    Icons.headset_mic_outlined,
                                    size: 16.sp,
                                    color: Colors.grey[600],
                                  ),
                                  SizedBox(width: 6.w),
                                  Text(
                                    'Need help with this transaction? Contact Support',
                                    style: TextStyle(
                                      fontSize: 12.5.sp,
                                      color: Colors.grey[600],
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatReceiptField(ReceiptField field) {
    final label = field.label.toLowerCase();
    if (label.contains('amount') ||
        label.contains('balance') ||
        label.contains('fee') ||
        label.contains('charge')) {
      return _displayAmount(field.value);
    }
    return field.value;
  }
}

class _ReceiptItem extends StatelessWidget {
  final String label;
  final String value;
  final bool isBold;
  final Color? valueColor;

  const _ReceiptItem({
    required this.label,
    required this.value,
    this.isBold = false,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: 13.sp,
            color: Colors.grey[600],
            fontWeight: FontWeight.w500,
          ),
        ),
        SizedBox(width: 16.w),
        Flexible(
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: TextStyle(
              fontSize: 13.5.sp,
              fontWeight: isBold ? FontWeight.bold : FontWeight.w600,
              color: valueColor ?? const Color(0xFF1A1A1A),
            ),
          ),
        ),
      ],
    );
  }
}

class _DashedDivider extends StatelessWidget {
  const _DashedDivider();

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final boxWidth = constraints.constrainWidth();
        const dashWidth = 5.0;
        const dashSpace = 4.0;
        final dashCount = (boxWidth / (dashWidth + dashSpace)).floor();
        return Flex(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          direction: Axis.horizontal,
          children: List.generate(dashCount, (_) {
            return SizedBox(
              width: dashWidth,
              height: 1,
              child: DecoratedBox(
                decoration: BoxDecoration(color: Colors.grey[300]),
              ),
            );
          }),
        );
      },
    );
  }
}
