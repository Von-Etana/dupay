// lib/screen/foreign_currency/convert_currency_screen.dart

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:intl/intl.dart';
import '../../providers/foreign_currency_provider.dart';
import '../../providers/auth_provider.dart';

class ConvertCurrencyScreen extends ConsumerStatefulWidget {
  final String initialFromCurrency;
  const ConvertCurrencyScreen({super.key, this.initialFromCurrency = 'USD'});

  @override
  ConsumerState<ConvertCurrencyScreen> createState() =>
      _ConvertCurrencyScreenState();
}

class _ConvertCurrencyScreenState extends ConsumerState<ConvertCurrencyScreen> {

  final List<Map<String, dynamic>> _currencies = [
    {'flag': '🇳🇬', 'code': 'NGN', 'symbol': '₦', 'name': 'Nigerian Naira'},
    {'flag': '🇺🇸', 'code': 'USD', 'symbol': '\$', 'name': 'US Dollar'},
    {'flag': '🇪🇺', 'code': 'EUR', 'symbol': '€', 'name': 'Euro'},
  ];

  late int _fromIndex;
  late int _toIndex;
  final _amountCtrl = TextEditingController();
  Timer? _previewDebounce;

  double? _previewConverted;
  double? _previewFee;
  double? _previewRate;
  // Why the last preview failed, if it did. Without this the screen just sat
  // on dashes with a dead Convert button and gave the user no reason — the
  // backend's message (e.g. a misconfigured exchange rate) was thrown away.
  String? _previewError;
  bool _isPreviewing = false;
  bool _isConverting = false;

  // ── PIN (native keyboard) ──────────────────────────────────────────────────
  final _pinController = TextEditingController();
  final _pinFocusNode  = FocusNode();
  bool _showPinPad = false;

  Map<String, dynamic>? _receiptData;
  bool _showReceipt = false;

  @override
  void initState() {
    super.initState();
    _fromIndex = _currencies.indexWhere(
        (c) => c['code'] == widget.initialFromCurrency);
    if (_fromIndex < 0) _fromIndex = 0;
    final validTos = _validToIndices(_fromIndex);
    _toIndex = validTos.isNotEmpty ? validTos.first : 0;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(foreignCurrencyNotifierProvider.notifier).fetchRates();
    });
  }

  @override
  void dispose() {
    _amountCtrl.dispose();
    _previewDebounce?.cancel();
    _pinController.dispose();
    _pinFocusNode.dispose();
    super.dispose();
  }

  // ── Helpers ────────────────────────────────────────────────────────────────

  List<int> _validToIndices(int fromIdx) {
    return _currencies.asMap().entries
        .where((e) => e.key != fromIdx)
        .map((e) => e.key).toList();
  }

  String get _fromCode   => _currencies[_fromIndex]['code'];
  String get _toCode     => _currencies[_toIndex]['code'];
  String get _fromFlag   => _currencies[_fromIndex]['flag'];
  String get _toFlag     => _currencies[_toIndex]['flag'];
  String get _fromSymbol => _currencies[_fromIndex]['symbol'];
  String get _toSymbol   => _currencies[_toIndex]['symbol'];

  // NOTE: `watch`, not `read`.
  //
  // These getters are evaluated during build(), so with `ref.read` the screen
  // would render whatever balance happened to be in auth state at that moment
  // and would never rebuild when the post-conversion figures arrived. Together
  // with the cached (and therefore unchanged) profile response, that is why the
  // balance appeared frozen after a successful conversion.
  double get _fromBalance {
    final user = ref.watch(currentUserProvider);
    switch (_fromCode) {
      case 'USD': return user?.usdBalanceAmount ?? 0;
      case 'EUR': return user?.eurBalanceAmount ?? 0;
      default:    return double.tryParse(user?.balance ?? '0') ?? 0;
    }
  }

  double get _toBalance {
    final user = ref.watch(currentUserProvider);
    switch (_toCode) {
      case 'USD': return user?.usdBalanceAmount ?? 0;
      case 'EUR': return user?.eurBalanceAmount ?? 0;
      default:    return double.tryParse(user?.balance ?? '0') ?? 0;
    }
  }

  String _fmt(double v) => NumberFormat('#,##0.00', 'en_US').format(v);

  double get _parsedAmount =>
      double.tryParse(_amountCtrl.text.replaceAll(',', '')) ?? 0;

  String get _rateLabel {
    if (_previewRate == null || _previewRate == 0) return '—';
    if (_fromCode == 'NGN' && _toCode != 'NGN') {
      return '1 $_toCode = ${_fmt(1 / _previewRate!)} NGN';
    }
    return '1 $_fromCode = ${_fmt(_previewRate!)} $_toCode';
  }

  Map<String, dynamic> _unwrap(Map<String, dynamic> raw) {
    final inner = raw['data'];
    if (inner is Map<String, dynamic>) return inner;
    return raw;
  }

  // ── Preview ────────────────────────────────────────────────────────────────

  void _onAmountChanged(String val) {
    setState(() {
      _previewConverted = null;
      _previewFee = null;
      _previewRate = null;
      _previewError = null;
    });
    _previewDebounce?.cancel();
    final amount = double.tryParse(val.replaceAll(',', '')) ?? 0;
    if (amount <= 0) return;
    _previewDebounce =
        Timer(const Duration(milliseconds: 700), _fetchPreview);
  }

  Future<void> _fetchPreview() async {
    final amount = _parsedAmount;
    if (amount <= 0) return;
    setState(() => _isPreviewing = true);

    final response = await ref
        .read(foreignCurrencyNotifierProvider.notifier)
        .previewConversion(
          fromCurrency: _fromCode,
          toCurrency: _toCode,
          amount: amount,
        );

    if (!mounted) return;
    setState(() => _isPreviewing = false);

    if (!response.success || response.data == null) {
      setState(() {
        _previewError = response.message.isNotEmpty
            ? response.message
            : 'Unable to get a rate for this pair right now.';
        _previewConverted = null;
        _previewFee = null;
        _previewRate = null;
      });
      return;
    }

    final d = _unwrap(response.data!);
    setState(() {
      _previewError     = null;
      _previewRate      = double.tryParse(d['exchange_rate']?.toString() ?? '0') ?? 0;
      // The Fee row must show the ACTUAL fee the backend charges — a dynamic
      // percentage of the amount, capped at `cap` — not the flat `fee`
      // percentage constant (which never changes with the amount and would
      // render as a fixed value). The backend returns this pre-computed as
      // `fee_amount` (denominated in the source currency). We only fall back
      // to computing it locally (same rule) if that field is ever missing.
      _previewFee       = double.tryParse(d['fee_amount']?.toString() ?? '')
          ?? _computeFee(
               amount: amount,
               feePercent: double.tryParse(d['fee']?.toString() ?? '0') ?? 0,
               cap: double.tryParse(d['cap']?.toString() ?? '0') ?? 0,
             );
      _previewConverted = double.tryParse(d['converted_amount']?.toString() ?? '0') ?? 0;
    });
  }

  // Mirror of the backend fee rule (convertCurrency): fee is a percentage of
  // the source amount, then capped. Only used as a fallback when the API
  // response doesn't already include the pre-computed `fee_amount`.
  double _computeFee({
    required double amount,
    required double feePercent,
    required double cap,
  }) {
    var fee = amount * feePercent / 100;
    if (cap > 0 && fee > cap) fee = cap;
    return fee;
  }

  void _swap() {
    final newFrom  = _toIndex;
    final validTos = _validToIndices(newFrom);
    final newTo    = validTos.contains(_fromIndex) ? _fromIndex : validTos.first;
    setState(() {
      _fromIndex = newFrom;
      _toIndex   = newTo;
      _previewConverted = null;
      _previewFee = null;
      _previewRate = null;
    });
    if (_amountCtrl.text.isNotEmpty) _fetchPreview();
  }

  void _pickCurrency(bool isFrom) async {
    final allowedIndices = isFrom
        ? List.generate(_currencies.length, (i) => i)
        : _validToIndices(_fromIndex);

    final picked = await showModalBottomSheet<int>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) => Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
              margin: const EdgeInsets.only(top: 12),
              width: 40, height: 4,
              decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 16),
          Text('Select Currency',
              style: TextStyle(
                  fontSize: 18.sp, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          ...allowedIndices.map((i) {
            final c = _currencies[i];
            return ListTile(
              leading: Text(c['flag'],
                  style: const TextStyle(fontSize: 26)),
              title: Text(c['code'],
                  style: const TextStyle(fontWeight: FontWeight.bold)),
              subtitle: Text(c['name']),
              onTap: () => Navigator.of(ctx).pop(i),
            );
          }),
          const SizedBox(height: 16),
        ],
      ),
    );
    if (picked == null) return;
    setState(() {
      if (isFrom) {
        _fromIndex = picked;
        final validTos = _validToIndices(_fromIndex);
        if (!validTos.contains(_toIndex)) _toIndex = validTos.first;
      } else {
        _toIndex = picked;
      }
      _previewConverted = null;
      _previewFee = null;
      _previewRate = null;
    });
    if (_amountCtrl.text.isNotEmpty) _fetchPreview();
  }

  // ── PIN ────────────────────────────────────────────────────────────────────

  void _clearPin() {
    _pinController.clear();
  }

  void _onConvertPressed() {
    final amount = _parsedAmount;
    if (amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Enter a valid amount'),
          backgroundColor: Colors.red));
      return;
    }
    if (amount > _fromBalance) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text('Insufficient $_fromCode balance'),
          backgroundColor: Colors.red));
      return;
    }
    _clearPin();
    setState(() => _showPinPad = true);
    // Auto-open native keyboard
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _pinFocusNode.requestFocus();
    });
  }

  Future<void> _executeConvert() async {
    final amount = _parsedAmount;
    final pin    = _pinController.text;
    setState(() => _isConverting = true);

    final response = await ref
        .read(foreignCurrencyNotifierProvider.notifier)
        .executeConversion(
          fromCurrency: _fromCode,
          toCurrency: _toCode,
          amount: amount,
          transactionPin: pin,
        );

    if (!mounted) return;
    setState(() { _isConverting = false; _showPinPad = false; });
    _clearPin();

    if (response.success && response.data != null) {
      setState(() {
        _receiptData = _unwrap(response.data!);
        _showReceipt = true;
      });

      // executeConversion() already forces a balance refresh. This second,
      // un-awaited pass covers the case where the API writes the new balances
      // a beat after it returns the conversion receipt — by the time the user
      // taps "Done" or "Convert Again", the figures on screen are current.
      // ignore: unawaited_futures
      ref.read(authNotifierProvider.notifier).refreshBalances();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(response.message.isNotEmpty
              ? response.message : 'Conversion failed. Please try again.'),
          backgroundColor: Colors.red));
    }
  }

  // ── Build ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
            child: Row(children: [
              IconButton(
                icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                onPressed: () {
                  if (_showPinPad) {
                    _pinFocusNode.unfocus();
                    setState(() { _showPinPad = false; _clearPin(); });
                  } else {
                    Navigator.of(context).pop();
                  }
                },
              ),
              Expanded(
                child: Text(
                  _showReceipt
                      ? 'Conversion Receipt'
                      : 'Convert Currency',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 20.sp,
                      fontWeight: FontWeight.bold,
                      color: Colors.white),
                ),
              ),
              const SizedBox(width: 48),
            ]),
          ),
          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(28),
                      topRight: Radius.circular(28))),
              child: _showReceipt
                  ? _receiptView()
                  : _showPinPad
                      ? _pinPadView()
                      : _mainView(),
            ),
          ),
        ]),
      ),
    );
  }

  // ── Main view ──────────────────────────────────────────────────────────────

  Widget _mainView() {
    final hasAmount   = _parsedAmount > 0;
    final hasPreview  = _previewConverted != null;

    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _label('You Send'),
        const SizedBox(height: 8),
        _CurrencyCard(
          flag: _fromFlag, code: _fromCode, symbol: _fromSymbol,
          balance: _fromBalance, controller: _amountCtrl, editable: true,
          onChanged: _onAmountChanged,
          onPickCurrency: () => _pickCurrency(true),
        ),
        const SizedBox(height: 16),

        Center(
          child: GestureDetector(
            onTap: _swap,
            child: Container(
              width: 44, height: 44,
              decoration: BoxDecoration(
                color: const Color(0xFF34544A),
                shape: BoxShape.circle,
                boxShadow: [BoxShadow(
                    color: Colors.black.withOpacity(0.15),
                    blurRadius: 8, offset: const Offset(0, 4))],
              ),
              child: const Icon(Icons.swap_vert,
                  color: Colors.white, size: 22),
            ),
          ),
        ),
        const SizedBox(height: 16),

        _label('You Receive'),
        const SizedBox(height: 8),
        _CurrencyCard(
          flag: _toFlag, code: _toCode, symbol: _toSymbol,
          balance: _toBalance, controller: null, editable: false,
          previewAmount: _isPreviewing ? null : _previewConverted,
          isPreviewing: _isPreviewing,
          onPickCurrency: () => _pickCurrency(false),
        ),
        const SizedBox(height: 20),

        // Summary
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAF9),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Column(children: [
            _SummaryRow(
              label: 'Exchange Rate',
              value: _isPreviewing
                  ? 'Calculating...'
                  : _previewRate != null ? _rateLabel : '—',
              isLoading: _isPreviewing,
            ),
            const Divider(height: 20),
            _SummaryRow(
              label: 'Fee',
              value: _previewFee != null
                  ? (_fromSymbol + _fmt(_previewFee!)) : '—',
              isLoading: _isPreviewing,
            ),
            const Divider(height: 20),
            _SummaryRow(
              label: 'You Receive',
              value: hasPreview
                  ? (_toSymbol + _fmt(_previewConverted!)) : '—',
              isLoading: _isPreviewing,
              bold: true,
              valueColor: const Color(0xFF34544A),
            ),
          ]),
        ),

        // Surface why a preview failed instead of leaving the user staring at
        // dashes and a greyed-out Convert button.
        if (_previewError != null) ...[
          const SizedBox(height: 12),
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFFDECEC),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE5A0A0)),
            ),
            child: Row(children: [
              const Icon(Icons.error_outline,
                  color: Color(0xFFB3261E), size: 20),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  _previewError!,
                  style: const TextStyle(
                      color: Color(0xFF8C1D18), fontSize: 13, height: 1.35),
                ),
              ),
            ]),
          ),
        ],
        const SizedBox(height: 28),

        SizedBox(
          width: double.infinity, height: 52,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: hasAmount && hasPreview
                  ? const Color(0xFF34544A) : Colors.grey[300],
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
            ),
            onPressed: hasAmount && hasPreview && !_isPreviewing
                ? _onConvertPressed : null,
            child: Text('Convert',
                style: TextStyle(
                    fontSize: 18.sp,
                    fontWeight: FontWeight.bold,
                    color: hasAmount && hasPreview
                        ? Colors.white : Colors.grey[600])),
          ),
        ),
        const SizedBox(height: 12),
        Center(
          child: Text('Rates update in real-time',
              style: TextStyle(
                  fontSize: 12.sp, color: Colors.grey[500])),
        ),
      ]),
    );
  }

  // ── PIN Pad view (native keyboard) ─────────────────────────────────────────

  // ── PIN Pad view (native keyboard) ─────────────────────────────────────────

  Widget _pinPadView() {
    final amount = _parsedAmount;
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
      child: Column(children: [
        const SizedBox(height: 12),
        Text('Enter Transaction PIN',
            style: TextStyle(
                fontSize: 20.sp, fontWeight: FontWeight.bold)),
        const SizedBox(height: 6),
        Text(
          'Converting $_fromSymbol${_fmt(amount)} $_fromCode → $_toCode',
          style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 32),

        // PIN dots — tap to re-open keyboard
        GestureDetector(
          behavior: HitTestBehavior.opaque, // Ensures the entire row area catches the tap
          onTap: () {
            // Force re-focus to bring the keyboard back up
            if (_pinFocusNode.hasFocus) {
              _pinFocusNode.unfocus();
            }
            Future.delayed(const Duration(milliseconds: 50), () {
              if (mounted) _pinFocusNode.requestFocus();
            });
          },
          child: ValueListenableBuilder<TextEditingValue>(
            valueListenable: _pinController,
            builder: (_, val, __) => Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(
                  4, (i) => _PinDot(filled: i < val.text.length)),
            ),
          ),
        ),

        // Hidden TextField — safely drives native numeric keyboard without losing layout attachment
        SizedBox(
          height: 0.1, // Small but non-zero height keeps it attached to the UI tree securely
          width: 0.1,
          child: Opacity(
            opacity: 0,
            child: TextField(
              controller: _pinController,
              focusNode: _pinFocusNode,
              keyboardType: TextInputType.number,
              autofocus: true, // Automatically requests focus when layout switches to PIN view
              maxLength: 4,
              obscureText: true,
              showCursor: false, // Prevents random ghost cursors
              enableInteractiveSelection: false, // Stops copy/paste popups from breaking visibility
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
              decoration: const InputDecoration(
                counterText: '',
                border: InputBorder.none,
              ),
              onChanged: (val) {
                setState(() {}); // refresh dots
                if (val.length == 4 && !_isConverting) {
                  _executeConvert();
                }
              },
            ),
          ),
        ),

        const SizedBox(height: 32),
        if (_isConverting)
          const Center(
              child: CircularProgressIndicator(color: Color(0xFF34544A)))
        else
          TextButton(
            onPressed: () {
              _pinFocusNode.unfocus();
              setState(() { _showPinPad = false; _clearPin(); });
            },
            child: Text('Cancel',
                style: TextStyle(
                    color: Colors.red[400],
                    fontWeight: FontWeight.bold,
                    fontSize: 15.sp)),
          ),
      ]),
    );
  }

  // ── Receipt view ───────────────────────────────────────────────────────────

  Widget _receiptView() {
    final d         = _receiptData!;
    final fromCurr  = d['from_currency']?.toString() ?? _fromCode;
    final toCurr    = d['to_currency']?.toString()   ?? _toCode;
    final original  = double.tryParse(d['original_amount']?.toString() ?? '0') ?? 0;
    final received  = double.tryParse(
        d['final_amount_received']?.toString() ??
        d['converted_amount']?.toString() ?? '0') ?? 0;
    final fee       = double.tryParse(d['fee_amount']?.toString() ?? '0') ?? 0;
    final rate      = double.tryParse(d['exchange_rate']?.toString() ?? '0') ?? 0;
    final totalDebit= double.tryParse(d['total_deducted']?.toString() ?? '0') ?? 0;
    final balancesRaw = d['balances'];
    final balances  = balancesRaw is Map<String, dynamic> ? balancesRaw : <String, dynamic>{};
    final ngnBal    = double.tryParse(balances['NGN']?.toString() ?? '0') ?? 0;
    final usdBal    = double.tryParse(balances['USD']?.toString() ?? '0') ?? 0;
    final eurBal    = double.tryParse(balances['EUR']?.toString() ?? '0') ?? 0;

    String curSym(String code) => _currencies.firstWhere(
        (c) => c['code'] == code, orElse: () => _currencies[0])['symbol'];
    String curFlag(String code) => _currencies.firstWhere(
        (c) => c['code'] == code, orElse: () => _currencies[0])['flag'];

    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      child: Column(children: [
        Container(
          width: 80, height: 80,
          decoration: const BoxDecoration(
              color: Color(0xFFDDF3E8), shape: BoxShape.circle),
          child: const Icon(Icons.check_circle_rounded,
              color: Color(0xFF34544A), size: 52),
        ),
        const SizedBox(height: 12),
        Text('Conversion Successful!',
            style: TextStyle(
                fontSize: 22.sp, fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Text(
          '${curFlag(fromCurr)} ${curSym(fromCurr)}${_fmt(original)} $fromCurr'
          ' → ${curFlag(toCurr)} ${curSym(toCurr)}${_fmt(received)} $toCurr',
          style: TextStyle(fontSize: 15.sp, color: Colors.grey[700]),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 24),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAF9),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: Colors.grey[200]!),
          ),
          child: Column(children: [
            _ReceiptRow('You Sent',
                '${curSym(fromCurr)}${_fmt(original)} $fromCurr'),
            _ReceiptRow('You Received',
                '${curSym(toCurr)}${_fmt(received)} $toCurr',
                bold: true,
                valueColor: const Color(0xFF34544A)),
            _ReceiptRow('Exchange Rate',
                '1 $fromCurr = ${_fmt(rate)} $toCurr'),
            _ReceiptRow('Fee Charged',
                '${curSym(fromCurr)}${_fmt(fee)} $fromCurr'),
            _ReceiptRow('Total Deducted',
                '${curSym(fromCurr)}${_fmt(totalDebit)} $fromCurr'),
            const Divider(height: 24),
            Text('Updated Balances',
                style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14.sp,
                    color: Colors.grey[700])),
            const SizedBox(height: 10),
            _ReceiptRow('NGN Balance', '₦${_fmt(ngnBal)}'),
            _ReceiptRow('USD Balance', '\$${_fmt(usdBal)}'),
            _ReceiptRow('EUR Balance', '€${_fmt(eurBal)}'),
          ]),
        ),
        const SizedBox(height: 32),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF34544A),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            onPressed: () => Navigator.of(context).pop('converted'),
            child: const Text('Done',
                style: TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 16)),
          ),
        ),
        const SizedBox(height: 12),
        TextButton(
          onPressed: () {
            setState(() {
              _showReceipt = false;
              _receiptData = null;
              _amountCtrl.clear();
              _previewConverted = null;
              _previewFee = null;
              _previewRate = null;
            });
            // Chained conversions must start from the NEW balance, otherwise
            // the "Insufficient balance" guard below compares against a figure
            // that no longer exists.
            // ignore: unawaited_futures
            ref.read(authNotifierProvider.notifier).refreshBalances();
          },
          child: Text('Convert Again',
              style: TextStyle(
                  color: const Color(0xFF34544A),
                  fontWeight: FontWeight.w600,
                  fontSize: 14.sp)),
        ),
      ]),
    );
  }

  Widget _label(String text) => Text(text,
      style: TextStyle(
          fontSize: 13.sp,
          color: Colors.grey[600],
          fontWeight: FontWeight.w500));
}

// ─── Thousands Separator Formatter ────────────────────────────────────────────
class _ThousandsSeparatorFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    String raw = newValue.text.replaceAll(RegExp(r'[^\d.]'), '');
    final parts = raw.split('.');
    if (parts.length > 2) raw = '${parts[0]}.${parts[1]}';
    if (raw.isEmpty) return newValue.copyWith(text: '');

    final intPart = parts[0];
    final decPart = parts.length > 1 ? '.${parts[1]}' : '';
    final formatted = intPart.isEmpty
        ? ''
        : NumberFormat('#,##0', 'en_US')
            .format(int.tryParse(intPart) ?? 0);
    final result = '$formatted$decPart';
    return TextEditingValue(
      text: result,
      selection: TextSelection.collapsed(offset: result.length),
    );
  }
}

// ─── Currency Card ─────────────────────────────────────────────────────────────
class _CurrencyCard extends StatelessWidget {
  final String flag;
  final String code;
  final String symbol;
  final double balance;
  final TextEditingController? controller;
  final bool editable;
  final double? previewAmount;
  final bool isPreviewing;
  final ValueChanged<String>? onChanged;
  final VoidCallback onPickCurrency;

  const _CurrencyCard({
    required this.flag,
    required this.code,
    required this.symbol,
    required this.balance,
    required this.controller,
    required this.editable,
    required this.onPickCurrency,
    this.previewAmount,
    this.isPreviewing = false,
    this.onChanged,
  });

  String _fmt(double v) => NumberFormat('#,##0.00', 'en_US').format(v);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: editable ? Colors.white : const Color(0xFFF8FAF9),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
            color: editable
                ? const Color(0xFF34544A).withOpacity(0.4)
                : Colors.grey[200]!,
            width: editable ? 1.5 : 1),
        boxShadow: editable
            ? [BoxShadow(
                color: Colors.black.withOpacity(0.06),
                blurRadius: 8,
                offset: const Offset(0, 2))]
            : [],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(children: [
            GestureDetector(
              onTap: onPickCurrency,
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                    color: const Color(0xFF34544A).withOpacity(0.08),
                    borderRadius: BorderRadius.circular(20)),
                child: Row(children: [
                  Text(flag, style: const TextStyle(fontSize: 20)),
                  const SizedBox(width: 6),
                  Text(code,
                      style: const TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(width: 4),
                  const Icon(Icons.arrow_drop_down,
                      color: Color(0xFF34544A), size: 20),
                ]),
              ),
            ),
            if (editable) ...[
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: controller,
                  keyboardType:
                      const TextInputType.numberWithOptions(decimal: true),
                  textAlign: TextAlign.right,
                  inputFormatters: [_ThousandsSeparatorFormatter()],
                  onChanged: onChanged,
                  style: const TextStyle(
                      fontSize: 24, fontWeight: FontWeight.bold),
                  decoration: InputDecoration(
                    border: InputBorder.none,
                    isCollapsed: true,
                    hintText: '0.00',
                    hintStyle: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[400]),
                  ),
                ),
              ),
            ] else ...[
              const Spacer(),
              isPreviewing
                  ? const SizedBox(
                      width: 20, height: 20,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Color(0xFF34544A)))
                  : Text(
                      previewAmount != null
                          ? (symbol + _fmt(previewAmount!)) : (symbol + ' 0.00'),
                      style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: previewAmount != null
                              ? const Color(0xFF34544A)
                              : Colors.grey[400]),
                    ),
            ],
          ]),
          const SizedBox(height: 8),
          Row(children: [
            const Icon(Icons.account_balance_wallet_outlined,
                size: 14, color: Colors.grey),
            const SizedBox(width: 4),
            Text('Balance: $symbol${_fmt(balance)}',
                style: TextStyle(
                    fontSize: 12.sp, color: Colors.grey[600])),
          ]),
        ],
      ),
    );
  }
}

// ─── Summary Row ───────────────────────────────────────────────────────────────
class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isLoading;
  final bool bold;
  final Color? valueColor;
  const _SummaryRow(
      {required this.label,
      required this.value,
      this.isLoading = false,
      this.bold = false,
      this.valueColor});
  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: TextStyle(
                fontSize: 14.sp,
                color: Colors.grey[700],
                fontWeight:
                    bold ? FontWeight.w600 : FontWeight.normal)),
        isLoading
            ? const SizedBox(
                width: 16, height: 16,
                child: CircularProgressIndicator(
                    strokeWidth: 2, color: Color(0xFF34544A)))
            : Text(value,
                style: TextStyle(
                    fontSize: 14.sp,
                    fontWeight:
                        bold ? FontWeight.bold : FontWeight.w500,
                    color: valueColor ?? Colors.black87)),
      ],
    );
  }
}

// ─── Receipt Row ───────────────────────────────────────────────────────────────
class _ReceiptRow extends StatelessWidget {
  final String label;
  final String value;
  final bool bold;
  final Color? valueColor;
  const _ReceiptRow(this.label, this.value,
      {this.bold = false, this.valueColor});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: TextStyle(
                  fontSize: 13.sp, color: Colors.grey[600])),
          Flexible(
            child: Text(value,
                textAlign: TextAlign.right,
                style: TextStyle(
                    fontSize: 14.sp,
                    fontWeight:
                        bold ? FontWeight.bold : FontWeight.w500,
                    color: valueColor ?? Colors.black87)),
          ),
        ],
      ),
    );
  }
}

// ─── PIN Dot ───────────────────────────────────────────────────────────────────
class _PinDot extends StatelessWidget {
  final bool filled;
  const _PinDot({required this.filled});
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10),
      width: 52, height: 52,
      decoration: BoxDecoration(
        color: filled
            ? const Color(0xFF34544A).withOpacity(0.1)
            : Colors.grey[100],
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
            color: filled
                ? const Color(0xFF34544A)
                : Colors.grey[300]!,
            width: 1.5),
      ),
      child: Center(
          child: Icon(
              filled ? Icons.circle : Icons.circle_outlined,
              color: filled
                  ? const Color(0xFF34544A)
                  : Colors.grey[400],
              size: 14)),
    );
  }
}