// lib/screen/card/card_management_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:intl/intl.dart';
import '../../providers/card_provider.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/pin_entry_screen.dart';
import 'card_details_modal.dart';
import 'card_activity_screen.dart';
import 'add_funds_screen.dart';

class CardManagementScreen extends ConsumerWidget {
  final String currency;
  const CardManagementScreen({super.key, required this.currency});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cardState = ref.watch(cardNotifierProvider);
    final card = cardState.cards.firstWhere(
      (c) => c.currency == currency,
      orElse: () => VirtualCardModel(currency: currency),
    );
    return _CardManagementBody(currency: currency, card: card);
  }
}

class _CardManagementBody extends ConsumerStatefulWidget {
  final String currency;
  final VirtualCardModel card;
  const _CardManagementBody(
      {required this.currency, required this.card});

  @override
  ConsumerState<_CardManagementBody> createState() =>
      _CardManagementBodyState();
}

class _CardManagementBodyState
    extends ConsumerState<_CardManagementBody> {
  OverlayEntry? _copyOverlay;

  @override
  void dispose() {
    _copyOverlay?.remove();
    super.dispose();
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

  Future<String?> _askPin(BuildContext context, String title) async {
    return showPinEntry(context, title: title);
  }

  Future<void> _toggleFreeze(BuildContext context) async {
    final card = widget.card;
    final action = card.isFrozen ? 'unfreeze' : 'freeze';
    final pin = await _askPin(
        context,
        card.isFrozen ? 'Unfreeze Card' : 'Freeze Card');
    if (pin == null || pin.length < 4) return;

    _showLoader(context);
    final response = await ref
        .read(cardNotifierProvider.notifier)
        .toggleFreeze(
          cardId: card.id ?? 0,
          action: action,
          transactionPin: pin,
          currency: widget.currency,
        );
    if (context.mounted) {
      Navigator.of(context).pop();
      _snack(
        context,
        response.success
            ? (action == 'freeze'
                ? 'Card frozen successfully'
                : 'Card unfrozen successfully')
            : (response.message.isNotEmpty
                ? response.message
                : 'Action failed'),
        error: !response.success,
      );
    }
  }

  /// Resolves the identifier used by the virtual-card security endpoints
  /// (enable2fa / reset-pin / change-pin): the numeric DB id (e.g. 7),
  /// for both NGN and USD cards.
  String? _securityCardId(VirtualCardModel card) {
    return card.id?.toString();
  }

  // ── Enable 2FA ────────────────────────────────────────────────────────────
  Future<void> _enable2FA(BuildContext context, VirtualCardModel card) async {
    final cardId = _securityCardId(card);
    if (cardId == null) {
      _snack(context, 'Card not ready yet', error: true);
      return;
    }
    final pin = await _askPin(context, 'Enter Transaction PIN');
    if (pin == null || pin.length < 4) return;

    _showLoader(context);
    final response = await ref
        .read(cardNotifierProvider.notifier)
        .enable2FA(cardId: cardId, transactionPin: pin);
    if (context.mounted) {
      Navigator.of(context).pop();
      _snack(
        context,
        response.success
            ? '2FA enabled successfully'
            : (response.message.isNotEmpty
                ? response.message
                : 'Action failed'),
        error: !response.success,
      );
    }
  }

  // ── Reset card PIN ─────────────────────────────────────────────────────────
  Future<void> _resetCardPin(BuildContext context, VirtualCardModel card) async {
    final cardId = _securityCardId(card);
    if (cardId == null) {
      _snack(context, 'Card not ready yet', error: true);
      return;
    }

    final result = await _askPinPair(
      context,
      title: 'Reset Card PIN',
      fields: const [
        _PinField(key: 'new', label: 'New PIN'),
        _PinField(key: 'confirm', label: 'Confirm PIN'),
        _PinField(key: 'transaction', label: 'Transaction PIN'),
      ],
    );
    if (result == null) return;

    final newPin = result['new']!;
    final confirmPin = result['confirm']!;
    final transactionPin = result['transaction']!;
    if (newPin != confirmPin) {
      _snack(context, 'PINs do not match', error: true);
      return;
    }

    _showLoader(context);
    final response = await ref
        .read(cardNotifierProvider.notifier)
        .resetCardPin(
          cardId: cardId,
          newPin: newPin,
          transactionPin: transactionPin,
        );
    if (context.mounted) {
      Navigator.of(context).pop();
      _snack(
        context,
        response.success
            ? 'Card PIN reset successfully'
            : (response.message.isNotEmpty
                ? response.message
                : 'Action failed'),
        error: !response.success,
      );
    }
  }

  // ── Change card PIN ──────────────────────────────────────────────────────
  Future<void> _changeCardPin(BuildContext context, VirtualCardModel card) async {
    final cardId = _securityCardId(card);
    if (cardId == null) {
      _snack(context, 'Card not ready yet', error: true);
      return;
    }

    final result = await _askPinPair(
      context,
      title: 'Change Card PIN',
      fields: const [
        _PinField(key: 'old', label: 'Old PIN'),
        _PinField(key: 'new', label: 'New PIN'),
        _PinField(key: 'confirm', label: 'Confirm New PIN'),
        _PinField(key: 'transaction', label: 'Transaction PIN'),
      ],
    );
    if (result == null) return;

    final oldPin = result['old']!;
    final newPin = result['new']!;
    final confirmPin = result['confirm']!;
    final transactionPin = result['transaction']!;

    if (newPin != confirmPin) {
      _snack(context, 'New PINs do not match', error: true);
      return;
    }

    _showLoader(context);
    final response = await ref.read(cardNotifierProvider.notifier).changeCardPin(
          cardId: cardId,
          oldPin: oldPin,
          newPin: newPin,
          transactionPin: transactionPin,
        );
    if (context.mounted) {
      Navigator.of(context).pop();
      _snack(
        context,
        response.success
            ? 'Card PIN changed successfully'
            : (response.message.isNotEmpty
                ? response.message
                : 'Action failed'),
        error: !response.success,
      );
    }
  }

  /// Shows a dialog with multiple 4-digit PIN fields.
  /// Returns a map of key -> entered value, or null if cancelled
  /// or any field is left incomplete.
  Future<Map<String, String>?> _askPinPair(
    BuildContext context, {
    required String title,
    required List<_PinField> fields,
  }) async {
    final controllers = {
      for (final f in fields) f.key: TextEditingController()
    };

    final result = await showDialog<Map<String, String>>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              for (final f in fields)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: TextField(
                    controller: controllers[f.key],
                    keyboardType: TextInputType.number,
                    maxLength: 4,
                    obscureText: true,
                    decoration: InputDecoration(
                      hintText: '${f.label} (4 digits)',
                      labelText: f.label,
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
            ],
          ),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(ctx).pop(null),
              child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A)),
            onPressed: () {
              final values = {
                for (final f in fields) f.key: controllers[f.key]!.text
              };
              Navigator.of(ctx).pop(values);
            },
            child: const Text('Confirm',
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (result == null) return null;

    for (final f in fields) {
      final v = result[f.key] ?? '';
      if (v.length != 4) return null;
    }
    return result;
  }

  void _showLoader(BuildContext context) => showDialog(
        context: context,
        barrierDismissible: false,
        builder: (_) => const Center(
            child: CircularProgressIndicator(
                color: Color(0xFF34544A))),
      );

  void _snack(BuildContext context, String msg,
          {bool error = false}) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(msg),
        backgroundColor:
            error ? Colors.red : const Color(0xFF34544A),
      ));

  String _fmt(double v) => NumberFormat('#,##0.00').format(v);

  @override
  Widget build(BuildContext context) {
    final card = widget.card;
    // For NGN card, show the user's main NGN wallet balance
    final user = ref.watch(currentUserProvider);
    final ngnWalletBalance =
        double.tryParse(user?.balance ?? '0') ?? 0.0;

    // Balance to display:
    // NGN card → show main NGN wallet balance
    // USD card → show card balance
    final displayBalance =
        widget.currency == 'NGN' ? ngnWalletBalance : card.balance;
    final sym = widget.currency == 'NGN' ? '₦' : '\$';

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Row(children: [
            IconButton(
              icon: const Icon(Icons.arrow_back_ios,
                  color: Colors.white),
              onPressed: () =>
                  Navigator.pushNamed(context, '/homeNav'),
            ),
            Expanded(
              child: Text('Cards',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 25.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.white)),
            ),
          
          ]),
          const SizedBox(height: 30),
          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                color: Color(0xFF7CA183),
                borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20)),
              ),
              child: Padding(
                padding: const EdgeInsets.only(top: 20),
                child: Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20)),
                  ),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 24),
                    child: Column(
                        crossAxisAlignment:
                            CrossAxisAlignment.start,
                        children: [
                      SizedBox(height: 24.h),
                      _VirtualCardWidget(card: card),
                      SizedBox(height: 24.h),

                      // ── Available balance ─────────────────────────────
                      Row(
                          mainAxisAlignment:
                              MainAxisAlignment.spaceBetween,
                          children: [
                        Text(
                          widget.currency == 'NGN'
                              ? 'NGN Wallet Balance'
                              : 'Available balance',
                          style: TextStyle(
                              fontSize: 16.sp,
                              fontWeight: FontWeight.w500,
                              color: Colors.grey[600]),
                        ),
                        Text(
                          '$sym${_fmt(displayBalance)}',
                          style: TextStyle(
                              fontSize: 20.sp,
                              fontWeight: FontWeight.bold),
                        ),
                      ]),

                      // Card status indicator (only for USD so freeze is visible)
                      if (widget.currency == 'USD') ...[
                        SizedBox(height: 8.h),
                        Builder(builder: (_) {
                          // Three states: still provisioning (issuer hasn't
                          // released the PAN yet), active, or frozen.
                          final Color statusColor;
                          final IconData statusIcon;
                          final String statusText;
                          if (card.isProvisioning) {
                            statusColor = const Color(0xFFC98A00);
                            statusIcon = Icons.hourglass_top;
                            statusText = 'Card activating…';
                          } else if (card.isActive) {
                            statusColor = Colors.green;
                            statusIcon = Icons.check_circle_outline;
                            statusText = 'Active';
                          } else {
                            statusColor = Colors.red;
                            statusIcon = Icons.pause_circle_outline;
                            statusText = 'Frozen';
                          }
                          return Row(
                              mainAxisAlignment: MainAxisAlignment.end,
                              children: [
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 10, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: statusColor.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(20),
                                    border: Border.all(
                                        color: statusColor.withOpacity(0.4)),
                                  ),
                                  child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(statusIcon,
                                            size: 13, color: statusColor),
                                        const SizedBox(width: 4),
                                        Text(
                                          statusText,
                                          style: TextStyle(
                                              fontSize: 12.sp,
                                              fontWeight: FontWeight.bold,
                                              color: statusColor),
                                        ),
                                      ]),
                                ),
                              ]);
                        }),
                      ],
                      SizedBox(height: 24.h),

                      // ── Action buttons ────────────────────────────────
                      Row(children: [
                        Expanded(
                            child: _ActionBtn(
                                icon: Icons.add,
                                label: 'Add Funds',
                                onTap: () => Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                        builder: (_) =>
                                            AddFundsScreen(
                                                card: card))))),
                        SizedBox(width: 6.w),
                        Expanded(
                            child: _ActionBtn(
                                icon: Icons.bar_chart,
                                label: 'Transactions',
                                onTap: () => Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                        builder: (_) =>
                                            CardActivityScreen(
                                                card: card))))),
                        SizedBox(width: 6.w),
                        Expanded(
                            child: _ActionBtn(
                                icon: Icons.more_horiz,
                                label: 'More',
                                onTap: () =>
                                    _showMore(context, card))),
                      ]),
                      SizedBox(height: 24.h),

                      // ── Card management ───────────────────────────────
                      Text('Card Management',
                          style: TextStyle(
                              fontSize: 18.sp,
                              fontWeight: FontWeight.bold)),
                      SizedBox(height: 16.h),
                      _OptionTile(
                          title: 'Card details',
                          icon: Icons.credit_card,
                          onTap: () => showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              backgroundColor: Colors.transparent,
                              builder: (_) => CardDetailsModal(card: card))),
                      _OptionTile(
                        title: card.isFrozen
                            ? 'Unfreeze card'
                            : 'Freeze card',
                        icon: Icons.pause_circle_outline,
                        trailing: Switch(
                          value: card.isFrozen,
                          onChanged: (_) =>
                              _toggleFreeze(context),
                          activeColor: const Color(0xFF34544A),
                        ),
                      ),
                      _OptionTile(
                          title: 'View billing address',
                          icon: Icons.location_on,
                          onTap: () => showModalBottomSheet(
                              context: context,
                              isScrollControlled: true,
                              backgroundColor: Colors.transparent,
                              builder: (_) =>
                                  _BillingAddressWithCopy(
                                    card: card,
                                    onCopy: _copy,
                                  ))),

                      // ── Security ──────────────────────────────────────
                      if (true) ...[
                        SizedBox(height: 24.h),
                        Text('Security',
                            style: TextStyle(
                                fontSize: 18.sp,
                                fontWeight: FontWeight.bold)),
                        SizedBox(height: 16.h),
                        _OptionTile(
                          title: 'Enable 2FA',
                          icon: Icons.verified_user_outlined,
                          onTap: () => _enable2FA(context, card),
                        ),
                        _OptionTile(
                          title: 'Reset card PIN',
                          icon: Icons.lock_reset,
                          onTap: () => _resetCardPin(context, card),
                        ),
                        _OptionTile(
                          title: 'Change card PIN',
                          icon: Icons.password,
                          onTap: () => _changeCardPin(context, card),
                        ),
                      ],
                      SizedBox(height: 24.h),
                    ]),
                  ),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  void _showMore(BuildContext context, VirtualCardModel card) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(20)),
        ),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Container(
              margin: EdgeInsets.only(top: 12.h),
              width: 40.w,
              height: 4.h,
              decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2.r))),
          Padding(
            padding: EdgeInsets.all(24.w),
            child: Column(children: [
              Text('More options',
                  style: TextStyle(
                      fontSize: 20.sp,
                      fontWeight: FontWeight.bold)),
              SizedBox(height: 24.h),
              if (card.currency == 'USD')
                ListTile(
                  leading: const Icon(Icons.upload,
                      color: Color(0xFF34544A)),
                  title: const Text('Withdraw to wallet'),
                  onTap: () {
                    Navigator.pop(context);
                    _showWithdrawSheet(context, card);
                  },
                )
              else
                ListTile(
                  leading: Icon(Icons.upload, color: Colors.grey[400]),
                  title: Text('Withdraw to wallet',
                      style: TextStyle(color: Colors.grey[500])),
                  subtitle: Text('Available on USD cards only',
                      style: TextStyle(
                          fontSize: 12, color: Colors.grey[500])),
                  enabled: false,
                ),
            
            ]),
          ),
        ]),
      ),
    );
  }

  void _showWithdrawSheet(
      BuildContext context, VirtualCardModel card) {
    final amountCtrl = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 24,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24),
        child:
            Column(mainAxisSize: MainAxisSize.min, children: [
          Text('Withdraw from Card',
              style: TextStyle(
                  fontSize: 20.sp, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          TextField(
            controller: amountCtrl,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: [_ThousandsSeparatorFormatter()],
            decoration: InputDecoration(
              labelText: 'Amount',
              prefixText: card.currency == 'USD' ? '\$ ' : '',
              border: const OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A)),
              onPressed: () async {
                if (card.currency != 'USD') {
                  Navigator.of(ctx).pop();
                  _snack(context,
                      'Withdrawal is only available on USD cards',
                      error: true);
                  return;
                }
                // Strip commas before parsing
                final raw = amountCtrl.text.replaceAll(',', '');
                final amount = double.tryParse(raw) ?? 0;
                if (amount <= 0) return;
                Navigator.of(ctx).pop();
                final pin =
                    await _askPin(context, 'Enter PIN');
                if (pin == null) return;
                _showLoader(context);
                final response = await ref
                    .read(cardNotifierProvider.notifier)
                    .withdrawFromCard(
                      cardId: card.id ?? 0,
                      amount: amount,
                      transactionPin: pin,
                    );
                if (context.mounted) {
                  Navigator.of(context).pop();
                  _snack(
                      context,
                      response.success
                          ? 'Withdrawn successfully'
                          : response.message,
                      error: !response.success);
                }
              },
              child: const Text('Withdraw',
                  style: TextStyle(color: Colors.white)),
            ),
          ),
        ]),
      ),
    );
  }
}

// ── Card details modal with copy banner support ───────────────────────────────

class _CardDetailsWithCopy extends StatelessWidget {
  final VirtualCardModel card;
  final void Function(String text, String label) onCopy;
  const _CardDetailsWithCopy(
      {required this.card, required this.onCopy});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
            margin: EdgeInsets.only(top: 12.h),
            width: 40.w,
            height: 4.h,
            decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2.r))),
        Padding(
          padding: EdgeInsets.all(24.w),
          child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
            Text('Card Details',
                style: TextStyle(
                    fontSize: 24.sp, fontWeight: FontWeight.bold)),
            IconButton(
                onPressed: () => Navigator.of(context).pop(),
                icon: Icon(Icons.close,
                    color: Colors.grey[600], size: 24.sp)),
          ]),
        ),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 24.w),
          child: Column(children: [
            _CopyRow('Card Name', card.cardName ?? '—',
                () => onCopy(card.cardName ?? '', 'Card name')),
            SizedBox(height: 20.h),
            _CopyRow('Card Number', card.cardNumber ?? '—',
                () => onCopy(card.cardNumber ?? '', 'Card number')),
            SizedBox(height: 20.h),
            _CopyRow('CVV', card.cvv ?? '—',
                () => onCopy(card.cvv ?? '', 'CVV')),
            SizedBox(height: 20.h),
            _CopyRow('Expiry Date', card.expiryDate ?? '—',
                () => onCopy(card.expiryDate ?? '', 'Expiry date')),
            SizedBox(height: 20.h),
            _CopyRow('Currency', card.currency,
                () => onCopy(card.currency, 'Currency')),
            SizedBox(height: 24.h),
            GestureDetector(
              onTap: () {
                Navigator.of(context).pop();
                showModalBottomSheet(
                    context: context,
                    isScrollControlled: true,
                    backgroundColor: Colors.transparent,
                    builder: (_) => _BillingAddressWithCopy(
                        card: card, onCopy: onCopy));
              },
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(vertical: 16.h),
                decoration: BoxDecoration(
                    border:
                        Border.all(color: Colors.grey[300]!),
                    borderRadius: BorderRadius.circular(12.r)),
                child: Row(
                    mainAxisAlignment:
                        MainAxisAlignment.spaceBetween,
                    children: [
                  Padding(
                    padding: const EdgeInsets.only(left: 12),
                    child: Text('View billing address',
                        style: TextStyle(
                            fontSize: 16.sp,
                            fontWeight: FontWeight.w500)),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(right: 12),
                    child: Icon(Icons.arrow_forward_ios,
                        size: 16.sp, color: Colors.grey[600]),
                  ),
                ]),
              ),
            ),
            SizedBox(height: 24.h),
          ]),
        ),
      ]),
    );
  }
}

class _BillingAddressWithCopy extends StatelessWidget {
  final VirtualCardModel card;
  final void Function(String text, String label) onCopy;
  const _BillingAddressWithCopy(
      {required this.card, required this.onCopy});

  @override
  Widget build(BuildContext context) {
    final street = card.billingStreet ?? '—';
    final city = card.billingCity ?? '—';
    final state = card.billingState ?? '—';
    final country = card.billingCountry ?? '—';
    final zip = card.billingZip ?? '—';

    return Container(
      decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius:
              BorderRadius.vertical(top: Radius.circular(20))),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
            margin: EdgeInsets.only(top: 12.h),
            width: 40.w,
            height: 4.h,
            decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2.r))),
        Padding(
          padding: EdgeInsets.all(24.w),
          child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
            Text('Billing Address',
                style: TextStyle(
                    fontSize: 24.sp, fontWeight: FontWeight.bold)),
            IconButton(
                onPressed: () => Navigator.of(context).pop(),
                icon: Icon(Icons.close,
                    color: Colors.grey[600], size: 24.sp)),
          ]),
        ),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 24.w),
          child: Column(children: [
            _CopyRow('Street Address', street,
                () => onCopy(street, 'Street address')),
            SizedBox(height: 16.h),
            _CopyRow(
                'City', city, () => onCopy(city, 'City')),
            SizedBox(height: 16.h),
            _CopyRow(
                'State', state, () => onCopy(state, 'State')),
            SizedBox(height: 16.h),
            _CopyRow('Country', country,
                () => onCopy(country, 'Country')),
            SizedBox(height: 16.h),
            _CopyRow('Postal Code', zip,
                () => onCopy(zip, 'Postal code')),
            SizedBox(height: 24.h),
          ]),
        ),
      ]),
    );
  }
}

class _CopyRow extends StatelessWidget {
  final String label;
  final String value;
  final VoidCallback onCopy;
  const _CopyRow(this.label, this.value, this.onCopy);

  @override
  Widget build(BuildContext context) {
    return Row(crossAxisAlignment: CrossAxisAlignment.start,
        children: [
      Expanded(
          child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
        Text(label,
            style: TextStyle(
                fontSize: 14.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        Text(value,
            style: TextStyle(
                fontSize: 16.sp,
                fontWeight: FontWeight.w600)),
      ])),
      GestureDetector(
        onTap: onCopy,
        child: Container(
          padding:
              EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
          decoration: BoxDecoration(
              color: const Color(0xFF34544A).withOpacity(0.1),
              borderRadius: BorderRadius.circular(8.r),
              border: Border.all(
                  color:
                      const Color(0xFF34544A).withOpacity(0.3))),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.copy,
                size: 16.sp, color: const Color(0xFF34544A)),
            SizedBox(width: 4.w),
            Text('Copy',
                style: TextStyle(
                    fontSize: 12.sp,
                    color: const Color(0xFF34544A),
                    fontWeight: FontWeight.w600)),
          ]),
        ),
      ),
    ]);
  }
}

// ── Helper for multi-field PIN dialog ──────────────────────────────────────

class _PinField {
  final String key;
  final String label;
  const _PinField({required this.key, required this.label});
}

// ── Sub-widgets ───────────────────────────────────────────────────────────────

class _VirtualCardWidget extends StatelessWidget {
  final VirtualCardModel card;
  const _VirtualCardWidget({required this.card});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 200.h,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: card.isFrozen
              ? [Colors.grey, Colors.blueGrey]
              : [const Color(0xFF34544A), const Color(0xFF4a7066)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16.r),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withOpacity(0.15),
              blurRadius: 10,
              offset: const Offset(0, 4))
        ],
      ),
      child: Padding(
        padding: EdgeInsets.all(20.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
              Text('Virtual Card',
                  style: TextStyle(
                      fontSize: 14.sp, color: Colors.white70)),
              Row(children: [
                if (card.isFrozen)
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                        color: Colors.red.withOpacity(0.3),
                        borderRadius: BorderRadius.circular(8)),
                    child: Text('FROZEN',
                        style: TextStyle(
                            color: Colors.white,
                            fontSize: 11.sp,
                            fontWeight: FontWeight.bold)),
                  ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(12)),
                  child: Text('Dupay',
                      style: TextStyle(
                          fontSize: 12.sp,
                          color: Colors.white,
                          fontWeight: FontWeight.bold)),
                ),
              ]),
            ]),
            const Spacer(),
            Text(
              card.displayCardNumber,
              style: TextStyle(
                  fontSize: 18.sp,
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                  letterSpacing: 2),
            ),
            SizedBox(height: 8.h),
            Text(card.cardName ?? '',
                style:
                    TextStyle(fontSize: 14.sp, color: Colors.white70)),
            const Spacer(),
            Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
              Text(
                  card.expiryDate != null
                      ? 'EXP ${card.expiryDate}'
                      : 'debit',
                  style: TextStyle(
                      fontSize: 12.sp, color: Colors.white70)),
              _CurrencyBadge(currency: card.currency),
            ]),
          ],
        ),
      ),
    );
  }
}

class _CurrencyBadge extends StatelessWidget {
  final String currency;
  const _CurrencyBadge({required this.currency});

  @override
  Widget build(BuildContext context) {
    if (currency == 'NGN') {
      return Container(
        width: 32,
        height: 32,
        decoration: BoxDecoration(
            color: Colors.white.withOpacity(0.2),
            shape: BoxShape.circle),
        child: const Center(
            child: Text('₦',
                style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 18))),
      );
    }
    return Stack(children: [
      Container(
          width: 20,
          height: 20,
          decoration: const BoxDecoration(
              color: Colors.red, shape: BoxShape.circle)),
      Positioned(
          left: 14,
          child: Container(
              width: 20,
              height: 20,
              decoration: const BoxDecoration(
                  color: Colors.orange, shape: BoxShape.circle))),
    ]);
  }
}

class _ActionBtn extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback onTap;
  const _ActionBtn(
      {required this.icon, required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding:
            EdgeInsets.symmetric(vertical: 10.h, horizontal: 2.w),
        decoration: BoxDecoration(
            color: const Color(0xFF34544A),
            borderRadius: BorderRadius.circular(10.r)),
        child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            mainAxisSize: MainAxisSize.min,
            children: [
          Icon(icon, color: Colors.white, size: 14.sp),
          SizedBox(width: 4.w),
          Flexible(
              child: Text(label,
                  style: TextStyle(
                      fontSize: 11.sp,
                      color: Colors.white,
                      fontWeight: FontWeight.w600),
                  overflow: TextOverflow.ellipsis)),
        ]),
      ),
    );
  }
}

class _OptionTile extends StatelessWidget {
  final String title;
  final IconData icon;
  final Widget? trailing;
  final VoidCallback? onTap;
  const _OptionTile(
      {required this.title,
      required this.icon,
      this.trailing,
      this.onTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.only(bottom: 8.h),
      child: ListTile(
        leading: Icon(icon, color: const Color(0xFF34544A)),
        title: Text(title,
            style: TextStyle(
                fontSize: 16.sp, fontWeight: FontWeight.w500)),
        trailing:
            trailing ?? const Icon(Icons.arrow_forward_ios, size: 16),
        onTap: onTap,
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12.r)),
        tileColor: Colors.grey[50],
      ),
    );
  }
}
// ─── Thousands Separator Formatter ────────────────────────────────────────────
// Formats numeric input with commas as the user types (e.g. 12345.67 → 12,345.67).
// Allows one decimal point and digits only. Strip the commas before parsing:
//   final raw = controller.text.replaceAll(',', '');
//   final value = double.tryParse(raw) ?? 0;
class _ThousandsSeparatorFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    // Keep only digits and at most one decimal point.
    String raw = newValue.text.replaceAll(RegExp(r'[^\d.]'), '');
    final parts = raw.split('.');
    if (parts.length > 2) raw = '${parts[0]}.${parts[1]}';
    if (raw.isEmpty) return newValue.copyWith(text: '');

    final intPart = parts[0];
    final decPart = parts.length > 1 ? '.${parts[1]}' : '';
    final formatted = intPart.isEmpty
        ? ''
        : NumberFormat('#,##0', 'en_US').format(int.tryParse(intPart) ?? 0);
    final result = '$formatted$decPart';
    return TextEditingValue(
      text: result,
      selection: TextSelection.collapsed(offset: result.length),
    );
  }
}