// lib/screen/card/fund_usd_card_screen.dart
//
// Fund the USD virtual card by converting NGN, USD, or EUR wallet balance.
// Calls GET /virtual-card/exchanges-rates to show live rates.
// Calls POST /virtual-card/fund with { card_id, currency, amount, transaction_pin }

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:intl/intl.dart';
import '../../providers/card_provider.dart';
import '../../providers/auth_provider.dart';
import '../../core/network/api_service.dart';

// ─── Rate model ───────────────────────────────────────────────────────────────
class _CardRate {
  final String currency; // e.g. 'NGN'
  final double rate;     // how many source units = 1 USD
  final double fee;      // fee percentage

  const _CardRate(
      {required this.currency, required this.rate, required this.fee});
}

// ─── Screen ───────────────────────────────────────────────────────────────────
class FundUsdCardScreen extends ConsumerStatefulWidget {
  final VirtualCardModel card;
  const FundUsdCardScreen({super.key, required this.card});

  @override
  ConsumerState<FundUsdCardScreen> createState() => _FundUsdCardScreenState();
}

class _FundUsdCardScreenState extends ConsumerState<FundUsdCardScreen> {
  final _amountCtrl = TextEditingController();

  String _selectedCurrency = 'NGN';
  List<_CardRate> _rates = [];
  bool _loadingRates = true;
  String? _rateError;

  // ── Formatter ─────────────────────────────────────────────────────────────
  // Always adds commas: 1580.00 → "1,580.00", 15000 → "15,000.00"
  String _fmt(double v) => NumberFormat('#,##0.00').format(v);

  @override
  void initState() {
    super.initState();
    _fetchRates();
    _amountCtrl.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    super.dispose();
  }

  // ── Fetch rates ────────────────────────────────────────────────────────────
  Future<void> _fetchRates() async {
    setState(() {
      _loadingRates = true;
      _rateError = null;
    });
    try {
      final response = await ApiService.instance.get<Map<String, dynamic>>(
        '/virtual-card/exchanges-rates',
        fromJson: (j) => j,
      );
      if (response.data != null) {
        final raw = response.data!['data'];
        final list =
            raw is List ? raw : (raw is Map ? raw.values.toList() : []);
        final parsed = <_CardRate>[];
        for (final item in list) {
          if (item is Map<String, dynamic>) {
            final cur = item['currency']?.toString() ?? '';
            final source = cur.split('-').first;
            final rate =
                double.tryParse(item['rate']?.toString() ?? '0') ?? 0;
            final fee =
                double.tryParse(item['fee']?.toString() ?? '0') ?? 0;
            if (source.isNotEmpty && rate > 0) {
              parsed.add(_CardRate(currency: source, rate: rate, fee: fee));
            }
          }
        }
        setState(() {
          _rates = parsed;
          _loadingRates = false;
          if (parsed.isNotEmpty &&
              !parsed.any((r) => r.currency == _selectedCurrency)) {
            _selectedCurrency = parsed.first.currency;
          }
        });
      }
    } catch (e) {
      setState(() {
        _loadingRates = false;
        _rateError = 'Could not load rates. Tap to retry.';
      });
    }
  }

  // ── Active rate ────────────────────────────────────────────────────────────
  _CardRate? get _activeRate {
    try {
      return _rates.firstWhere((r) => r.currency == _selectedCurrency);
    } catch (_) {
      return null;
    }
  }

  // ── Conversion preview ─────────────────────────────────────────────────────
  ({double usd, double fee, double total}) _preview(double amount) {
    final r = _activeRate;
    if (r == null || r.rate <= 0 || amount <= 0) {
      return (usd: 0, fee: 0, total: 0);
    }
    final usd = amount / r.rate;
    final feeUsd = usd * (r.fee / 100);
    final feeSource = feeUsd * r.rate;
    return (usd: usd, fee: feeSource, total: amount + feeSource);
  }

  String _symbol(String cur) {
    switch (cur) {
      case 'NGN':
        return '₦';
      case 'EUR':
        return '€';
      default:
        return '\$';
    }
  }

  // Returns raw balance string from user model for selected currency
  double _walletBalanceDouble(user) {
    String raw;
    switch (_selectedCurrency) {
      case 'NGN':
        raw = user?.balance ?? '0';
        break;
      case 'USD':
        raw = user?.usdBalance ?? '0';
        break;
      case 'EUR':
        raw = user?.eurBalance ?? '0';
        break;
      default:
        raw = '0';
    }
    return double.tryParse(raw) ?? 0.0;
  }

  // ── PIN dialog ─────────────────────────────────────────────────────────────
  Future<String?> _askPin() async {
    final ctrl = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Enter Transaction PIN'),
        content: TextField(
          controller: ctrl,
          maxLength: 4,
          obscureText: true,
          keyboardType: TextInputType.number,
          decoration:
              const InputDecoration(border: OutlineInputBorder()),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.of(ctx).pop(null),
              child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A)),
            onPressed: () => Navigator.of(ctx).pop(ctrl.text),
            child: const Text('Confirm',
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  // ── Fund ───────────────────────────────────────────────────────────────────
  Future<void> _fund() async {
    final amount = double.tryParse(_amountCtrl.text.replaceAll(',', '')) ?? 0;
    if (amount <= 0) {
      _snack('Enter a valid amount', error: true);
      return;
    }
    final preview = _preview(amount);
    if (preview.usd < 3) {
      _snack(
          'Minimum is \$3.00. ${_symbol(_selectedCurrency)}${_fmt(amount)} = \$${_fmt(preview.usd)}',
          error: true);
      return;
    }
    final pin = await _askPin();
    if (pin == null || pin.length < 4) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    final response = await ref
        .read(cardNotifierProvider.notifier)
        .fundCard(
          cardId: widget.card.id ?? 0,
          currency: _selectedCurrency,
          amount: amount,
          transactionPin: pin,
        );

    if (!mounted) return;
    Navigator.of(context).pop();
    _snack(
      response.success
          ? 'Card funded successfully!'
          : (response.message.isNotEmpty ? response.message : 'Failed'),
      error: !response.success,
    );
    if (response.success) Navigator.of(context).pop();
  }

  void _snack(String msg, {bool error = false}) =>
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(msg),
        backgroundColor:
            error ? Colors.red : const Color(0xFF34544A),
      ));

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);
    final card = widget.card;
    final amount = double.tryParse(_amountCtrl.text.replaceAll(',', '')) ?? 0;
    final preview = _preview(amount);
    final sym = _symbol(_selectedCurrency);
    final walletBal = _walletBalanceDouble(user);

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
                onPressed: () => Navigator.of(context).pop()),
            Expanded(
                child: Text('Fund USD Card',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 22.sp,
                        fontWeight: FontWeight.w700,
                        color: Colors.white))),
            const SizedBox(width: 48),
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
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20))),
                  child: SingleChildScrollView(
                    padding: EdgeInsets.all(24.w),
                    child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                      // ── Mini card preview ─────────────────────────────
                      Container(
                        width: double.infinity,
                        height: 160.h,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [
                              Color(0xFF34544A),
                              Color(0xFF4a7066)
                            ],
                          ),
                          borderRadius: BorderRadius.circular(16.r),
                        ),
                        padding: EdgeInsets.all(20.w),
                        child: Column(
                            crossAxisAlignment:
                                CrossAxisAlignment.start,
                            children: [
                          Text('USD Virtual Card',
                              style: TextStyle(
                                  fontSize: 13.sp,
                                  color: Colors.white70)),
                          const Spacer(),
                          Text(
                              card.cardNumber ??
                                  '**** **** **** ****',
                              style: TextStyle(
                                  fontSize: 16.sp,
                                  color: Colors.white,
                                  fontWeight: FontWeight.w600,
                                  letterSpacing: 2)),
                          SizedBox(height: 4.h),
                          // Card balance — with commas
                          Text(
                              '\$${_fmt(card.balance)}',
                              style: TextStyle(
                                  fontSize: 14.sp,
                                  color: Colors.white70)),
                        ]),
                      ),
                      SizedBox(height: 24.h),

                      // ── Exchange rates ────────────────────────────────
                      Row(
                          mainAxisAlignment:
                              MainAxisAlignment.spaceBetween,
                          children: [
                        Text('Exchange Rates',
                            style: TextStyle(
                                fontSize: 16.sp,
                                fontWeight: FontWeight.w700)),
                        if (_rateError != null)
                          GestureDetector(
                            onTap: _fetchRates,
                            child: const Text('Retry',
                                style: TextStyle(
                                    color: Color(0xFF34544A),
                                    fontWeight: FontWeight.bold)),
                          )
                        else if (!_loadingRates)
                          GestureDetector(
                            onTap: _fetchRates,
                            child: const Icon(Icons.refresh,
                                size: 18,
                                color: Color(0xFF34544A)),
                          ),
                      ]),
                      SizedBox(height: 10.h),

                      if (_loadingRates)
                        const Center(
                            child: Padding(
                          padding:
                              EdgeInsets.symmetric(vertical: 12),
                          child: CircularProgressIndicator(
                              color: Color(0xFF34544A)),
                        ))
                      else if (_rateError != null)
                        Text(_rateError!,
                            style:
                                const TextStyle(color: Colors.red))
                      else
                        Wrap(
                          spacing: 10,
                          runSpacing: 10,
                          children: _rates.map((r) {
                            final isSelected =
                                r.currency == _selectedCurrency;
                            return GestureDetector(
                              onTap: () => setState(() =>
                                  _selectedCurrency = r.currency),
                              child: Container(
                                padding: EdgeInsets.symmetric(
                                    horizontal: 14.w,
                                    vertical: 10.h),
                                decoration: BoxDecoration(
                                  color: isSelected
                                      ? const Color(0xFF34544A)
                                      : const Color(0xFFF8FAF9),
                                  borderRadius:
                                      BorderRadius.circular(12.r),
                                  border: Border.all(
                                      color: isSelected
                                          ? const Color(0xFF34544A)
                                          : Colors.grey.shade300),
                                ),
                                child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                  Text(r.currency,
                                      style: TextStyle(
                                          fontSize: 14.sp,
                                          fontWeight:
                                              FontWeight.bold,
                                          color: isSelected
                                              ? Colors.white
                                              : Colors.black87)),
                                  SizedBox(height: 2.h),
                                  // Rate — with commas
                                  Text(
                                      '${_symbol(r.currency)}${_fmt(r.rate)} = \$1',
                                      style: TextStyle(
                                          fontSize: 12.sp,
                                          color: isSelected
                                              ? Colors.white70
                                              : Colors.grey[600])),
                                  SizedBox(height: 2.h),
                                  Text('Fee: ${r.fee}%',
                                      style: TextStyle(
                                          fontSize: 11.sp,
                                          color: isSelected
                                              ? Colors.white60
                                              : Colors.grey[500])),
                                ]),
                              ),
                            );
                          }).toList(),
                        ),
                      SizedBox(height: 24.h),

                      // ── Wallet balance ────────────────────────────────
                      Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                            color: const Color(0xFFF8FAF9),
                            borderRadius:
                                BorderRadius.circular(12)),
                        child: Row(children: [
                          const Icon(
                              Icons.account_balance_wallet,
                              color: Color(0xFF34544A)),
                          const SizedBox(width: 12),
                          Column(
                              crossAxisAlignment:
                                  CrossAxisAlignment.start,
                              children: [
                            Text(
                                '$_selectedCurrency Wallet Balance',
                                style: const TextStyle(
                                    color: Colors.grey,
                                    fontSize: 13)),
                            // Wallet balance — with commas
                            Text(
                                '$sym${_fmt(walletBal)}',
                                style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 16)),
                          ]),
                        ]),
                      ),
                      SizedBox(height: 24.h),

                      // ── Amount input ──────────────────────────────────
                      Text('Amount to pay ($sym)',
                          style: TextStyle(
                              fontSize: 16.sp,
                              fontWeight: FontWeight.w600)),
                      SizedBox(height: 12.h),
                      TextField(
                        controller: _amountCtrl,
                        keyboardType:
                            const TextInputType.numberWithOptions(
                                decimal: true),
                        inputFormatters: [_ThousandsFormatter()],
                        decoration: InputDecoration(
                          prefixText: sym,
                          hintText: '0.00',
                          filled: true,
                          fillColor: const Color(0xFFF8FAF9),
                          border: OutlineInputBorder(
                              borderRadius:
                                  BorderRadius.circular(12),
                              borderSide: BorderSide.none),
                          contentPadding: EdgeInsets.symmetric(
                              vertical: 16.h, horizontal: 16.w),
                        ),
                      ),
                      SizedBox(height: 16.h),

                      // ── Conversion preview ────────────────────────────
                      if (amount > 0 && _activeRate != null) ...[
                        Container(
                          padding: EdgeInsets.all(14.w),
                          decoration: BoxDecoration(
                              color: const Color(0xFFF0F7F4),
                              borderRadius:
                                  BorderRadius.circular(12),
                              border: Border.all(
                                  color: const Color(0xFF34544A)
                                      .withOpacity(0.2))),
                          child: Column(children: [
                            // You pay — with commas
                            _PreviewRow(
                                label: 'You pay',
                                value: '$sym${_fmt(amount)}'),
                            // Fee — with commas
                            _PreviewRow(
                                label:
                                    'Fee (${_activeRate!.fee}%)',
                                value:
                                    '$sym${_fmt(preview.fee)}'),
                            const Divider(height: 16),
                            // Total deducted — with commas
                            _PreviewRow(
                                label: 'Total deducted',
                                value:
                                    '$sym${_fmt(preview.total)}',
                                bold: true),
                            // Card gets — with commas
                            _PreviewRow(
                                label: 'Card gets',
                                value:
                                    '\$${_fmt(preview.usd)} USD',
                                bold: true,
                                valueColor:
                                    const Color(0xFF34544A)),
                          ]),
                        ),
                        SizedBox(height: 24.h),
                      ] else
                        SizedBox(height: 16.h),

                      // ── Fund button ───────────────────────────────────
                      SizedBox(
                        width: double.infinity,
                        height: 50,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor:
                                _amountCtrl.text.isNotEmpty &&
                                        !_loadingRates
                                    ? const Color(0xFF34544A)
                                    : Colors.grey[300],
                            shape: RoundedRectangleBorder(
                                borderRadius:
                                    BorderRadius.circular(12.r)),
                          ),
                          onPressed:
                              _amountCtrl.text.isNotEmpty &&
                                      !_loadingRates &&
                                      _activeRate != null
                                  ? _fund
                                  : null,
                          child: Text('Fund USD Card',
                              style: TextStyle(
                                  fontSize: 18.sp,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold)),
                        ),
                      ),
                      SizedBox(height: 16.h),
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
}

// ── Helper widget ─────────────────────────────────────────────────────────────
class _PreviewRow extends StatelessWidget {
  final String label;
  final String value;
  final bool bold;
  final Color? valueColor;
  const _PreviewRow(
      {required this.label,
      required this.value,
      this.bold = false,
      this.valueColor});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
        Text(label,
            style: TextStyle(
                fontSize: 13,
                color: Colors.grey[700],
                fontWeight:
                    bold ? FontWeight.w600 : FontWeight.normal)),
        Text(value,
            style: TextStyle(
                fontSize: 13,
                fontWeight:
                    bold ? FontWeight.bold : FontWeight.normal,
                color: valueColor ?? Colors.black87)),
      ]),
    );
  }
}
/// Formats a numeric amount field with thousand separators as the user types,
/// e.g. "10000" -> "10,000", while allowing up to 2 decimal places
/// ("10000.5" -> "10,000.5"). Strip the commas before parsing to a number:
/// `double.tryParse(controller.text.replaceAll(',', ''))`.
class _ThousandsFormatter extends TextInputFormatter {
  final NumberFormat _fmt = NumberFormat.decimalPattern('en_US');

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    // Strip everything except digits and dots, collapse to a single dot.
    var text = newValue.text.replaceAll(RegExp(r'[^0-9.]'), '');
    final dot = text.indexOf('.');
    if (dot != -1) {
      text =
          '${text.substring(0, dot + 1)}${text.substring(dot + 1).replaceAll('.', '')}';
    }
    if (text.isEmpty) return const TextEditingValue(text: '');

    String result;
    if (text.contains('.')) {
      final parts = text.split('.');
      final intPart = parts[0];
      var decPart = parts.length > 1 ? parts[1] : '';
      if (decPart.length > 2) decPart = decPart.substring(0, 2);
      final formattedInt =
          intPart.isEmpty ? '0' : _fmt.format(int.parse(intPart));
      result = '$formattedInt.$decPart';
    } else {
      result = _fmt.format(int.parse(text));
    }

    return TextEditingValue(
      text: result,
      selection: TextSelection.collapsed(offset: result.length),
    );
  }
}
