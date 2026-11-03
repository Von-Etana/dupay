// lib/screen/notifications_screen.dart
//
// Modern, standard Notification Center for Dupay.
// Matches Dupay brand palette (0xFF34544A pine green, 0xFF7CA183 sage accent),
// supports category filtering, real-time transaction & transfer alerts,
// full notification detail dialogs/sheets, one-tap reference copy,
// and direct receipt inspection.

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:dupay/screen/transaction/transaction_receipt_screen.dart';
import 'package:dupay/providers/transaction_provider.dart';
import 'package:dupay/models/transaction/transaction_model.dart';
import 'package:dupay/core/utils/formatters.dart';
import 'package:dupay/widgets/app_alert.dart';

enum NotificationFilter { all, transactions, security, general }

class AppNotificationModel {
  final String id;
  final String title;
  final String body;
  final String type; // 'credit', 'debit', 'security', 'info', 'bill'
  final String? amount;
  final String? reference;
  final String? sessionId;
  final String? status;
  final String? service;
  final DateTime timestamp;
  bool isRead;

  AppNotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.type,
    this.amount,
    this.reference,
    this.sessionId,
    this.status,
    this.service,
    required this.timestamp,
    this.isRead = false,
  });
}

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() =>
      _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  static const Color _brandColor = Color(0xFF34544A);
  static const Color _brandAccent = Color(0xFF7CA183);
  static const Color _successGreen = Color(0xFF1DBF73);

  NotificationFilter _selectedFilter = NotificationFilter.all;
  final List<AppNotificationModel> _notifications = [];
  bool _initializedWithArgs = false;

  @override
  void initState() {
    super.initState();
    _loadInitialNotifications();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_initializedWithArgs) {
      _initializedWithArgs = true;
      final args = ModalRoute.of(context)?.settings.arguments;
      if (args is Map) {
        final title = args['title'] as String?;
        final body = args['body'] as String?;
        if (title != null || body != null) {
          final customNotification = AppNotificationModel(
            id: 'incoming_${DateTime.now().millisecondsSinceEpoch}',
            title: title ?? 'DuPay Notification',
            body: body ?? '',
            type: (args['type'] as String?) ?? 'info',
            amount: args['amount'] as String?,
            reference: args['reference'] as String?,
            sessionId: args['session_id'] as String?,
            status: args['status'] as String? ?? 'success',
            service: args['service'] as String?,
            timestamp: DateTime.now(),
            isRead: false,
          );
          _notifications.insert(0, customNotification);
          WidgetsBinding.instance.addPostFrameCallback((_) {
            _showNotificationDetails(customNotification);
          });
        }
      }
    }
  }

  void _loadInitialNotifications() {
    // Provide clean, structured default operational notifications
    _notifications.addAll([
      AppNotificationModel(
        id: 'notif_sec_1',
        title: 'Two-Factor Authentication Active',
        body:
            'Login OTP verification is enabled to protect your wallet and account.',
        type: 'security',
        status: 'active',
        service: 'Security System',
        timestamp: DateTime.now().subtract(const Duration(minutes: 25)),
        isRead: false,
      ),
      AppNotificationModel(
        id: 'notif_tx_1',
        title: 'Wallet Funding Confirmed',
        body:
            'Your deposit was successful. Funds have been credited to your DuPay wallet.',
        type: 'credit',
        amount: '₦5,000.00',
        reference: 'DUPAY-DEP-849201',
        sessionId: '99912084920199',
        status: 'success',
        service: 'Wallet Deposit',
        timestamp: DateTime.now().subtract(const Duration(hours: 3)),
        isRead: false,
      ),
      AppNotificationModel(
        id: 'notif_tx_2',
        title: 'Airtime Recharge Successful',
        body: 'Airtime recharge was successfully completed.',
        type: 'bill',
        amount: '₦1,000.00',
        reference: 'DUPAY-AIR-203912',
        status: 'success',
        service: 'Airtime',
        timestamp: DateTime.now().subtract(const Duration(hours: 8)),
        isRead: true,
      ),
      AppNotificationModel(
        id: 'notif_info_1',
        title: 'Instant Virtual Cards & Receipts',
        body:
            'You can now share high-definition receipts and generate instant virtual cards.',
        type: 'info',
        service: 'Product Update',
        timestamp: DateTime.now().subtract(const Duration(days: 1)),
        isRead: true,
      ),
    ]);
  }

  List<AppNotificationModel> _getFilteredNotifications(
    List<TransactionModel>? transactions,
  ) {
    // Merge live transactions from provider into the notification center
    final combined = List<AppNotificationModel>.from(_notifications);

    if (transactions != null) {
      for (final tx in transactions.take(6)) {
        final isCredit = tx.type == '1' || tx.type.toLowerCase() == 'credit';
        final title =
            isCredit
                ? 'Transfer Received (${formatCurrency(tx.amount)})'
                : 'Payment Completed (${formatCurrency(tx.amount)})';

        if (!combined.any((n) => n.reference == tx.reference)) {
          combined.add(
            AppNotificationModel(
              id: 'tx_${tx.id}',
              title: title,
              body: tx.description ?? 'Transaction processed successfully.',
              type: isCredit ? 'credit' : 'debit',
              amount: formatCurrency(tx.amount),
              reference: tx.reference ?? tx.id,
              sessionId: tx.metadata?['session_id']?.toString(),
              status: tx.status,
              service:
                  tx.metadata?['service_type']?.toString() ??
                  (isCredit ? 'Credit Transfer' : 'Debit Payment'),
              timestamp: tx.createdAt,
              isRead: true,
            ),
          );
        }
      }
    }

    combined.sort((a, b) => b.timestamp.compareTo(a.timestamp));

    switch (_selectedFilter) {
      case NotificationFilter.transactions:
        return combined
            .where(
              (n) =>
                  n.type == 'credit' || n.type == 'debit' || n.type == 'bill',
            )
            .toList();
      case NotificationFilter.security:
        return combined.where((n) => n.type == 'security').toList();
      case NotificationFilter.general:
        return combined.where((n) => n.type == 'info').toList();
      case NotificationFilter.all:
        return combined;
    }
  }

  void _markAllAsRead() {
    setState(() {
      for (var n in _notifications) {
        n.isRead = true;
      }
    });
    AppAlert.showSuccess(context, 'All notifications marked as read');
  }

  void _showNotificationDetails(AppNotificationModel item) {
    setState(() => item.isRead = true);
    HapticFeedback.lightImpact();

    final isTx =
        item.amount != null ||
        item.reference != null ||
        item.type == 'credit' ||
        item.type == 'debit' ||
        item.type == 'bill';
    final normalizedStatus = (item.status ?? 'success').trim().toLowerCase();
    final statusColor =
        normalizedStatus == 'pending'
            ? Colors.orange
            : (normalizedStatus == 'refund'
                ? Colors.blue
                : (normalizedStatus == 'success' ||
                        normalizedStatus == 'successful' ||
                        normalizedStatus == '1'
                    ? _successGreen
                    : Colors.red));
    final statusIcon =
        normalizedStatus == 'pending'
            ? Icons.access_time_rounded
            : (normalizedStatus == 'refund'
                ? Icons.replay_rounded
                : (statusColor == _successGreen
                    ? Icons.check_circle_rounded
                    : Icons.cancel_rounded));

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder:
          (ctx) => Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(28.r),
                topRight: Radius.circular(28.r),
              ),
            ),
            padding: EdgeInsets.fromLTRB(22.w, 14.h, 22.w, 32.h),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Drag indicator handle
                Center(
                  child: Container(
                    width: 44.w,
                    height: 4.h,
                    decoration: BoxDecoration(
                      color: Colors.grey[300],
                      borderRadius: BorderRadius.circular(10.r),
                    ),
                  ),
                ),
                SizedBox(height: 18.h),

                // Modal Header
                Row(
                  children: [
                    Container(
                      width: 46.w,
                      height: 46.h,
                      decoration: BoxDecoration(
                        color: _iconBgColor(item.type),
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        _typeIcon(item.type),
                        color: _iconColor(item.type),
                        size: 24.sp,
                      ),
                    ),
                    SizedBox(width: 14.w),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.service ??
                                (isTx
                                    ? 'Transaction Details'
                                    : 'Security Alert'),
                            style: TextStyle(
                              fontSize: 12.sp,
                              color: _brandAccent,
                              fontWeight: FontWeight.w600,
                              letterSpacing: 0.3,
                            ),
                          ),
                          Text(
                            item.title,
                            style: TextStyle(
                              fontSize: 16.5.sp,
                              fontWeight: FontWeight.bold,
                              color: const Color(0xFF1A1A1A),
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: Icon(
                        Icons.close_rounded,
                        size: 22.sp,
                        color: Colors.grey[600],
                      ),
                      onPressed: () => Navigator.of(ctx).pop(),
                    ),
                  ],
                ),

                Padding(
                  padding: EdgeInsets.symmetric(vertical: 14.h),
                  child: Divider(color: Colors.grey[200], height: 1),
                ),

                // If it's a transaction/transfer notification: show full financial breakdown
                if (isTx) ...[
                  if (item.amount != null) ...[
                    Center(
                      child: Column(
                        children: [
                          Text(
                            'Amount',
                            style: TextStyle(
                              fontSize: 12.sp,
                              color: Colors.grey[500],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          SizedBox(height: 4.h),
                          Text(
                            item.amount!,
                            style: TextStyle(
                              fontSize: 28.sp,
                              fontWeight: FontWeight.w800,
                              color: _brandColor,
                              letterSpacing: -0.5,
                            ),
                          ),
                          SizedBox(height: 6.h),
                          Container(
                            padding: EdgeInsets.symmetric(
                              horizontal: 12.w,
                              vertical: 4.h,
                            ),
                            decoration: BoxDecoration(
                              color: statusColor.withOpacity(0.12),
                              borderRadius: BorderRadius.circular(20.r),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  statusIcon,
                                  size: 14.sp,
                                  color: statusColor,
                                ),
                                SizedBox(width: 4.w),
                                Text(
                                  (item.status ?? 'Successful').toUpperCase(),
                                  style: TextStyle(
                                    fontSize: 11.sp,
                                    fontWeight: FontWeight.bold,
                                    color: statusColor,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: 18.h),
                  ],

                  Container(
                    padding: EdgeInsets.all(14.w),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAF9),
                      borderRadius: BorderRadius.circular(16.r),
                      border: Border.all(color: const Color(0xFFE5EAE7)),
                    ),
                    child: Column(
                      children: [
                        _DetailRow('Description', item.body),
                        if (item.reference != null &&
                            item.reference!.isNotEmpty) ...[
                          SizedBox(height: 10.h),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Reference',
                                style: TextStyle(
                                  fontSize: 13.sp,
                                  color: Colors.grey[600],
                                ),
                              ),
                              InkWell(
                                onTap: () {
                                  Clipboard.setData(
                                    ClipboardData(text: item.reference!),
                                  );
                                  HapticFeedback.lightImpact();
                                  AppAlert.showSuccess(
                                    context,
                                    'Reference copied to clipboard',
                                  );
                                },
                                child: Row(
                                  children: [
                                    Text(
                                      item.reference!,
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
                            ],
                          ),
                        ],
                        if (item.sessionId != null &&
                            item.sessionId!.isNotEmpty) ...[
                          SizedBox(height: 10.h),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Session ID',
                                style: TextStyle(
                                  fontSize: 13.sp,
                                  color: Colors.grey[600],
                                ),
                              ),
                              InkWell(
                                onTap: () {
                                  Clipboard.setData(
                                    ClipboardData(text: item.sessionId!),
                                  );
                                  HapticFeedback.lightImpact();
                                  AppAlert.showSuccess(
                                    context,
                                    'Session ID copied to clipboard',
                                  );
                                },
                                child: Row(
                                  children: [
                                    Text(
                                      item.sessionId!,
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
                            ],
                          ),
                        ],
                        SizedBox(height: 10.h),
                        _DetailRow(
                          'Date & Time',
                          _formatTimestamp(item.timestamp),
                        ),
                      ],
                    ),
                  ),

                  SizedBox(height: 20.h),

                  // Button: View Full Receipt
                  SizedBox(
                    height: 50.h,
                    child: ElevatedButton.icon(
                      onPressed: () {
                        Navigator.of(ctx).pop();
                        Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder:
                                (_) => TransactionReceiptScreen(
                                  data: TransactionReceiptData(
                                    service: item.service ?? 'Transaction',
                                    type: item.type,
                                    description: item.body,
                                    amount: item.amount ?? '₦0.00',
                                    status: item.status ?? 'success',
                                    reference: item.reference ?? '',
                                    sessionId: item.sessionId,
                                    initialBalance: '',
                                    finalBalance: '',
                                    date: item.timestamp,
                                  ),
                                  isNewTransaction: false,
                                ),
                          ),
                        );
                      },
                      icon: Icon(Icons.receipt_long_rounded, size: 18.sp),
                      label: const Text('View Full Digital Receipt'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _brandColor,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                      ),
                    ),
                  ),
                ] else ...[
                  // General / Security Information Message
                  Container(
                    padding: EdgeInsets.all(16.w),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAF9),
                      borderRadius: BorderRadius.circular(16.r),
                      border: Border.all(color: const Color(0xFFE5EAE7)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.body,
                          style: TextStyle(
                            fontSize: 14.sp,
                            color: const Color(0xFF2D3748),
                            height: 1.5,
                          ),
                        ),
                        SizedBox(height: 14.h),
                        Row(
                          children: [
                            Icon(
                              Icons.access_time_rounded,
                              size: 14.sp,
                              color: Colors.grey[500],
                            ),
                            SizedBox(width: 6.w),
                            Text(
                              _formatTimestamp(item.timestamp),
                              style: TextStyle(
                                fontSize: 12.sp,
                                color: Colors.grey[600],
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  SizedBox(height: 20.h),
                  SizedBox(
                    height: 48.h,
                    child: ElevatedButton(
                      onPressed: () => Navigator.of(ctx).pop(),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _brandColor,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                      ),
                      child: const Text('Got it'),
                    ),
                  ),
                ],
              ],
            ),
          ),
    );
  }

  IconData _typeIcon(String type) {
    switch (type.toLowerCase()) {
      case 'credit':
        return Icons.arrow_downward_rounded;
      case 'debit':
        return Icons.arrow_upward_rounded;
      case 'bill':
        return Icons.receipt_rounded;
      case 'security':
        return Icons.shield_rounded;
      default:
        return Icons.notifications_active_rounded;
    }
  }

  Color _iconColor(String type) {
    switch (type.toLowerCase()) {
      case 'credit':
        return _successGreen;
      case 'debit':
        return _brandColor;
      case 'bill':
        return const Color(0xFFE65100);
      case 'security':
        return const Color(0xFF1E3A8A);
      default:
        return _brandColor;
    }
  }

  Color _iconBgColor(String type) {
    return _iconColor(type).withOpacity(0.12);
  }

  String _formatTimestamp(DateTime dt) {
    final now = DateTime.now();
    final diff = now.difference(dt);

    if (diff.inMinutes < 2) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays == 1) return 'Yesterday';
    return '${dt.day}/${dt.month}/${dt.year}';
  }

  @override
  Widget build(BuildContext context) {
    final txState = ref.watch(transactionNotifierProvider);
    final transactions = txState.transactions;
    final filtered = _getFilteredNotifications(transactions);

    return Scaffold(
      backgroundColor: _brandColor,
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Top App Bar
            Padding(
              padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
              child: Row(
                children: [
                  InkWell(
                    onTap: () => Navigator.of(context).pop(),
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
                      'Notification Center',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        letterSpacing: -0.2,
                      ),
                    ),
                  ),
                  InkWell(
                    onTap: _markAllAsRead,
                    borderRadius: BorderRadius.circular(10.r),
                    child: Container(
                      padding: EdgeInsets.all(8.w),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(10.r),
                      ),
                      child: Icon(
                        Icons.done_all_rounded,
                        color: Colors.white,
                        size: 18.sp,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // White curved bottom container
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
                child: Column(
                  children: [
                    // Category Filter Pills
                    Container(
                      padding: EdgeInsets.fromLTRB(16.w, 16.h, 16.w, 10.h),
                      child: SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        physics: const BouncingScrollPhysics(),
                        child: Row(
                          children: [
                            _FilterChip(
                              label: 'All',
                              isSelected:
                                  _selectedFilter == NotificationFilter.all,
                              onTap:
                                  () => setState(
                                    () =>
                                        _selectedFilter =
                                            NotificationFilter.all,
                                  ),
                            ),
                            SizedBox(width: 8.w),
                            _FilterChip(
                              label: 'Transfers & Payments',
                              isSelected:
                                  _selectedFilter ==
                                  NotificationFilter.transactions,
                              onTap:
                                  () => setState(
                                    () =>
                                        _selectedFilter =
                                            NotificationFilter.transactions,
                                  ),
                            ),
                            SizedBox(width: 8.w),
                            _FilterChip(
                              label: 'Security & Alerts',
                              isSelected:
                                  _selectedFilter ==
                                  NotificationFilter.security,
                              onTap:
                                  () => setState(
                                    () =>
                                        _selectedFilter =
                                            NotificationFilter.security,
                                  ),
                            ),
                            SizedBox(width: 8.w),
                            _FilterChip(
                              label: 'Updates',
                              isSelected:
                                  _selectedFilter == NotificationFilter.general,
                              onTap:
                                  () => setState(
                                    () =>
                                        _selectedFilter =
                                            NotificationFilter.general,
                                  ),
                            ),
                          ],
                        ),
                      ),
                    ),

                    // Notification items list
                    Expanded(
                      child:
                          filtered.isEmpty
                              ? _EmptyNotificationsView(filter: _selectedFilter)
                              : ListView.separated(
                                physics: const BouncingScrollPhysics(),
                                padding: EdgeInsets.fromLTRB(
                                  16.w,
                                  8.h,
                                  16.w,
                                  24.h,
                                ),
                                itemCount: filtered.length,
                                separatorBuilder:
                                    (_, __) => SizedBox(height: 10.h),
                                itemBuilder: (context, index) {
                                  final item = filtered[index];
                                  return _NotificationCard(
                                    item: item,
                                    onTap: () => _showNotificationDetails(item),
                                  );
                                },
                              ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NotificationCard extends StatelessWidget {
  final AppNotificationModel item;
  final VoidCallback onTap;

  const _NotificationCard({required this.item, required this.onTap});

  @override
  Widget build(BuildContext context) {
    const brandColor = Color(0xFF34544A);
    const successGreen = Color(0xFF1DBF73);

    Color iconColor;
    IconData icon;
    switch (item.type.toLowerCase()) {
      case 'credit':
        iconColor = successGreen;
        icon = Icons.arrow_downward_rounded;
        break;
      case 'debit':
        iconColor = brandColor;
        icon = Icons.arrow_upward_rounded;
        break;
      case 'bill':
        iconColor = const Color(0xFFE65100);
        icon = Icons.receipt_rounded;
        break;
      case 'security':
        iconColor = const Color(0xFF1E3A8A);
        icon = Icons.shield_rounded;
        break;
      default:
        iconColor = brandColor;
        icon = Icons.notifications_active_rounded;
    }

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16.r),
      child: Container(
        padding: EdgeInsets.all(14.w),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16.r),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.03),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
          border: Border.all(
            color:
                item.isRead ? Colors.transparent : brandColor.withOpacity(0.2),
            width: 1,
          ),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: 44.w,
              height: 44.h,
              decoration: BoxDecoration(
                color: iconColor.withOpacity(0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: iconColor, size: 22.sp),
            ),
            SizedBox(width: 12.w),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Expanded(
                        child: Text(
                          item.title,
                          style: TextStyle(
                            fontSize: 14.5.sp,
                            fontWeight:
                                item.isRead ? FontWeight.w600 : FontWeight.bold,
                            color: const Color(0xFF1A1A1A),
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (!item.isRead)
                        Container(
                          width: 8.w,
                          height: 8.h,
                          decoration: const BoxDecoration(
                            color: successGreen,
                            shape: BoxShape.circle,
                          ),
                        ),
                    ],
                  ),
                  SizedBox(height: 4.h),
                  Text(
                    item.body,
                    style: TextStyle(
                      fontSize: 12.5.sp,
                      color: Colors.grey[600],
                      height: 1.35,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  SizedBox(height: 8.h),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        _formatTime(item.timestamp),
                        style: TextStyle(
                          fontSize: 11.5.sp,
                          color: Colors.grey[500],
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      Row(
                        children: [
                          Text(
                            'View Details',
                            style: TextStyle(
                              fontSize: 11.5.sp,
                              fontWeight: FontWeight.bold,
                              color: brandColor,
                            ),
                          ),
                          SizedBox(width: 2.w),
                          Icon(
                            Icons.arrow_forward_ios_rounded,
                            size: 10.sp,
                            color: brandColor,
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatTime(DateTime dt) {
    final now = DateTime.now();
    final diff = now.difference(dt);
    if (diff.inMinutes < 2) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    if (diff.inDays == 1) return 'Yesterday';
    return '${dt.day}/${dt.month}/${dt.year}';
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    const brandColor = Color(0xFF34544A);

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20.r),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 7.h),
        decoration: BoxDecoration(
          color: isSelected ? brandColor : Colors.white,
          borderRadius: BorderRadius.circular(20.r),
          border: Border.all(
            color: isSelected ? brandColor : Colors.grey[300]!,
          ),
          boxShadow:
              isSelected
                  ? [
                    BoxShadow(
                      color: brandColor.withOpacity(0.25),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ]
                  : null,
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12.5.sp,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
            color: isSelected ? Colors.white : Colors.grey[700],
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);

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
        SizedBox(width: 12.w),
        Flexible(
          child: Text(
            value,
            textAlign: TextAlign.right,
            style: TextStyle(
              fontSize: 13.5.sp,
              fontWeight: FontWeight.w600,
              color: const Color(0xFF1A1A1A),
            ),
          ),
        ),
      ],
    );
  }
}

class _EmptyNotificationsView extends StatelessWidget {
  final NotificationFilter filter;

  const _EmptyNotificationsView({required this.filter});

  @override
  Widget build(BuildContext context) {
    const brandColor = Color(0xFF34544A);

    return Center(
      child: Padding(
        padding: EdgeInsets.symmetric(horizontal: 32.w),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 76.w,
              height: 76.h,
              decoration: BoxDecoration(
                color: brandColor.withOpacity(0.08),
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.notifications_none_rounded,
                size: 40.sp,
                color: brandColor,
              ),
            ),
            SizedBox(height: 18.h),
            Text(
              'No Notifications',
              style: TextStyle(
                fontSize: 18.sp,
                fontWeight: FontWeight.bold,
                color: const Color(0xFF1A1A1A),
              ),
            ),
            SizedBox(height: 6.h),
            Text(
              'You have no notifications in this category. Transaction updates and alerts will appear here.',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 13.sp,
                color: Colors.grey[600],
                height: 1.4,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
