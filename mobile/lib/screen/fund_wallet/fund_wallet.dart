// lib/screen/fund_wallet/fund_wallet.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/fund_wallet_provider.dart';
import '../../models/fund_wallet/virtual_account_model.dart';

class FundWalletScreen extends ConsumerStatefulWidget {
  const FundWalletScreen({super.key});

  @override
  ConsumerState<FundWalletScreen> createState() => _FundWalletScreenState();
}

class _FundWalletScreenState extends ConsumerState<FundWalletScreen> {

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(fundWalletNotifierProvider.notifier).loadVirtualAccount();
    });
  }

  Future<void> _onCreate() async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF7CA183))),
    );
    final response = await ref
        .read(fundWalletNotifierProvider.notifier)
        .createVirtualAccount();
    if (!mounted) return;
    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(response.success
          ? 'Virtual account created successfully!'
          : (response.message.isNotEmpty ? response.message : 'Failed')),
      backgroundColor: response.success ? Colors.green : Colors.red,
    ));
  }

  void _copy(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text('$label copied!'),
      backgroundColor: const Color(0xFF7CA183),
      duration: const Duration(seconds: 2),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final walletState = ref.watch(fundWalletNotifierProvider);

    return Scaffold(
      backgroundColor: const Color(0xFF7CA183),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          // ── Header ────────────────────────────────────────────────────────
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 16),
            child: Row(children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_ios,
                    color: Colors.white, size: 20),
                onPressed: () => Navigator.of(context).pop(),
              ),
              Expanded(
                child: Text('Auto Wallet Funding',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white)),
              ),
              const SizedBox(width: 48),
            ]),
          ),

          // ── Body ──────────────────────────────────────────────────────────
          Expanded(
            child: Container(
              width: double.infinity,
              decoration: const BoxDecoration(
                color: Color(0xFFF4F7FA),
                borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20)),
              ),
              child: _buildBody(walletState),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _buildBody(FundWalletState walletState) {
    // Loading
    if (walletState.isLoading && !walletState.hasChecked) {
      return const Center(
          child: CircularProgressIndicator(color: Color(0xFF7CA183)));
    }

    // Check account number specifically — not just hasAccount
    final account = walletState.virtualAccount;
    final hasValidAccount = account != null &&
        account.accountNumber != null &&
        account.accountNumber!.isNotEmpty;

    return hasValidAccount
        ? _walletView(account!)
        : _noAccountView();
  }

  // ─── No Account View ───────────────────────────────────────────────────────

  Widget _noAccountView() => SingleChildScrollView(
    padding: const EdgeInsets.all(24),
    child: Column(children: [
      const SizedBox(height: 20),
      // Icon
      Container(
        width: 88, height: 88,
        decoration: BoxDecoration(
          color: const Color(0xFF7CA183).withOpacity(0.1),
          shape: BoxShape.circle,
        ),
        child: const Icon(Icons.account_balance_outlined,
            size: 44, color: Color(0xFF7CA183)),
      ),
      const SizedBox(height: 20),
      Text('No Virtual Account',
          style: TextStyle(
              fontSize: 22.sp,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800])),
      const SizedBox(height: 8),
      Text(
        'Create a virtual account to receive bank transfers directly into your DuPay wallet.',
        textAlign: TextAlign.center,
        style: TextStyle(
            fontSize: 14.sp, color: Colors.grey[600], height: 1.5),
      ),
      const SizedBox(height: 32),

      // Benefits
      _BenefitTile(
        icon: Icons.flash_on_rounded,
        title: 'Instant Settlement',
        subtitle: 'Transfers reflect in your wallet immediately',
      ),
      _BenefitTile(
        icon: Icons.security_rounded,
        title: 'Secure & Dedicated',
        subtitle: 'Your personal account number, exclusive to you',
      ),
      _BenefitTile(
        icon: Icons.account_balance_rounded,
        title: 'All Banks Supported',
        subtitle: 'Receive from any Nigerian bank',
      ),
      const SizedBox(height: 32),

      SizedBox(
        width: double.infinity,
        child: ElevatedButton(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF7CA183),
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(32)),
            padding: const EdgeInsets.symmetric(vertical: 16),
          ),
          onPressed: _onCreate,
          child: Text('Create Virtual Account',
              style: TextStyle(
                  fontSize: 16.sp, fontWeight: FontWeight.bold)),
        ),
      ),
    ]),
  );

  // ─── Has Account View ──────────────────────────────────────────────────────

  Widget _walletView(VirtualAccountModel account) => SingleChildScrollView(
    padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [

      // ── Green "Dedicated account ready" hero card ─────────────────────────
      Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: [Color(0xFF2C4A38), Color(0xFF7CA183)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Dedicated account ready',
                style: TextStyle(
                    color: Colors.white.withOpacity(0.75),
                    fontSize: 13.sp,
                    fontWeight: FontWeight.w500)),
            const SizedBox(height: 10),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 36, height: 36,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.credit_card_rounded,
                      color: Colors.white, size: 18),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text('Transfer instantly to fund your wallet',
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 19.sp,
                          fontWeight: FontWeight.bold,
                          height: 1.25)),
                ),
              ],
            ),
            const SizedBox(height: 14),
            Text('Use the details below and copy them in one tap.',
                style: TextStyle(
                    color: Colors.white.withOpacity(0.75),
                    fontSize: 13.sp,
                    height: 1.4)),
          ],
        ),
      ),
      const SizedBox(height: 24),

      // ── "Account Details" section title ──────────────────────────────────
      Text('Account Details',
          style: TextStyle(
              fontSize: 16.sp,
              fontWeight: FontWeight.bold,
              color: Colors.grey[800])),
      const SizedBox(height: 12),

      // ── White card: bank name + holder + rows ─────────────────────────────
      Container(
        width: double.infinity,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 10,
                offset: const Offset(0, 3)),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(account.bankName ?? 'Virtual Account',
                style: TextStyle(
                    fontSize: 19.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey[850])),
            const SizedBox(height: 2),
            Text(
              (account.accountName ?? '—').toUpperCase(),
              style: TextStyle(
                  fontSize: 13.sp,
                  color: Colors.grey[500],
                  fontWeight: FontWeight.w500,
                  letterSpacing: 0.3),
            ),
            const SizedBox(height: 16),

            if (account.accountNumber != null)
              _CopyRow(
                icon: Icons.tag_rounded,
                label: 'Account Number',
                value: account.accountNumber!,
                onCopy: () =>
                    _copy(account.accountNumber!, 'Account number'),
              ),
            if (account.bankName != null) const SizedBox(height: 12),
            if (account.bankName != null)
              _CopyRow(
                icon: Icons.account_balance_rounded,
                label: 'Bank Name',
                value: account.bankName!,
                onCopy: () => _copy(account.bankName!, 'Bank name'),
              ),
          ],
        ),
      ),
      const SizedBox(height: 16),

      // ── Bottom info banner ─────────────────────────────────────────────────
      Container(
        width: double.infinity,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: const Color(0xFF7CA183).withOpacity(0.06),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
              color: const Color(0xFF7CA183).withOpacity(0.15)),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Icon(Icons.info_outline,
                color: Color(0xFF7CA183), size: 18),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'Send money to this account number only. Bank name is provided for confirmation and copy convenience.',
                style: TextStyle(
                    fontSize: 13.sp,
                    color: const Color(0xFF7CA183),
                    height: 1.4),
              ),
            ),
          ],
        ),
      ),
    ]),
  );
}

// ─── Copy Row (matches design: icon circle, label/value, Copy pill) ───────────

class _CopyRow extends StatefulWidget {
  final IconData icon;
  final String label;
  final String value;
  final VoidCallback onCopy;

  const _CopyRow({
    required this.icon,
    required this.label,
    required this.value,
    required this.onCopy,
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
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      decoration: BoxDecoration(
        color: const Color(0xFFF6F8F7),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.grey[200]!),
      ),
      child: Row(children: [
        Container(
          width: 36, height: 36,
          decoration: BoxDecoration(
            color: const Color(0xFF7CA183).withOpacity(0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(widget.icon, size: 18, color: const Color(0xFF7CA183)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(widget.label,
                  style: TextStyle(
                      fontSize: 12.sp,
                      color: Colors.grey[500],
                      fontWeight: FontWeight.w500)),
              const SizedBox(height: 2),
              Text(widget.value,
                  style: TextStyle(
                      fontSize: 16.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.grey[850])),
            ],
          ),
        ),
        const SizedBox(width: 8),
        GestureDetector(
          onTap: _onTap,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: _copied
                  ? const Color(0xFF7CA183)
                  : Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(
                  color: _copied
                      ? const Color(0xFF7CA183)
                      : Colors.grey[300]!),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              Icon(
                _copied ? Icons.check_rounded : Icons.copy_rounded,
                size: 14,
                color: _copied ? Colors.white : const Color(0xFF7CA183),
              ),
              const SizedBox(width: 4),
              Text(
                _copied ? 'Copied' : 'Copy',
                style: TextStyle(
                    fontSize: 12.sp,
                    fontWeight: FontWeight.bold,
                    color: _copied ? Colors.white : const Color(0xFF7CA183)),
              ),
            ]),
          ),
        ),
      ]),
    );
  }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

class _BenefitTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  const _BenefitTile(
      {required this.icon,
      required this.title,
      required this.subtitle});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(children: [
        Container(
          width: 44, height: 44,
          decoration: BoxDecoration(
            color: const Color(0xFF7CA183).withOpacity(0.08),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: const Color(0xFF7CA183), size: 20),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
            Text(title,
                style: const TextStyle(
                    fontWeight: FontWeight.w600, fontSize: 14)),
            Text(subtitle,
                style: TextStyle(
                    fontSize: 12, color: Colors.grey[500])),
          ]),
        ),
      ]),
    );
  }
}