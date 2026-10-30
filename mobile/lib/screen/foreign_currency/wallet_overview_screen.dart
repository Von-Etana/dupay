// lib/screen/foreign_currency/wallet_overview_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:intl/intl.dart';
import '../../providers/foreign_currency_provider.dart';
import '../../providers/auth_provider.dart';
import '../../models/foreign_currency/foreign_account_model.dart';
import 'convert_currency_screen.dart';
import 'create_account_flow.dart';
import '../send_money/bank_transfer_screen.dart';

class WalletOverviewScreen extends ConsumerStatefulWidget {
  const WalletOverviewScreen({super.key});

  @override
  ConsumerState<WalletOverviewScreen> createState() =>
      _WalletOverviewScreenState();
}

class _WalletOverviewScreenState extends ConsumerState<WalletOverviewScreen>
    with TickerProviderStateMixin {

  final List<Map<String, dynamic>> _currencies = [
    {
      'flag': '🇺🇸', 'code': 'USD', 'symbol': '\$',
      'name': 'US Dollar',
      'gradient': [const Color(0xFF34544A), const Color(0xFF7CA183)],
    },
    {
      'flag': '🇪🇺', 'code': 'EUR', 'symbol': '€',
      'name': 'Euro',
      'gradient': [const Color(0xFF34544A), const Color(0xFF7CA183)],
    },

  ];

  int _selected = 0;
  late PageController _pageController;
  bool _balanceHidden = false;

  @override
  void initState() {
    super.initState();
    _pageController = PageController(viewportFraction: 0.88);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(authNotifierProvider.notifier).refreshBalances();
      for (final c in _currencies) {
        ref.read(foreignCurrencyNotifierProvider.notifier).fetchAccount(c['code']);
      }
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  String _fmt(double v) => NumberFormat('#,##0.00', 'en_US').format(v);

  /// Opens the converter and re-pulls balances when it closes.
  ///
  /// `Navigator.push` was previously fire-and-forget, so returning from a
  /// successful conversion left this screen rendering the balances it was
  /// built with.
  Future<void> _openConverter(String code) async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => ConvertCurrencyScreen(initialFromCurrency: code),
      ),
    );

    if (!mounted) return;

    // Refresh unconditionally — a conversion may have succeeded even if the
    // user backed out with the system gesture instead of tapping "Done".
    await ref.read(authNotifierProvider.notifier).refreshBalances();
    if (!mounted) return;

    if (result == 'converted') {
      for (final c in _currencies) {
        ref
            .read(foreignCurrencyNotifierProvider.notifier)
            .fetchAccount(c['code'], forceRefresh: true);
      }
    }
  }

  double _balanceFor(String code) {
    final user = ref.watch(currentUserProvider);
    switch (code) {
      case 'USD': return user?.usdBalanceAmount ?? 0;
      case 'EUR': return user?.eurBalanceAmount ?? 0;
      default:    return 0;
    }
  }

  Future<void> _handleCreateAccount(String code, String name) async {
    final result = await Navigator.push(context, MaterialPageRoute(
        builder: (_) => CreateAccountFlow(currency: name, currencyCode: code)));
    if (result == 'completed') {
      ref.read(foreignCurrencyNotifierProvider.notifier).fetchAccount(code, forceRefresh: true);
    }
  }

  /// bank_address comes as a JSON string:
  /// {"city":"Memphis","country":"US","line1":"6351 ...","state":"TN","postal_code":"38119"}
  /// Parse it into a human-readable multi-line string.
  String _parseBankAddress(String raw) {
    try {
      // Remove surrounding quotes if any
      final trimmed = raw.trim();
      if (trimmed.startsWith('{')) {
        // It's a JSON object string — parse manually
        String result = trimmed
            .replaceAll('{', '')
            .replaceAll('}', '')
            .replaceAll('"', '');
        // Split by comma, extract values only in order: line1, line2, city, state, postal_code, country
        final Map<String, String> parts = {};
        for (final pair in result.split(',')) {
          final kv = pair.split(':');
          if (kv.length >= 2) {
            parts[kv[0].trim()] = kv.sublist(1).join(':').trim();
          }
        }
        // Helper: is the value usable (not empty, not the literal 'null')?
        bool ok(String? v) =>
            v != null && v.isNotEmpty && v.toLowerCase() != 'null';

        final lines = <String>[];
        if (ok(parts['line1'])) lines.add(parts['line1']!);
        if (ok(parts['line2'])) lines.add(parts['line2']!);
        if (ok(parts['city'])) {
          lines.add(ok(parts['state'])
              ? '${parts['city']!}, ${parts['state']!}'
              : parts['city']!);
        }
        if (ok(parts['postal_code'])) lines.add(parts['postal_code']!);
        if (ok(parts['country'])) lines.add(parts['country']!);
        return lines.where((l) => l.isNotEmpty).join('\n');
      }
    } catch (_) {}
    return raw; // fallback: show as-is
  }

  // Share all account details — lets user choose Copy or Share
  void _shareOrCopyAll(ForeignAccountModel account) async {
    final text = [
      if (account.accountName != null)   'Account Holder: ${account.accountName}',
      if (account.bankName != null)      'Bank: ${account.bankName}',
      if (account.accountNumber != null) 'Account Number: ${account.accountNumber}',
      if (account.routingNumber != null) 'Routing Number: ${account.routingNumber}',
      if (account.swiftCode != null)     'SWIFT: ${account.swiftCode}',
      if (account.iban != null)          'IBAN: ${account.iban}',
      if (account.bankAddress != null)
        'Address: ${_parseBankAddress(account.bankAddress!)}',
    ].join('\n');

    // Show bottom sheet with Copy or Share options
    await showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              margin: const EdgeInsets.only(top: 12),
              width: 40, height: 4,
              decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2)),
            ),
            const SizedBox(height: 8),
            ListTile(
              leading: const Icon(Icons.copy_rounded,
                  color: Color(0xFF34544A)),
              title: const Text('Copy All Details',
                  style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text('Copy to clipboard'),
              onTap: () {
                Navigator.pop(context);
                Clipboard.setData(ClipboardData(text: text));
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
                  content: Text('All details copied!'),
                  backgroundColor: Color(0xFF34544A),
                  duration: Duration(seconds: 2),
                ));
              },
            ),
            ListTile(
              leading: const Icon(Icons.share_rounded,
                  color: Color(0xFF34544A)),
              title: const Text('Share Details',
                  style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text('Share via WhatsApp, Email etc.'),
              onTap: () async {
                // iOS needs an anchor rect for the share-sheet popover, or the
                // sheet silently fails to appear. Capture it before popping the
                // bottom sheet, while this screen is still laid out.
                final box = context.findRenderObject() as RenderBox?;
                final Rect origin = box != null
                    ? box.localToGlobal(Offset.zero) & box.size
                    : Rect.fromLTWH(0, 0, MediaQuery.of(context).size.width,
                        MediaQuery.of(context).size.height / 2);
                Navigator.pop(context);
                // Wait for the bottom sheet to finish dismissing before
                // presenting the share sheet, otherwise iOS won't present it.
                await Future.delayed(const Duration(milliseconds: 250));
                await SharePlus.instance.share(
                  ShareParams(
                    text: text,
                    subject: 'My DuPay Account Details',
                    sharePositionOrigin: origin,
                  ),
                );
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  /// Compact 2-line address for display
  String _parseBankAddressCompact(String raw) {
    try {
      final trimmed = raw.trim();
      if (trimmed.startsWith('{')) {
        final Map<String, String> parts = {};
        final cleaned = trimmed.replaceAll('{', '').replaceAll('}', '').replaceAll('"', '');
        for (final pair in cleaned.split(',')) {
          final kv = pair.split(':');
          if (kv.length >= 2) parts[kv[0].trim()] = kv.sublist(1).join(':').trim();
        }
        // Treat the literal string 'null' as empty (occurs because raw JSON
        // contains `"line2":null` which becomes the substring `line2:null`
        // after quote-stripping).
        bool ok(String? v) =>
            v != null && v.isNotEmpty && v.toLowerCase() != 'null';

        final line1   = ok(parts['line1'])   ? parts['line1']!   : '';
        final city    = ok(parts['city'])    ? parts['city']!    : '';
        final state   = ok(parts['state'])   ? parts['state']!   : '';
        final country = ok(parts['country']) ? parts['country']! : '';
        return [
          if (line1.isNotEmpty) line1,
          [city, state, country].where((s) => s.isNotEmpty).join(', '),
        ].where((s) => s.isNotEmpty).join('\n');
      }
    } catch (_) {}
    return raw;
  }

  void _copyToClipboard(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text('$label copied'),
      backgroundColor: const Color(0xFF34544A),
      duration: const Duration(seconds: 2),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final fcState = ref.watch(foreignCurrencyNotifierProvider);
    final user    = ref.watch(currentUserProvider);
    final meta    = _currencies[_selected];
    final code    = meta['code'] as String;
    final account = fcState.accountFor(code);
    final balance = _balanceFor(code);

    final isActive    = account?.isActive == true;
    final isPending   = account?.isPending == true;
    final noAccount   = account == null || (!isPending && !isActive);

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [

          // ── Top bar ────────────────────────────────────────────────────────
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_ios, color: Colors.white, size: 20),
                onPressed: () => Navigator.pop(context),
              ),
              const Expanded(
                child: Text('Foreign Wallets',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white,
                        fontSize: 18, fontWeight: FontWeight.bold)),
              ),
              const SizedBox(width: 48),
            ]),
          ),

          // ── Card carousel ──────────────────────────────────────────────────
          SizedBox(
            height: 200.h,
            child: PageView.builder(
              controller: _pageController,
              itemCount: _currencies.length,
              onPageChanged: (i) => setState(() => _selected = i),
              itemBuilder: (_, i) {
                final cur     = _currencies[i];
                final curCode = cur['code'] as String;
                final curBal  = _balanceFor(curCode);
                final curAcc  = fcState.accountFor(curCode);
                final isActive = curAcc?.isActive == true;
                final isSelected = i == _selected;

                return AnimatedScale(
                  scale: isSelected ? 1.0 : 0.93,
                  duration: const Duration(milliseconds: 250),
                  child: Container(
                    margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: cur['gradient'] as List<Color>,
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(24),
                      boxShadow: isSelected ? [
                        BoxShadow(
                          color: (cur['gradient'] as List<Color>)[0].withOpacity(0.5),
                          blurRadius: 20, offset: const Offset(0, 8),
                        )
                      ] : [],
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(22),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.center,
                                children: [
                                  Text(cur['flag'],
                                      style: const TextStyle(fontSize: 22)),
                                  const SizedBox(width: 8),
                                  Text(curCode,
                                      style: const TextStyle(
                                          color: Colors.white,
                                          fontWeight: FontWeight.bold,
                                          fontSize: 16)),
                                ],
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.15),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  isActive ? '● Active'
                                    : curAcc?.isPending == true ? '◌ Pending'
                                    : '+ Create',
                                  style: TextStyle(
                                    color: isActive ? Colors.greenAccent
                                        : Colors.white.withOpacity(0.8),
                                    fontSize: 11,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const Spacer(),
                          Text('Available Balance',
                              style: TextStyle(
                                  color: Colors.white.withOpacity(0.6),
                                  fontSize: 12)),
                          const SizedBox(height: 4),
                          Row(children: [
                            Text(
                              _balanceHidden ? '••••••' :
                                  '${cur['symbol']}${_fmt(curBal)}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 26,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(width: 8),
                            GestureDetector(
                              onTap: () =>
                                  setState(() => _balanceHidden = !_balanceHidden),
                              child: Icon(
                                _balanceHidden
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                color: Colors.white.withOpacity(0.6),
                                size: 18,
                              ),
                            ),
                          ]),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          ),

          // ── Page dots ──────────────────────────────────────────────────────
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(_currencies.length, (i) => AnimatedContainer(
              duration: const Duration(milliseconds: 250),
              margin: const EdgeInsets.symmetric(horizontal: 4),
              width: i == _selected ? 20 : 8,
              height: 8,
              decoration: BoxDecoration(
                color: i == _selected
                    ? Colors.white
                    : Colors.white.withOpacity(0.3),
                borderRadius: BorderRadius.circular(4),
              ),
            )),
          ),
          const SizedBox(height: 20),

          // ── Bottom sheet ───────────────────────────────────────────────────
          Expanded(
            child: Container(
              width: double.infinity,
              decoration: const BoxDecoration(
                color: Color(0xFFF4F7FA),
                borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(32),
                    topRight: Radius.circular(32)),
              ),
              // Per-currency gate: only spin when we have nothing cached for
              // *this* currency. isLoading is shared across all currencies, so
              // without the `account == null` guard a cached USD page would
              // spin just because EUR happens to be fetching in the background.
              child: (fcState.isLoading && account == null)
                  ? const Center(child: CircularProgressIndicator(
                      color: Color(0xFF34544A)))
                  : noAccount
                      ? _noAccountView(code, meta)
                      : isPending
                          ? _pendingView(code, meta)
                          : _activeView(code, meta, account!, balance),
            ),
          ),
        ]),
      ),
    );
  }

  // ─── No account ─────────────────────────────────────────────────────────────

  Widget _noAccountView(String code, Map<String, dynamic> meta) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(28),
      child: Column(children: [
        const SizedBox(height: 12),
        Container(
          width: 72, height: 72,
          decoration: BoxDecoration(
            color: const Color(0xFF34544A).withOpacity(0.1),
            shape: BoxShape.circle,
          ),
          child: Center(
            child: Text(meta['flag'], style: const TextStyle(fontSize: 36)),
          ),
        ),
        const SizedBox(height: 16),
        Text('No ${meta['name']} Account',
            style: const TextStyle(
                fontSize: 20, fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        Text(
          'Open a $code account to send, receive and convert money internationally.',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 14, color: Colors.grey[600], height: 1.5),
        ),
        const SizedBox(height: 32),
        // Benefits
        _BenefitTile(
          icon: Icons.public,
          title: 'International Transfers',
          subtitle: 'Send money globally at great rates',
        ),
        _BenefitTile(
          icon: Icons.swap_horiz,
          title: 'Currency Conversion',
          subtitle: 'Convert between NGN, USD and EUR',
        ),
        _BenefitTile(
          icon: Icons.account_balance,
          title: 'Virtual Account',
          subtitle: 'Receive foreign payments directly',
        ),
        const SizedBox(height: 32),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF34544A),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            onPressed: () => _handleCreateAccount(code, meta['name']),
            child: Text('Open $code Account',
                style: const TextStyle(
                    fontWeight: FontWeight.bold, fontSize: 16)),
          ),
        ),
      ]),
    );
  }

  // ─── Pending ────────────────────────────────────────────────────────────────

  Widget _pendingView(String code, Map<String, dynamic> meta) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(28),
      child: Column(children: [
        const SizedBox(height: 12),
        Container(
          width: 72, height: 72,
          decoration: BoxDecoration(
            color: Colors.orange.withOpacity(0.12),
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.hourglass_top_rounded,
              color: Colors.orange, size: 36),
        ),
        const SizedBox(height: 16),
        const Text('Application Under Review',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
        const SizedBox(height: 8),
        Text(
          'Your $code account request is being reviewed. This usually takes 2–3 business days.',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 14, color: Colors.grey[600], height: 1.5),
        ),
        const SizedBox(height: 32),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.orange.withOpacity(0.08),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.orange.withOpacity(0.3)),
          ),
          child: Row(children: [
            const Icon(Icons.info_outline, color: Colors.orange),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                'You\'ll receive a notification once your account is approved.',
                style: TextStyle(color: Colors.orange[800], fontSize: 13),
              ),
            ),
          ]),
        ),
        const SizedBox(height: 24),
        // Still allow conversion even while pending
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            style: OutlinedButton.styleFrom(
              side: const BorderSide(color: Color(0xFF34544A), width: 1.5),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            onPressed: () => _openConverter(code),
            icon: const Icon(Icons.swap_horiz, color: Color(0xFF34544A)),
            label: const Text('Convert Currency',
                style: TextStyle(
                    color: Color(0xFF34544A), fontWeight: FontWeight.bold)),
          ),
        ),
      ]),
    );
  }

  // ─── Active ──────────────────────────────────────────────────────────────────

  Widget _activeView(String code, Map<String, dynamic> meta,
      ForeignAccountModel account, double balance) {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [

        // ── Action buttons ──────────────────────────────────────────────────
        Row(children: [
          _ActionButton(
            icon: Icons.swap_horiz_rounded,
            label: 'Convert',
            color: const Color(0xFF34544A),
            onTap: () => _openConverter(code),
          ),
          const SizedBox(width: 12),
          
          _ActionButton(
            icon: Icons.send_rounded,
            label: 'Send Money',
            color: const Color(0xFF34544A),
            onTap: () => Navigator.push(context, MaterialPageRoute(
                builder: (_) => const BankTransferScreen())),
          ),
        ]),
        const SizedBox(height: 24),

        // ── Account details card ────────────────────────────────────────────
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Account Details',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            GestureDetector(
              onTap: () => _shareOrCopyAll(account),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFF34544A).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(
                      color: const Color(0xFF34544A).withOpacity(0.3)),
                ),
                child: const Row(children: [
                  Icon(Icons.share_rounded, size: 14,
                      color: Color(0xFF34544A)),
                  SizedBox(width: 4),
                  Text('Share All', style: TextStyle(
                      fontSize: 12, color: Color(0xFF34544A),
                      fontWeight: FontWeight.w600)),
                ]),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(color: Colors.black.withOpacity(0.06),
                  blurRadius: 12, offset: const Offset(0, 4)),
            ],
          ),
          child: Column(children: [
            // Gradient header
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF34544A), Color(0xFF7CA183)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20)),
              ),
              child: Row(children: [
                CircleAvatar(
                  backgroundColor: Colors.white.withOpacity(0.2),
                  child: Text(meta['flag'],
                      style: const TextStyle(fontSize: 20)),
                ),
                const SizedBox(width: 12),
                Expanded(child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(account.bankName ?? '—',
                        style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 16)),
                    Text(account.accountName ?? '—',
                        style: TextStyle(
                            color: Colors.white.withOpacity(0.8),
                            fontSize: 13)),
                  ],
                )),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(code,
                      style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 13)),
                ),
              ]),
            ),
            // All copyable detail rows
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Column(children: [
                _CopyRow(
                  label: 'Account Holder',
                  value: account.accountName ?? '—',
                  icon: Icons.person_outline,
                  onCopy: () => _copyToClipboard(
                      account.accountName ?? '', 'Account holder'),
                ),
                _CopyRow(
                  label: 'Bank Name',
                  value: account.bankName ?? '—',
                  icon: Icons.account_balance_outlined,
                  onCopy: () => _copyToClipboard(
                      account.bankName ?? '', 'Bank name'),
                ),
                if (account.accountNumber != null)
                  _CopyRow(
                    label: 'Account Number',
                    value: account.accountNumber!,
                    icon: Icons.credit_card_outlined,
                    onCopy: () => _copyToClipboard(
                        account.accountNumber!, 'Account number'),
                    highlight: true,
                  ),
                // Show routing / SWIFT / IBAN whenever the backend actually
                // returned them, rather than assuming USD => routing and
                // EUR => SWIFT. Some EUR accounts come back with a SWIFT/BIC
                // (and IBAN), and gating on the currency code hid those rows.
                if (account.routingNumber != null &&
                    account.routingNumber!.isNotEmpty)
                  _CopyRow(
                    label: 'Routing Number',
                    value: account.routingNumber!,
                    icon: Icons.route_outlined,
                    onCopy: () => _copyToClipboard(
                        account.routingNumber!, 'Routing number'),
                  ),
                if (account.swiftCode != null &&
                    account.swiftCode!.isNotEmpty)
                  _CopyRow(
                    label: 'SWIFT / BIC',
                    value: account.swiftCode!,
                    icon: Icons.code_outlined,
                    onCopy: () => _copyToClipboard(
                        account.swiftCode!, 'SWIFT code'),
                  ),
                if (account.iban != null && account.iban!.isNotEmpty)
                  _CopyRow(
                    label: 'IBAN',
                    value: account.iban!,
                    icon: Icons.account_balance_wallet_outlined,
                    onCopy: () =>
                        _copyToClipboard(account.iban!, 'IBAN'),
                  ),
                if (account.bankAddress != null)
                  _CopyRow(
                    label: 'Bank Address',
                    value: _parseBankAddressCompact(account.bankAddress!),
                    icon: Icons.location_on_outlined,
                    onCopy: () => _copyToClipboard(
                        _parseBankAddress(account.bankAddress!), 'Bank address'),
                    multiline: true,
                  ),
                const SizedBox(height: 4),
              ]),
            ),
          ]),
        ),

        // ── USD-only "Important Information" notice ─────────────────────────
        if (code == 'USD') ...[
          const SizedBox(height: 16),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFF34544A).withOpacity(0.06),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(
                  color: const Color(0xFF34544A).withOpacity(0.2)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(children: [
                  const Icon(Icons.info_outline,
                      color: Color(0xFF34544A), size: 18),
                  const SizedBox(width: 8),
                  const Text('Important Information',
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: Color(0xFF34544A))),
                ]),
                const SizedBox(height: 10),
                _InfoBullet(
                  'Receiving payment via ACH has a 0.9% fee (minimum \$2)',
                ),
                _InfoBullet(
                  'You\'ll need the ACH Routing number for these payments.',
                ),
                _InfoBullet(
                  'Processing time for incoming payments can take between 1-3 days depending on the payment scheme used by the sending bank.',
                ),
              ],
            ),
          ),
        ],

        const SizedBox(height: 24),
      ]),
    );
  }
}

// ─── Widgets ───────────────────────────────────────────────────────────────────

class _InfoBullet extends StatelessWidget {
  final String text;
  const _InfoBullet(this.text);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(top: 5, right: 8),
            child: Container(
              width: 5, height: 5,
              decoration: BoxDecoration(
                color: const Color(0xFF34544A).withOpacity(0.6),
                shape: BoxShape.circle,
              ),
            ),
          ),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 12.5,
                color: Colors.grey[800],
                height: 1.4,
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
  const _ActionButton({required this.icon, required this.label,
      required this.color, required this.onTap});
  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 14),
          decoration: BoxDecoration(
            color: color.withOpacity(0.08),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withOpacity(0.15)),
          ),
          child: Column(children: [
            Icon(icon, color: color, size: 22),
            const SizedBox(height: 6),
            Text(label,
                style: TextStyle(
                    color: color,
                    fontWeight: FontWeight.w600,
                    fontSize: 11)),
          ]),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final VoidCallback? onCopy;
  const _DetailRow({required this.label, required this.value, this.onCopy});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(children: [
        Expanded(child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label,
                style: TextStyle(
                    fontSize: 12, color: Colors.grey[500],
                    fontWeight: FontWeight.w500)),
            const SizedBox(height: 2),
            Text(value,
                style: const TextStyle(
                    fontWeight: FontWeight.w600, fontSize: 14),
                softWrap: true,
                maxLines: 6,
                overflow: TextOverflow.visible,
              ),
          ],
        )),
        if (onCopy != null)
          GestureDetector(
            onTap: onCopy,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFF34544A).withOpacity(0.08),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.copy_rounded,
                  size: 16, color: Color(0xFF34544A)),
            ),
          ),
      ]),
    );
  }
}

class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  const _StatCard({required this.label, required this.value,
      required this.icon, required this.color});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05),
              blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Icon(icon, color: color, size: 22),
        const SizedBox(height: 8),
        Text(value,
            style: TextStyle(
                fontSize: 16, fontWeight: FontWeight.bold, color: color)),
        const SizedBox(height: 2),
        Text(label,
            style: TextStyle(fontSize: 11, color: Colors.grey[500])),
      ]),
    );
  }
}

class _BenefitTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _BenefitTile({required this.icon, required this.title,
      required this.subtitle});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(children: [
        Container(
          width: 44, height: 44,
          decoration: BoxDecoration(
            color: const Color(0xFF34544A).withOpacity(0.08),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: const Color(0xFF34544A), size: 20),
        ),
        const SizedBox(width: 14),
        Expanded(child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w600, fontSize: 14)),
            Text(subtitle,
                style: TextStyle(
                    fontSize: 12, color: Colors.grey[500])),
          ],
        )),
      ]),
    );
  }
}

// ─── Copy Row ─────────────────────────────────────────────────────────────────
// A detail row with a prominent copy button and tap-anywhere-to-copy support.

class _CopyRow extends StatefulWidget {
  final String label;
  final String value;
  final IconData icon;
  final VoidCallback onCopy;
  final bool highlight;  // account number gets extra emphasis
  final bool multiline;  // bank address wraps

  const _CopyRow({
    required this.label,
    required this.value,
    required this.icon,
    required this.onCopy,
    this.highlight = false,
    this.multiline = false,
  });

  @override
  State<_CopyRow> createState() => _CopyRowState();
}

class _CopyRowState extends State<_CopyRow> {
  bool _copied = false;

  void _onTap() {
    widget.onCopy();
    setState(() => _copied = true);
    Future.delayed(const Duration(seconds: 2),
        () { if (mounted) setState(() => _copied = false); });
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: _onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 5),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: _copied
              ? const Color(0xFF34544A).withOpacity(0.06)
              : widget.highlight
                  ? const Color(0xFF34544A).withOpacity(0.04)
                  : const Color(0xFFF8FAF9),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: _copied
                ? const Color(0xFF34544A).withOpacity(0.4)
                : widget.highlight
                    ? const Color(0xFF34544A).withOpacity(0.2)
                    : Colors.grey[200]!,
            width: 1,
          ),
        ),
        child: Row(
          crossAxisAlignment: widget.multiline
              ? CrossAxisAlignment.start
              : CrossAxisAlignment.center,
          children: [
            // Left icon
            Container(
              width: 32, height: 32,
              decoration: BoxDecoration(
                color: const Color(0xFF34544A).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Icon(widget.icon,
                  size: 16, color: const Color(0xFF34544A)),
            ),
            const SizedBox(width: 12),

            // Label + value
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(widget.label,
                      style: TextStyle(
                          fontSize: 11,
                          color: Colors.grey[500],
                          fontWeight: FontWeight.w500)),
                  const SizedBox(height: 2),
                  Text(
                    widget.value,
                    style: TextStyle(
                      fontSize: widget.highlight ? 16 : 14,
                      fontWeight: widget.highlight
                          ? FontWeight.bold
                          : FontWeight.w600,
                      color: widget.highlight
                          ? const Color(0xFF34544A)
                          : Colors.black87,
                      letterSpacing: widget.highlight ? 1.2 : 0,
                    ),
                    softWrap: true,
                  ),
                ],
              ),
            ),

            // Copy button
            const SizedBox(width: 8),
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: _copied
                    ? const Color(0xFF34544A)
                    : const Color(0xFF34544A).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                Icon(
                  _copied ? Icons.check_rounded : Icons.copy_rounded,
                  size: 13,
                  color: _copied ? Colors.white : const Color(0xFF34544A),
                ),
                const SizedBox(width: 4),
                Text(
                  _copied ? 'Copied!' : 'Copy',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: _copied ? Colors.white : const Color(0xFF34544A),
                  ),
                ),
              ]),
            ),
          ],
        ),
      ),
    );
  }
}
