// lib/screen/send_money/p2p_screen.dart
// Replace the existing file with this version.

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/auth_provider.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/transfer_provider.dart';
import '../../providers/user_provider.dart';
import '../transaction/transaction_summary_screen.dart';
import '../../core/utils/formatters.dart';

class P2PScreen extends ConsumerStatefulWidget {
  final String? prefilledAmount;
  final String? prefilledMessage;
  final Map<String, dynamic>? preselectedUser;

  const P2PScreen({
    Key? key,
    this.prefilledAmount,
    this.prefilledMessage,
    this.preselectedUser,
  }) : super(key: key);

  @override
  ConsumerState<P2PScreen> createState() => _P2PScreenState();
}

class _P2PScreenState extends ConsumerState<P2PScreen> {
  final TextEditingController _searchController = TextEditingController();
  String _search = '';
  bool _isSearching = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(userNotifierProvider.notifier).loadAllUsers();
      // If a user was pre-selected (from QR scan), open send dialog immediately
      if (widget.preselectedUser != null) {
        _onSendToUser(widget.preselectedUser!);
      }
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _onSearchChanged(String val) async {
    setState(() => _search = val);
    if (val.length >= 1) {
      setState(() => _isSearching = true);
      await ref.read(userNotifierProvider.notifier).searchUsers(val);
      setState(() => _isSearching = false);
    } else if (val.isEmpty) {
      // Reset to initial list
      ref.read(userNotifierProvider.notifier).loadAllUsers();
    }
  }

  void _onSendToUser(Map<String, dynamic> user) {
    // If no amount was prefilled, ask for one first
    if (widget.prefilledAmount == null || widget.prefilledAmount!.isEmpty) {
      _showAmountDialog(user);
      return;
    }
    _navigateToSummary(user, widget.prefilledAmount!, widget.prefilledMessage ?? '');
  }

  void _showAmountDialog(Map<String, dynamic> user) {
    final amountCtrl = TextEditingController();
    final messageCtrl = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheetState) {
          // Live balance check — recomputed on every keystroke.
          final user_ = ref.read(currentUserProvider);
          final balance = double.tryParse(user_?.balance ?? '0') ?? 0;
          final amt =
              double.tryParse(amountCtrl.text.replaceAll(',', '')) ?? 0;
          final insufficient = amt > 0 && amt > balance;
          final canContinue = amt > 0 && !insufficient;

          return Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(ctx).viewInsets.bottom,
              top: 24,
              left: 16,
              right: 16,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Text(
                  'Send to ${user['name'] ?? user['username']}',
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 18),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: amountCtrl,
                  keyboardType: TextInputType.number,
                  // Digits only + live thousands separators (e.g. 10,000).
                  inputFormatters: [_ThousandsSeparatorFormatter()],
                  onChanged: (_) => setSheetState(() {}),
                  decoration: InputDecoration(
                    labelText: 'Amount (₦)',
                    border: const OutlineInputBorder(),
                    // Inline error so the user sees it as they type, not just
                    // on submit.
                    errorText: insufficient
                        ? 'Insufficient balance'
                        : null,
                  ),
                ),
                const SizedBox(height: 6),
                Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Available Balance: ₦${formatNumber(balance.toStringAsFixed(2))}',
                    style: TextStyle(
                      fontSize: 12,
                      color: insufficient ? Colors.red : Colors.grey[600],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: messageCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Message (Optional)',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF34544A),
                      disabledBackgroundColor: Colors.grey[400],
                    ),
                    onPressed: !canContinue
                        ? null
                        : () {
                            final cleanAmount =
                                amountCtrl.text.replaceAll(',', '');
                            Navigator.pop(ctx);
                            _navigateToSummary(
                                user, cleanAmount, messageCtrl.text);
                          },
                    child: const Text('Continue',
                        style: TextStyle(color: Colors.white)),
                  ),
                ),
                const SizedBox(height: 16),
              ],
            ),
          );
        },
      ),
    );
  }

  void _navigateToSummary(
    Map<String, dynamic> user,
    String amount,
    String message,
  ) {
    // Strip any thousands separators so the backend always receives a clean
    // numeric string (covers prefilled / QR amounts too).
    final cleanAmount = amount.replaceAll(',', '');
    final receiverId =
        user['wallet_id']?.toString() ?? user['id']?.toString() ?? '';
    final name = user['name'] ?? user['username'] ?? 'User';

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TransactionSummaryScreen(
          data: TransactionSummaryData(
            service: 'P2P Transfer',
            from: 'Wallet',
            accountName: name,
            amount: '₦${formatNumber(cleanAmount)}',
            description: message,
            onConfirm: (pin) async {
              final response = await ref
                  .read(transferNotifierProvider.notifier)
                  .fundTransfer(
                    receiverId: receiverId,
                    amount: cleanAmount,
                    comment: message.isEmpty ? null : message,
                    transactionPin: pin,
                  );
              return {
                'success': response.success,
                'message': response.message,
                'reference': response.data?['reference'] ?? '',
                'description': 'Transfer to $name',
                'initial_balance':
                    response.data?['initial_balance'] ?? '',
                'final_balance': response.data?['final_balance'] ?? '',
              };
            },
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final userState   = ref.watch(userNotifierProvider);
    final currentUser = ref.watch(currentUserProvider);
    final walletBalance = double.tryParse(currentUser?.balance ?? '0') ?? 0;
    final hasZeroBalance = walletBalance <= 0;

    final users = userState.users ?? [];
    // Only show results when user has searched
    final filtered = _search.isEmpty ? [] : users;

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
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'Send Money',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 22.sp,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.refresh, color: Colors.white),
                  onPressed: () =>
                      ref.read(userNotifierProvider.notifier).loadAllUsers(),
                ),
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
                    child: Column(
                      children: [
                        SizedBox(height: 30.h),

                        // ── Balance display & zero check ─────────────────────
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: hasZeroBalance
                                  ? Colors.red.withOpacity(0.08)
                                  : const Color(0xFF34544A).withOpacity(0.06),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(
                                color: hasZeroBalance
                                    ? Colors.red.withOpacity(0.3)
                                    : const Color(0xFF34544A).withOpacity(0.2),
                              ),
                            ),
                            child: Row(children: [
                              Icon(
                                hasZeroBalance
                                    ? Icons.warning_amber_rounded
                                    : Icons.account_balance_wallet_outlined,
                                size: 16,
                                color: hasZeroBalance
                                    ? Colors.red[700]
                                    : const Color(0xFF34544A),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                hasZeroBalance
                                    ? 'Insufficient balance — please fund your wallet'
                                    : 'Available Balance: ₦${formatNumber(walletBalance.toStringAsFixed(2))}',
                                    
                                style: TextStyle(
                                  fontSize: 12.sp,
                                  color: hasZeroBalance
                                      ? Colors.red[700]
                                      : const Color(0xFF34544A),
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ]),
                          ),
                        ),
                        SizedBox(height: 12.h),

                        Padding(
                          padding:
                              const EdgeInsets.symmetric(horizontal: 20.0),
                          child: TextField(
                            controller: _searchController,
                            onChanged: _onSearchChanged,
                            decoration: InputDecoration(
                              prefixIcon:
                                  Icon(Icons.search, color: Colors.grey[600]),
                              hintText:
                                  'Search by phone number, username...',
                              hintStyle:
                                  TextStyle(color: Colors.grey[500]),
                              filled: true,
                              fillColor: const Color(0xFFF8FAF9),
                              border: OutlineInputBorder(
                                borderRadius:
                                    BorderRadius.circular(12.r),
                                borderSide: BorderSide.none,
                              ),
                              enabledBorder: OutlineInputBorder(
                                borderRadius:
                                    BorderRadius.circular(12.r),
                                borderSide: BorderSide.none,
                              ),
                              focusedBorder: OutlineInputBorder(
                                borderRadius:
                                    BorderRadius.circular(12.r),
                                borderSide: const BorderSide(
                                  color: Color(0xFF34544A),
                                  width: 1.5,
                                ),
                              ),
                              contentPadding: EdgeInsets.symmetric(
                                vertical: 18.h,
                                horizontal: 16.w,
                              ),
                            ),
                          ),
                        ),
                        SizedBox(height: 12.h),

                        if (userState.isLoading || _isSearching)
                          const Expanded(
                            child: Center(
                              child: CircularProgressIndicator(
                                  color: Color(0xFF34544A)),
                            ),
                          )
                        else if (filtered.isEmpty)
                          Expanded(
                            child: Center(
                              child: Text(
                                _search.isEmpty
                                    ? 'No users found'
                                    : 'No results for "$_search"',
                                style: TextStyle(
                                  color: Colors.grey[500],
                                  fontSize: 16.sp,
                                ),
                              ),
                            ),
                          )
                        else
                          Expanded(
                            child: ListView.separated(
                              padding: const EdgeInsets.symmetric(
                                  vertical: 8),
                              itemCount: filtered.length,
                              separatorBuilder: (_, __) =>
                                  const SizedBox(height: 0),
                              itemBuilder: (context, i) {
                                final user = filtered[i];
                                final name = user['name']?.toString() ??
                                    '${user['first_name'] ?? ''} ${user['last_name'] ?? ''}'.trim();
                                final username =
                                    user['username']?.toString() ??
                                        user['phone_number']?.toString() ??
                                        '';
                                return Opacity(
                                  opacity: hasZeroBalance ? 0.5 : 1.0,
                                  child: Container(
                                    color: Colors.white,
                                    margin: const EdgeInsets.symmetric(
                                        vertical: 4, horizontal: 8),
                                    child: ListTile(
                                      leading: CircleAvatar(
                                        backgroundColor: Colors.grey[300],
                                        child: Text(
                                          name.isNotEmpty
                                              ? name[0].toUpperCase()
                                              : '?',
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            color: Color(0xFF34544A),
                                          ),
                                        ),
                                      ),
                                      title: Text(
                                        name,
                                        style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: Color(0xFF34544A),
                                        ),
                                      ),
                                      subtitle: Text(
                                        username,
                                        style: TextStyle(
                                            color: Colors.grey[700]),
                                      ),
                                      trailing: OutlinedButton(
                                        onPressed: hasZeroBalance
                                            ? null
                                            : () => _onSendToUser(user),
                                        style: OutlinedButton.styleFrom(
                                          foregroundColor:
                                              const Color(0xFF34544A),
                                          side: const BorderSide(
                                            color: Color(0xFF34544A),
                                          ),
                                          shape: RoundedRectangleBorder(
                                            borderRadius:
                                                BorderRadius.circular(8),
                                          ),
                                        ),
                                        child: const Text('Send'),
                                      ),
                                      onTap: hasZeroBalance
                                          ? null
                                          : () => _onSendToUser(user),
                                    ),
                                  ),
                                );
                              },
                            ),
                          ),
                      ],
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

String formatNumber(String val) {
  final n = double.tryParse(val);
  if (n == null) return val;
  return n.toStringAsFixed(2).replaceAllMapped(
        RegExp(r'(\d)(?=(\d{3})+\.)', caseSensitive: false),
        (m) => '${m[1]},',
      );
}
/// Live thousands-separator formatter for the amount field (e.g. 10,000).
/// Digits only — strips everything else, then re-groups. Matches the formatter
/// used on the airtime / bank-transfer amount inputs.
class _ThousandsSeparatorFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    final raw = newValue.text.replaceAll(RegExp(r'[^\d]'), '');
    if (raw.isEmpty) return newValue.copyWith(text: '');

    final formatted = NumberFormat('#,##0', 'en_US').format(int.parse(raw));
    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}