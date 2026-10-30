// lib/screen/activity/activity_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/transaction_provider.dart';
import '../../models/transaction/transaction_model.dart';
import '../../core/utils/formatters.dart';
import '../transaction/transaction_receipt_screen.dart';

class ActivityScreen extends ConsumerStatefulWidget {
  const ActivityScreen({super.key});

  @override
  ConsumerState<ActivityScreen> createState() => _ActivityScreenState();
}

class _ActivityScreenState extends ConsumerState<ActivityScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _search = '';
  DateTime? _startDate;
  DateTime? _endDate;
  final ScrollController _scrollCtrl = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(transactionNotifierProvider.notifier).getTransactions();
    });
    _scrollCtrl.addListener(() {
      if (_scrollCtrl.position.pixels >=
          _scrollCtrl.position.maxScrollExtent - 200) {
        final txState = ref.read(transactionNotifierProvider);
        if (txState.hasMore && !txState.isLoadingMore) {
          ref.read(transactionNotifierProvider.notifier).loadMore(
            search:    _search.isEmpty ? null : _search,
            startDate: _startDate != null ? _fmtDate(_startDate!) : null,
            endDate:   _endDate   != null ? _fmtDate(_endDate!)   : null,
          );
        }
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollCtrl.dispose();
    super.dispose();
  }

  // ── Search ────────────────────────────────────────────────────────────────
  void _onSearch(String value) {
    setState(() => _search = value);
    if (value.length >= 2) {
      ref.read(transactionNotifierProvider.notifier).getTransactions(
            search: value,
            startDate: _startDate != null ? _fmtDate(_startDate!) : null,
            endDate:   _endDate   != null ? _fmtDate(_endDate!)   : null,
          );
    } else if (value.isEmpty) {
      _refresh();
    }
  }

  Future<void> _refresh() async {
    ref.read(transactionNotifierProvider.notifier).getTransactions(
          search:    _search.isEmpty ? null : _search,
          startDate: _startDate != null ? _fmtDate(_startDate!) : null,
          endDate:   _endDate   != null ? _fmtDate(_endDate!)   : null,
        );
  }

  String _fmtDate(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  // ── Filter bottom sheet ───────────────────────────────────────────────────
  void _showFilterSheet() {
    DateTime? tempStart = _startDate;
    DateTime? tempEnd   = _endDate;

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) {
          Future<void> pickDate(bool isStart) async {
            final picked = await showDatePicker(
              context: ctx,
              initialDate: isStart
                  ? (tempStart ?? DateTime.now())
                  : (tempEnd ?? DateTime.now()),
              firstDate: DateTime(2020),
              lastDate:  DateTime.now(),
              builder: (context, child) => Theme(
                data: ThemeData.light().copyWith(
                  colorScheme: const ColorScheme.light(
                    primary: Color(0xFF34544A),
                    onPrimary: Colors.white,
                  ),
                ),
                child: child!,
              ),
            );
            if (picked == null) return;
            setSheet(() {
              if (isStart) tempStart = picked;
              else         tempEnd   = picked;
            });
          }

          String _display(DateTime? d) {
            if (d == null) return '';
            return '${d.day.toString().padLeft(2, '0')} / '
                '${d.month.toString().padLeft(2, '0')} / ${d.year}';
          }

          return Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 36),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Handle
                Center(
                  child: Container(
                    width: 40, height: 4,
                    decoration: BoxDecoration(
                        color: Colors.grey[300],
                        borderRadius: BorderRadius.circular(2)),
                  ),
                ),
                const SizedBox(height: 20),
                const Text('Select Date Range',
                    style: TextStyle(
                        fontSize: 20, fontWeight: FontWeight.bold)),
                const SizedBox(height: 24),

                // Start date
                const Text('Start Date',
                    style: TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 15)),
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: () => pickDate(true),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAF9),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                          color: tempStart != null
                              ? const Color(0xFF34544A)
                              : Colors.grey[300]!),
                    ),
                    child: Row(children: [
                      Expanded(
                        child: Text(
                          tempStart != null
                              ? _display(tempStart)
                              : 'Select start date',
                          style: TextStyle(
                              color: tempStart != null
                                  ? Colors.black87
                                  : Colors.grey[500],
                              fontSize: 15),
                        ),
                      ),
                      Icon(Icons.calendar_month_rounded,
                          color: const Color(0xFF34544A), size: 22),
                    ]),
                  ),
                ),
                const SizedBox(height: 20),

                // End date
                const Text('End Date',
                    style: TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 15)),
                const SizedBox(height: 8),
                GestureDetector(
                  onTap: () => pickDate(false),
                  child: Container(
                    width: double.infinity,
                    padding: const EdgeInsets.symmetric(
                        horizontal: 16, vertical: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAF9),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(
                          color: tempEnd != null
                              ? const Color(0xFF34544A)
                              : Colors.grey[300]!),
                    ),
                    child: Row(children: [
                      Expanded(
                        child: Text(
                          tempEnd != null
                              ? _display(tempEnd)
                              : 'Select end date',
                          style: TextStyle(
                              color: tempEnd != null
                                  ? Colors.black87
                                  : Colors.grey[500],
                              fontSize: 15),
                        ),
                      ),
                      Icon(Icons.calendar_month_rounded,
                          color: const Color(0xFF34544A), size: 22),
                    ]),
                  ),
                ),
                const SizedBox(height: 28),

                // Buttons
                Row(children: [
                  // Clear filter
                  if (_startDate != null || _endDate != null)
                    Expanded(
                      child: OutlinedButton(
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: Color(0xFF34544A)),
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(32)),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        onPressed: () {
                          setState(() {
                            _startDate = null;
                            _endDate   = null;
                          });
                          Navigator.pop(ctx);
                          _refresh();
                        },
                        child: const Text('Clear',
                            style: TextStyle(
                                color: Color(0xFF34544A),
                                fontWeight: FontWeight.bold)),
                      ),
                    ),
                  if (_startDate != null || _endDate != null)
                    const SizedBox(width: 12),
                  Expanded(
                    flex: 2,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF34544A),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(32)),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      onPressed: () {
                        setState(() {
                          _startDate = tempStart;
                          _endDate   = tempEnd;
                        });
                        Navigator.pop(ctx);
                        _refresh();
                      },
                      child: const Text('Apply Filter',
                          style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 16)),
                    ),
                  ),
                ]),
              ],
            ),
          );
        },
      ),
    );
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  Color _statusColor(String status) {
    switch (status.toLowerCase()) {
      case 'success':    return const Color(0xFF1DBF73);
      case 'refund':     return const Color(0xFF2196F3);
      case 'failed':
      case 'unsuccessful': return const Color(0xFFF44336);
      case 'pending':    return Colors.orange;
      default:           return Colors.grey;
    }
  }

  // type field is "0" (debit) or "1" (credit) — use service_type or description
  String _txTitle(TransactionModel tx) {
    // service_type holds the human-readable name e.g. "Airtime", "Currency Conversion"
    final serviceType = tx.metadata?['service_type']?.toString() ?? '';
    if (serviceType.isNotEmpty) return serviceType;
    final service = tx.metadata?['service']?.toString() ?? '';
    if (service.isNotEmpty && service != 'System') return service;
    // Fallback: parse from description
    if (tx.description != null && tx.description!.isNotEmpty) {
      final desc = tx.description!;
      if (desc.toLowerCase().contains('convert')) return 'Currency Conversion';
      if (desc.toLowerCase().contains('airtime')) return 'Airtime';
      if (desc.toLowerCase().contains('data'))    return 'Data';
      if (desc.toLowerCase().contains('cable'))   return 'Cable TV';
      if (desc.toLowerCase().contains('bill') || desc.toLowerCase().contains('electric'))
        return 'Bill Payment';
      if (desc.toLowerCase().contains('transfer')) return 'Transfer';
      // Return a clean snippet of the description as title
      return desc.length > 30 ? '${desc.substring(0, 30)}...' : desc;
    }
    // Last resort: map "0"/"1" to readable
    if (tx.type == '0') return 'Debit Transaction';
    if (tx.type == '1') return 'Credit Transaction';
    return tx.type;
  }

  IconData _serviceIcon(TransactionModel tx) {
    // Status drives the icon — success / pending / failed / refund each get
    // their own glyph, tinted by _statusColor on the tile.
    switch (tx.status.toLowerCase()) {
      case 'success':      return Icons.check_circle;
      case 'failed':
      case 'unsuccessful': return Icons.cancel;
      case 'pending':      return Icons.access_time;
      case 'refund':       return Icons.replay_circle_filled;
    }
    final title = _txTitle(tx).toLowerCase();
    if (title.contains('airtime'))    return Icons.phone_android;
    if (title.contains('data'))       return Icons.wifi;
    if (title.contains('transfer') || title.contains('p2p')) return Icons.send;
    if (title.contains('card'))       return Icons.credit_card;
    if (title.contains('cable'))      return Icons.tv;
    if (title.contains('bill') || title.contains('electric')) return Icons.bolt;
    if (title.contains('wallet') || title.contains('fund'))
      return Icons.account_balance_wallet;
    if (title.contains('pos'))        return Icons.point_of_sale;
    if (title.contains('conver'))     return Icons.swap_horiz;
    if (tx.type == '1')               return Icons.arrow_downward; // credit
    if (tx.type == '0')               return Icons.arrow_upward;   // debit
    return Icons.receipt_long;
  }

  // Credit (type=1) → positive green; Debit (type=0) → red
  Color _amountColor(TransactionModel tx) {
    if (tx.type == '1') return const Color.fromARGB(255, 0, 0, 0);
    if (tx.type == '0') return const Color.fromARGB(255, 0, 0, 0);
    return Colors.black87;
  }

  String _amountPrefix(TransactionModel tx) =>
      tx.type == '1' ? '+' : tx.type == '0' ? '-' : '';

  String _formatDate(DateTime date) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun',
                    'Jul','Aug','Sep','Oct','Nov','Dec'];
    return '${date.day} ${months[date.month - 1]}';
  }

  void _openReceipt(TransactionModel tx) {
    Navigator.push(context, MaterialPageRoute(
      builder: (_) => TransactionReceiptScreen(
        data: TransactionReceiptData(
          service:        _txTitle(tx),
          type:           tx.type == '0' ? 'Debit' : tx.type == '1' ? 'Credit' : tx.type,
          description:    tx.description ?? '',
          amount:         '₦${tx.amount}',
          status:         tx.status,
          reference:      tx.reference ?? tx.id,
          sessionId:      tx.metadata?['session_id']?.toString(),
          initialBalance: tx.metadata?['initial_balance']?.toString() ?? '',
          finalBalance:   tx.metadata?['final_balance']?.toString() ?? '',
          date:           tx.createdAt,
          phoneNumber:    tx.metadata?['phone_number']?.toString(),
        ),
      ),
    ));
  }

  bool get _hasFilter => _startDate != null || _endDate != null;

  @override
  Widget build(BuildContext context) {
    final txState      = ref.watch(transactionNotifierProvider);
    final transactions = txState.transactions ?? [];

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Text('Transaction History',
              style: TextStyle(
                  fontSize: 25.sp,
                  fontWeight: FontWeight.w700,
                  color: Colors.white)),
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
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20))),
                  child: Column(children: [
                    SizedBox(height: 20.h),

             

                    // ── Filter button row ───────────────────────────────────
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          // Active filter chip
                          if (_hasFilter)
                            Container(
                              margin: const EdgeInsets.only(right: 8),
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: const Color(0xFF34544A).withOpacity(0.1),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Row(children: [
                                Text(
                                  _startDate != null && _endDate != null
                                      ? '${_formatDate(_startDate!)} – ${_formatDate(_endDate!)}'
                                      : _startDate != null
                                          ? 'From ${_formatDate(_startDate!)}'
                                          : 'To ${_formatDate(_endDate!)}',
                                  style: const TextStyle(
                                      fontSize: 12,
                                      color: Color(0xFF34544A),
                                      fontWeight: FontWeight.w600),
                                ),
                                const SizedBox(width: 4),
                                GestureDetector(
                                  onTap: () {
                                    setState(() {
                                      _startDate = null;
                                      _endDate   = null;
                                    });
                                    _refresh();
                                  },
                                  child: const Icon(Icons.close,
                                      size: 14, color: Color(0xFF34544A)),
                                ),
                              ]),
                            ),
                          // Filter button
                          GestureDetector(
                            onTap: _showFilterSheet,
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 16, vertical: 10),
                              decoration: BoxDecoration(
                                color: _hasFilter
                                    ? const Color(0xFF34544A)
                                    : Colors.white,
                                borderRadius: BorderRadius.circular(32),
                                border: Border.all(
                                    color: const Color(0xFF34544A),
                                    width: 1.5),
                              ),
                              child: Row(children: [
                                Icon(Icons.tune_rounded,
                                    size: 16,
                                    color: _hasFilter
                                        ? Colors.white
                                        : const Color(0xFF34544A)),
                                const SizedBox(width: 6),
                                Text('Filter Transaction',
                                    style: TextStyle(
                                        color: _hasFilter
                                            ? Colors.white
                                            : const Color(0xFF34544A),
                                        fontWeight: FontWeight.bold,
                                        fontSize: 13)),
                              ]),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 12),

                    // ── Transaction list ────────────────────────────────────
                    Expanded(
                      child: txState.isLoading
                          ? const Center(child: CircularProgressIndicator(
                              color: Color(0xFF34544A)))
                          : txState.error != null
                              ? _ErrorView(
                                  message: txState.error!,
                                  onRetry: _refresh)
                              : transactions.isEmpty
                                  ? _EmptyView(
                                      search: _search,
                                      hasFilter: _hasFilter)
                                  : RefreshIndicator(
                                      color: const Color(0xFF34544A),
                                      onRefresh: _refresh,
                                      child: ListView.builder(
                                        controller: _scrollCtrl,
                                        padding: const EdgeInsets.only(bottom: 80),
                                        itemCount: transactions.length + 1,
                                        itemBuilder: (_, i) {
                                          // Last item = load-more footer
                                          if (i == transactions.length) {
                                            final s = ref.watch(transactionNotifierProvider);
                                            if (s.isLoadingMore) {
                                              return const Padding(
                                                padding: EdgeInsets.all(20),
                                                child: Center(
                                                  child: CircularProgressIndicator(
                                                    color: Color(0xFF34544A),
                                                    strokeWidth: 2,
                                                  ),
                                                ),
                                              );
                                            }
                                            if (s.hasMore) {
                                              return Padding(
                                                padding: const EdgeInsets.symmetric(
                                                    vertical: 16, horizontal: 40),
                                                child: ElevatedButton(
                                                  style: ElevatedButton.styleFrom(
                                                    backgroundColor: const Color(0xFF34544A),
                                                    shape: RoundedRectangleBorder(
                                                        borderRadius: BorderRadius.circular(32)),
                                                  ),
                                                  onPressed: () => ref
                                                      .read(transactionNotifierProvider.notifier)
                                                      .loadMore(
                                                        search:    _search.isEmpty ? null : _search,
                                                        startDate: _startDate != null ? _fmtDate(_startDate!) : null,
                                                        endDate:   _endDate   != null ? _fmtDate(_endDate!)   : null,
                                                      ),
                                                  child: const Text('Load More',
                                                      style: TextStyle(color: Colors.white)),
                                                ),
                                              );
                                            }
                                            return const Padding(
                                              padding: EdgeInsets.all(16),
                                              child: Center(
                                                child: Text('All transactions loaded',
                                                    style: TextStyle(
                                                        color: Colors.grey, fontSize: 12)),
                                              ),
                                            );
                                          }
                                          final tx = transactions[i];
                                          return _TransactionTile(
                                            title:    _txTitle(tx),
                                            tx:       tx,
                                            statusColor: _statusColor(tx.status),
                                            icon:     _serviceIcon(tx),
                                            amountColor: _amountColor(tx),
                                            prefix:   _amountPrefix(tx),
                                            formattedDate: _formatDate(tx.createdAt),
                                            onTap: () => _openReceipt(tx),
                                          );
                                        },
                                      ),
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

// ─── Tile ─────────────────────────────────────────────────────────────────────

class _TransactionTile extends StatelessWidget {
  final TransactionModel tx;
  final String title;
  final Color statusColor;
  final IconData icon;
  final Color amountColor;
  final String prefix;
  final String formattedDate;
  final VoidCallback onTap;

  const _TransactionTile({
    required this.tx,
    required this.title,
    required this.statusColor,
    required this.icon,
    required this.amountColor,
    required this.prefix,
    required this.formattedDate,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 5),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: const [
                BoxShadow(
                    color: Colors.black12,
                    blurRadius: 4,
                    offset: Offset(0, 2))
              ]),
          child: ListTile(
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            leading: CircleAvatar(
              backgroundColor: statusColor.withOpacity(0.12),
              radius: 24,
              child: Icon(icon, color: statusColor, size: 20),
            ),
            title: Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.bold, fontSize: 14),
                maxLines: 1,
                overflow: TextOverflow.ellipsis),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (tx.description != null && tx.description!.isNotEmpty)
                  Text(tx.description!,
                      style: TextStyle(
                          fontSize: 12, color: Colors.grey[600]),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis),
                const SizedBox(height: 4),
                Row(children: [
                  Text('$prefix${formatCurrency(tx.amount)}',
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                          color: amountColor)),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                        color: statusColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(6)),
                    child: Text(tx.status,
                        style: TextStyle(
                            color: statusColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 11)),
                  ),
                ]),
              ],
            ),
            trailing: Text(formattedDate,
                style: const TextStyle(
                    fontWeight: FontWeight.w500,
                    fontSize: 12,
                    color: Colors.grey)),
          ),
        ),
      ),
    );
  }
}

// ─── Empty ────────────────────────────────────────────────────────────────────

class _EmptyView extends StatelessWidget {
  final String search;
  final bool hasFilter;
  const _EmptyView({required this.search, this.hasFilter = false});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
        Icon(Icons.receipt_long_outlined, size: 64, color: Colors.grey[400]),
        const SizedBox(height: 16),
        Text(
          hasFilter
              ? 'No transactions in this date range'
              : search.isNotEmpty
                  ? 'No results for "$search"'
                  : 'No transactions yet',
          style: TextStyle(
              fontSize: 16,
              color: Colors.grey[600],
              fontWeight: FontWeight.w500),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(
          hasFilter
              ? 'Try a different date range'
              : search.isNotEmpty
                  ? 'Try a different search term'
                  : 'Your transaction history will appear here',
          style: TextStyle(fontSize: 13, color: Colors.grey[400]),
        ),
      ]),
    );
  }
}

// ─── Error ────────────────────────────────────────────────────────────────────

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
        Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
        const SizedBox(height: 16),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Text(message,
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600], fontSize: 14)),
        ),
        const SizedBox(height: 20),
        ElevatedButton.icon(
          onPressed: onRetry,
          icon: const Icon(Icons.refresh),
          label: const Text('Retry'),
          style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF34544A),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12))),
        ),
      ]),
    );
  }
}