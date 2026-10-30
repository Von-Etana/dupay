// lib/screen/card/card_activity_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:intl/intl.dart';
import '../../providers/card_provider.dart';
import '../../providers/auth_provider.dart';

class CardActivityScreen extends ConsumerStatefulWidget {
  final VirtualCardModel card;
  const CardActivityScreen({super.key, required this.card});

  @override
  ConsumerState<CardActivityScreen> createState() =>
      _CardActivityScreenState();
}

class _CardActivityScreenState
    extends ConsumerState<CardActivityScreen> {
  OverlayEntry? _copyOverlay;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _copyOverlay?.remove();
    super.dispose();
  }

  void _load() {
    if (widget.card.id != null) {
      ref.read(cardNotifierProvider.notifier).fetchCardTransactions(
            cardId: widget.card.id!,
            currency: widget.card.currency,
          );
    }
  }

  // ── Copy banner ───────────────────────────────────────────────────────────
  void _showCopyBanner(String label) {
    _copyOverlay?.remove();
    _copyOverlay = OverlayEntry(
      builder: (_) => Positioned(
        top: MediaQuery.of(context).padding.top + 12,
        left: 24,
        right: 24,
        child: Material(
          color: Colors.transparent,
          child: Container(
            padding: const EdgeInsets.symmetric(
                horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              color: const Color(0xFF34544A),
              borderRadius: BorderRadius.circular(12),
              boxShadow: [
                BoxShadow(
                    color: Colors.black.withOpacity(0.18),
                    blurRadius: 12,
                    offset: const Offset(0, 4))
              ],
            ),
            child: Row(children: [
              const Icon(Icons.check_circle,
                  color: Colors.white, size: 18),
              const SizedBox(width: 10),
              Text('$label copied!',
                  style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 14)),
            ]),
          ),
        ),
      ),
    );
    Overlay.of(context).insert(_copyOverlay!);
    Future.delayed(const Duration(seconds: 2), () {
      _copyOverlay?.remove();
      _copyOverlay = null;
    });
  }

  void _copy(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    _showCopyBanner(label);
  }

  // ── Field helpers ─────────────────────────────────────────────────────────
  String _fmt(double v) => NumberFormat('#,##0.00').format(v);

  String _fmtInt(double v) => NumberFormat('#,##0').format(v);

  String _description(Map<String, dynamic> tx) {
    final s = tx['narrative']?.toString().trim() ??
        tx['narration']?.toString() ??
        tx['remark']?.toString() ??
        tx['description']?.toString() ??
        tx['merchant_name']?.toString() ??
        tx['merchant']?.toString() ??
        'Card Transaction';
    return s.isNotEmpty ? s : 'Card Transaction';
  }

  double _amount(Map<String, dynamic> tx) {
    final cents =
        double.tryParse(tx['centAmount']?.toString() ?? '');
    if (cents != null) return cents / 100;
    return double.tryParse(tx['amount']?.toString() ?? '0') ?? 0;
  }

  int _centAmount(Map<String, dynamic> tx) {
    return int.tryParse(tx['centAmount']?.toString() ?? '0') ?? 0;
  }

  bool _isVerify(Map<String, dynamic> tx) {
    final t = tx['type']?.toString().toLowerCase() ?? '';
    return t == 'authorization_verify' ||
        tx['method']?.toString() == 'verify';
  }

  bool _isDebit(Map<String, dynamic> tx) {
    final t = (tx['type']?.toString() ?? '').toLowerCase();
    if (t == 'debit' || t == '0') return true;
    if (t == 'credit' || t == '1') return false;
    final m = (tx['method']?.toString() ?? '').toLowerCase();
    if (m == 'topup') return false;
    if (m == 'spend' || m == 'withdrawal') return true;
    return _amount(tx) < 0;
  }

  // Full date+time for detail sheet
  String _fullDate(String? raw) {
    if (raw == null || raw.isEmpty) return 'N/A';
    try {
      final dt = DateTime.parse(raw).toLocal();
      return DateFormat('dd MMM yyyy, HH:mm').format(dt);
    } catch (_) {
      return raw;
    }
  }

  // Short date for list row (e.g. "4 June 2026")
  String _shortDate(Map<String, dynamic> tx) {
    final raw = tx['createdAt']?.toString() ??
        tx['date']?.toString() ??
        tx['trans_date']?.toString() ??
        tx['created_at']?.toString() ??
        '';
    if (raw.isEmpty) return '';
    try {
      final dt = DateTime.parse(raw).toLocal();
      return DateFormat('d MMMM yyyy').format(dt);
    } catch (_) {
      return raw;
    }
  }

  String _status(Map<String, dynamic> tx) =>
      tx['status']?.toString() ?? 'unknown';

  String _typeLabel(Map<String, dynamic> tx) {
    final t = tx['type']?.toString() ?? '';
    final m = tx['method']?.toString() ?? '';
    if (t == 'authorization_verify') return 'Authorization_verify';
    if (m == 'topup') return 'Top-up';
    if (m == 'withdrawal') return 'Withdrawal';
    if (m == 'spend') return 'Purchase';
    return t.isNotEmpty
        ? t[0].toUpperCase() + t.substring(1)
        : 'Transaction';
  }

  // Completed / Failed / Pending label
  String _statusLabel(String status) {
    switch (status.toLowerCase()) {
      case 'success':
      case 'successful':
      case 'approved':
        return 'Completed';
      case 'failed':
      case 'declined':
        return 'Failed';
      default:
        return 'Pending';
    }
  }

  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'success':
      case 'successful':
      case 'approved':
        return const Color(0xFF34544A);
      case 'failed':
      case 'declined':
        return Colors.red;
      default:
        return Colors.orange;
    }
  }

  double? _balanceAfterDollars(Map<String, dynamic> tx) {
    final raw = tx['cardBalanceAfter']?.toString();
    if (raw == null) return null;
    final cents = double.tryParse(raw);
    if (cents == null) return null;
    return cents / 100;
  }

  // ── Transaction detail bottom sheet ──────────────────────────────────────
  void _showDetail(Map<String, dynamic> tx, String sym) {
    final verify = _isVerify(tx);
    final debit = verify ? false : _isDebit(tx);
    final amount = _amount(tx);
    final cents = _centAmount(tx);
    final status = _status(tx);
    final balAfter = _balanceAfterDollars(tx);
    final currency =
        (tx['currency']?.toString() ?? widget.card.currency)
            .toUpperCase();

    final createdAt = tx['createdAt']?.toString() ??
        tx['created_at']?.toString();
    final updatedAt = tx['updatedAt']?.toString() ??
        tx['updated_at']?.toString();
    final reference = tx['reference']?.toString();
    final txId = tx['id']?.toString();
    final settlementId =
        tx['settlementTransactionId']?.toString();
    final authId =
        tx['authorizationTransactionId']?.toString();
    final txType = tx['transactionType']?.toString();

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.75,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (_, scrollCtrl) => Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius:
                BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: Column(children: [
            // Drag handle
            Container(
              margin: EdgeInsets.symmetric(vertical: 12.h),
              width: 40.w,
              height: 4.h,
              decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2)),
            ),
            // Header
            Padding(
              padding:
                  EdgeInsets.symmetric(horizontal: 20.w),
              child: Row(
                  mainAxisAlignment:
                      MainAxisAlignment.spaceBetween,
                  children: [
                Text('Transaction Details',
                    style: TextStyle(
                        fontSize: 18.sp,
                        fontWeight: FontWeight.bold)),
                IconButton(
                    onPressed: () =>
                        Navigator.of(context).pop(),
                    icon: Icon(Icons.close,
                        color: Colors.grey[500], size: 22)),
              ]),
            ),
            const Divider(height: 1),
            // Scrollable content
            Expanded(
              child: ListView(
                controller: scrollCtrl,
                padding: EdgeInsets.all(20.w),
                children: [
                  // ── Transaction Type ─────────────────────
                  _DetailBox(children: [
                    _DetailField(
                      label: 'Transaction Type',
                      value: txType?.isNotEmpty == true
                          ? txType!
                          : _typeLabel(tx),
                    ),
                  ]),
                  SizedBox(height: 12.h),

                  // ── Dates ────────────────────────────────
                  _DetailBox(children: [
                    _DetailField(
                      label: 'Created At',
                      value: _fullDate(createdAt),
                    ),
                    if (updatedAt != null &&
                        updatedAt != createdAt) ...[
                      const Divider(height: 20),
                      _DetailField(
                        label: 'Updated At',
                        value: _fullDate(updatedAt),
                      ),
                    ],
                  ]),
                  SizedBox(height: 20.h),

                  // ── Amounts ───────────────────────────────
                  Text('Amounts',
                      style: TextStyle(
                          fontSize: 16.sp,
                          fontWeight: FontWeight.bold)),
                  SizedBox(height: 10.h),
                  _DetailBox(children: [
                    _DetailField(
                      label: 'Amount',
                      value:
                          '$currency ${_fmt(amount)}',
                    ),
                    const Divider(height: 20),
                    _DetailField(
                      label: 'Cent Amount',
                      value: '$cents cents',
                    ),
                    if (balAfter != null) ...[
                      const Divider(height: 20),
                      _DetailField(
                        label: 'Balance After',
                        // Strowallet returns cents — already divided
                        value:
                            '$currency ${_fmt(balAfter)}',
                      ),
                    ],
                  ]),
                  SizedBox(height: 20.h),

                  // ── References ───────────────────────────
                  Text('References',
                      style: TextStyle(
                          fontSize: 16.sp,
                          fontWeight: FontWeight.bold)),
                  SizedBox(height: 10.h),
                  _DetailBox(children: [
                    if (reference?.isNotEmpty == true) ...[
                      _DetailField(
                        label: 'Reference',
                        value: reference!,
                        onCopy: () =>
                            _copy(reference, 'Reference'),
                      ),
                      const Divider(height: 20),
                    ],
                    if (txId?.isNotEmpty == true) ...[
                      _DetailField(
                        label: 'Transaction ID',
                        value: txId!,
                        onCopy: () =>
                            _copy(txId, 'Transaction ID'),
                      ),
                      const Divider(height: 20),
                    ],
                    _DetailField(
                      label: 'Settlement ID',
                      value: settlementId?.isNotEmpty == true
                          ? settlementId!
                          : 'N/A',
                      onCopy: settlementId?.isNotEmpty == true
                          ? () => _copy(
                              settlementId!,
                              'Settlement ID')
                          : null,
                    ),
                    if (authId?.isNotEmpty == true) ...[
                      const Divider(height: 20),
                      _DetailField(
                        label: 'Authorization ID',
                        value: authId!,
                        onCopy: () => _copy(
                            authId, 'Authorization ID'),
                      ),
                    ],
                  ]),
                  SizedBox(height: 32.h),
                ],
              ),
            ),
          ]),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cardState = ref.watch(cardNotifierProvider);
    final user = ref.watch(currentUserProvider);
    final card = widget.card;
    final txs = cardState.transactions;
    final sym = card.currency == 'NGN' ? '₦' : '\$';

    final displayBalance = card.currency == 'NGN'
        ? (double.tryParse(user?.balance ?? '0') ?? 0.0)
        : card.balance;

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          // ── Header ──────────────────────────────────────
          Row(children: [
            IconButton(
                icon: const Icon(Icons.arrow_back_ios,
                    color: Colors.white),
                onPressed: () => Navigator.of(context).pop()),
            Expanded(
                child: Text('Card activity',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 22.sp,
                        fontWeight: FontWeight.w700,
                        color: Colors.white))),
            IconButton(
              icon: const Icon(Icons.refresh,
                  color: Colors.white),
              onPressed: _load,
            ),
          ]),
          const SizedBox(height: 30),

          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(20),
                      topRight: Radius.circular(20))),
              child: Padding(
                padding: const EdgeInsets.only(top: 20),
                child: Container(
                  decoration: const BoxDecoration(
                      color: Color(0xFFF4F6F8),
                      borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20))),
                  child: Column(children: [
                    // ── Balance header ───────────────────────
                   
                    // ── Transaction list ─────────────────────
                    Expanded(
                      child: cardState.isLoading
                          ? const Center(
                              child:
                                  CircularProgressIndicator(
                                      color: Color(
                                          0xFF34544A)))
                          : txs.isEmpty
                              ? _EmptyState(
                                  onRefresh: _load)
                              : ListView.separated(
                                  padding: EdgeInsets.symmetric(
                                      horizontal: 16.w,
                                      vertical: 8.h),
                                  itemCount: txs.length,
                                  separatorBuilder:
                                      (_, __) => SizedBox(
                                          height: 8.h),
                                  itemBuilder: (_, i) {
                                    final tx = txs[i];
                                    final verify =
                                        _isVerify(tx);
                                    final debit = verify
                                        ? false
                                        : _isDebit(tx);
                                    final amount =
                                        _amount(tx);
                                    final status =
                                        _status(tx);
                                    final statusLabel =
                                        _statusLabel(
                                            status);
                                    final statusColor =
                                        _statusColor(
                                            status);

                                    return GestureDetector(
                                      onTap: () =>
                                          _showDetail(
                                              tx, sym),
                                      child: Container(
                                        padding: EdgeInsets
                                            .all(14.w),
                                        decoration:
                                            BoxDecoration(
                                          color:
                                              Colors.white,
                                          borderRadius:
                                              BorderRadius
                                                  .circular(
                                                      14.r),
                                          boxShadow: [
                                            BoxShadow(
                                              color: Colors
                                                  .black
                                                  .withOpacity(
                                                      0.04),
                                              blurRadius:
                                                  6,
                                              offset:
                                                  const Offset(
                                                      0,
                                                      2),
                                            )
                                          ],
                                        ),
                                        child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment
                                                    .start,
                                            children: [
                                          // Date
                                          Text(
                                            _shortDate(tx),
                                            style: TextStyle(
                                                fontSize:
                                                    13.sp,
                                                fontWeight:
                                                    FontWeight
                                                        .w600,
                                                color: Colors
                                                    .black87),
                                          ),
                                          SizedBox(
                                              height: 6.h),
                                          // Description + badge + amount
                                          Row(
                                              crossAxisAlignment:
                                                  CrossAxisAlignment
                                                      .center,
                                              children: [
                                            Expanded(
                                              child: Text(
                                                _description(
                                                    tx),
                                                style: TextStyle(
                                                    fontSize:
                                                        13.sp,
                                                    color: Colors
                                                        .grey[700]),
                                                maxLines:
                                                    2,
                                                overflow:
                                                    TextOverflow
                                                        .ellipsis,
                                              ),
                                            ),
                                            SizedBox(
                                                width:
                                                    8.w),
                                            // Status badge
                                            Container(
                                              padding: EdgeInsets.symmetric(
                                                  horizontal:
                                                      10.w,
                                                  vertical:
                                                      4.h),
                                              decoration: BoxDecoration(
                                                  color: statusColor
                                                      .withOpacity(
                                                          0.12),
                                                  borderRadius:
                                                      BorderRadius.circular(
                                                          20.r)),
                                              child: Text(
                                                statusLabel,
                                                style: TextStyle(
                                                    fontSize:
                                                        11.sp,
                                                    color:
                                                        statusColor,
                                                    fontWeight:
                                                        FontWeight
                                                            .w600),
                                              ),
                                            ),
                                            SizedBox(
                                                width:
                                                    8.w),
                                            // Amount + chevron
                                            Row(
                                                mainAxisSize:
                                                    MainAxisSize
                                                        .min,
                                                children: [
                                              Text(
                                                verify
                                                    ? '+\$0'
                                                    : '${debit ? '-' : '+'}$sym${_fmt(amount)}',
                                                style: TextStyle(
                                                    fontSize:
                                                        13.sp,
                                                    fontWeight:
                                                        FontWeight
                                                            .bold,
                                                    color: verify
                                                        ? Colors.grey
                                                        : debit
                                                            ? Colors.red
                                                            : const Color(0xFF34544A)),
                                              ),
                                              SizedBox(
                                                  width:
                                                      4.w),
                                              Icon(
                                                Icons
                                                    .chevron_right,
                                                size:
                                                    16.sp,
                                                color: Colors
                                                    .grey[400],
                                              ),
                                            ]),
                                          ]),
                                        ]),
                                      ),
                                    );
                                  },
                                ),
                    ),
                  ]),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }
}

// ── Detail box widget ─────────────────────────────────────────────────────────

class _DetailBox extends StatelessWidget {
  final List<Widget> children;
  const _DetailBox({required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: EdgeInsets.all(16.w),
      decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14.r),
          boxShadow: [
            BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 6,
                offset: const Offset(0, 2))
          ]),
      child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: children),
    );
  }
}

class _DetailField extends StatelessWidget {
  final String label;
  final String value;
  final VoidCallback? onCopy;
  const _DetailField(
      {required this.label,
      required this.value,
      this.onCopy});

  @override
  Widget build(BuildContext context) {
    return Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
      Expanded(
        child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
          Text(label,
              style: TextStyle(
                  fontSize: 12.sp, color: Colors.grey[500])),
          SizedBox(height: 4.h),
          Text(value,
              style: TextStyle(
                  fontSize: 14.sp,
                  fontWeight: FontWeight.w500,
                  color: Colors.black87)),
        ]),
      ),
      if (onCopy != null)
        GestureDetector(
          onTap: onCopy,
          child: Padding(
            padding: const EdgeInsets.only(left: 8, top: 2),
            child: Icon(Icons.copy_outlined,
                size: 18.sp, color: Colors.grey[500]),
          ),
        ),
    ]);
  }
}

// ── Empty state ───────────────────────────────────────────────────────────────

class _EmptyState extends StatelessWidget {
  final VoidCallback onRefresh;
  const _EmptyState({required this.onRefresh});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
        Icon(Icons.receipt_long_outlined,
            size: 64, color: Colors.grey[300]),
        const SizedBox(height: 16),
        Text('No transactions yet',
            style: TextStyle(
                fontSize: 16.sp, color: Colors.grey[500])),
        const SizedBox(height: 12),
       
      ]),
    );
  }
}