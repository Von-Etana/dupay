

import 'package:flutter/material.dart';
import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/bill_provider.dart';
import '../../models/bill/bill_model.dart';
import '../transaction/transaction_summary_screen.dart';
import '../transaction/transaction_receipt_screen.dart';

class BillPaymentScreen extends ConsumerStatefulWidget {
  const BillPaymentScreen({super.key});

  @override
  ConsumerState<BillPaymentScreen> createState() => _BillPaymentScreenState();
}

class _BillPaymentScreenState extends ConsumerState<BillPaymentScreen> {
  BillModel? selectedBill;
  String? verifiedCustomerName;
  String? _verifyError;

  final _meterCtrl  = TextEditingController();
  Timer? _debounce;
  final _amountCtrl = TextEditingController();
  final _phoneCtrl  = TextEditingController();
  String _meterType = 'prepaid';
  bool _isVerifying = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(billNotifierProvider.notifier).getBills();
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _meterCtrl.dispose();
    _amountCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  void _onMeterChanged(String value) {
    setState(() { verifiedCustomerName = null; _verifyError = null; });
    _debounce?.cancel();
    if (value.length >= 11 && selectedBill != null) {
      _debounce = Timer(const Duration(milliseconds: 800), _verifyMeter);
    }
  }

  Future<void> _verifyMeter() async {
    if (selectedBill == null || _meterCtrl.text.isEmpty) return;
    setState(() {
      _isVerifying = true;
      verifiedCustomerName = null;
      _verifyError = null;
    });
    final response = await ref.read(billNotifierProvider.notifier).verifyBill(
          discoId: selectedBill!.id.toString(),
          meterNumber: _meterCtrl.text,
          meterType: _meterType,
        );
    if (!mounted) return;
    setState(() => _isVerifying = false);

    if (response.success) {
      setState(() {
        verifiedCustomerName = response.message.isNotEmpty
            ? response.message
            : 'Verified';
        _verifyError = null;
      });
    } else {
      setState(() {
        verifiedCustomerName = null;
        _verifyError = response.message.isNotEmpty
            ? response.message
            : 'Meter number not found. Please check and try again.';
      });
    }
  }

  void _proceed() {
    if (selectedBill == null ||
        _meterCtrl.text.isEmpty ||
        _amountCtrl.text.isEmpty ||
        _phoneCtrl.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Please fill all fields'),
            backgroundColor: Colors.red),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TransactionSummaryScreen(
          data: TransactionSummaryData(
            service: 'Electricity Bill',
            from: 'Wallet',
            accountName: verifiedCustomerName ?? '',
            accountNumber: _meterCtrl.text,
            amount: '₦${_amountCtrl.text}',
            description:
                '${selectedBill!.name ?? selectedBill!.disco} • ${_meterType.toUpperCase()}',
            onConfirm: (pin) async {
              final response =
                  await ref.read(billNotifierProvider.notifier).payBill(
                        phoneNumber: _phoneCtrl.text,
                        discoId: selectedBill!.id.toString(),
                        meterNumber: _meterCtrl.text,
                        meterType: _meterType,
                        amount: _amountCtrl.text,
                        transactionPin: pin,
                      );
              // Build the service-specific receipt rows from what we know
              // (provider, customer, meter) plus anything the API returns
              // (prepaid token/units, VAT). Empty values are skipped.
              final rd = response.data;
              String s(dynamic v) => (v?.toString() ?? '').trim();
              final token = s(rd?['token']).isNotEmpty
                  ? s(rd?['token'])
                  : s(rd?['meter_token']);
              final units = s(rd?['units']).isNotEmpty
                  ? s(rd?['units'])
                  : s(rd?['unit']);
              final vat = s(rd?['vat']).isNotEmpty
                  ? s(rd?['vat'])
                  : s(rd?['charge']);

              final extraFields = <ReceiptField>[
                ReceiptField(
                    'Provider', selectedBill!.name ?? selectedBill!.disco ?? ''),
                ReceiptField('Customer', verifiedCustomerName ?? ''),
                ReceiptField('Meter Number', _meterCtrl.text),
                ReceiptField('Meter Type', _meterType.toUpperCase()),
                if (token.isNotEmpty)
                  ReceiptField('Token', token, isBold: true),
                if (units.isNotEmpty) ReceiptField('Units', units),
                if (vat.isNotEmpty) ReceiptField('VAT', '₦$vat'),
              ];

              return {
                'success': response.success,
                'message': response.message,
                'reference': response.data?['reference']?.toString() ??
                    response.data?['session_id']?.toString() ??
                    '',
                'description': '',
                'extra_fields': extraFields,
                'initial_balance':
                    response.data?['initial_balance']?.toString() ?? '',
                'final_balance':
                    response.data?['final_balance']?.toString() ?? '',
              };
            },
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final billState = ref.watch(billNotifierProvider);
    final bills     = billState.bills ?? [];

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Row(children: [
            IconButton(
                icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                onPressed: selectedBill != null
                    ? () => setState(() {
                          selectedBill = null;
                          verifiedCustomerName = null;
                        })
                    : () => Navigator.of(context).pop()),
            Expanded(
                child: Text('Bill Payment',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 22.sp,
                        fontWeight: FontWeight.w600,
                        color: Colors.white))),
            const SizedBox(width: 48),
          ]),
          const SizedBox(height: 20),
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
                  child: billState.isLoading && bills.isEmpty
                      ? const Center(
                          child: CircularProgressIndicator(
                              color: Color(0xFF34544A)))
                      : selectedBill == null
                          ? _discoList(bills)
                          : _billForm(),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _discoList(List<BillModel> bills) {
    return SingleChildScrollView(
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(height: 24.h),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Text('Select Disco',
              style: TextStyle(
                  fontSize: 16.sp, fontWeight: FontWeight.bold)),
        ),
        SizedBox(height: 18.h),
        if (bills.isEmpty)
          Center(
              child: Padding(
                  padding: const EdgeInsets.all(32),
                  child: Text('No disco providers available',
                      style: TextStyle(color: Colors.grey[500]))))
        else
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: Container(
              decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                        color: Colors.grey.withOpacity(0.08),
                        blurRadius: 8,
                        offset: const Offset(0, 2))
                  ]),
              child: ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: bills.length,
                separatorBuilder: (_, __) =>
                    Divider(height: 1, color: Colors.grey[200]),
                itemBuilder: (_, i) {
                  final bill = bills[i];
                  return ListTile(
                    leading: const Icon(Icons.blur_circular,
                        color: Color(0xFF34544A)),
                    title: Text(bill.name ?? bill.disco ?? '—',
                        style: TextStyle(
                            fontSize: 17.sp, fontWeight: FontWeight.w500)),
                    subtitle: Text(bill.disco ?? '',
                        style: TextStyle(
                            fontSize: 14.sp,
                            color: const Color(0xFF7CA183),
                            fontWeight: FontWeight.bold)),
                    trailing: Icon(Icons.chevron_right,
                        color: Colors.grey[400]),
                    onTap: () => setState(() {
                      selectedBill = bill;
                      verifiedCustomerName = null;
                      _meterCtrl.clear();
                      _amountCtrl.clear();
                      _phoneCtrl.clear();
                    }),
                  );
                },
              ),
            ),
          ),
        SizedBox(height: 24.h),
      ]),
    );
  }

  Widget _billForm() {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        // Selected disco header
        Row(children: [
          const Icon(Icons.bolt, color: Color(0xFF34544A), size: 28),
          const SizedBox(width: 8),
          Expanded(
              child: Text(
                  selectedBill!.name ?? selectedBill!.disco ?? '—',
                  style: TextStyle(
                      fontWeight: FontWeight.bold, fontSize: 18.sp))),
          GestureDetector(
            onTap: () => setState(() {
              selectedBill = null;
              verifiedCustomerName = null;
            }),
            child: const Icon(Icons.close, color: Colors.red, size: 24),
          ),
        ]),
        const SizedBox(height: 24),

        // Meter type
        Text('Meter Type',
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15.sp)),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: _meterTypeBtn('Prepaid', 'prepaid')),
          const SizedBox(width: 12),
          Expanded(child: _meterTypeBtn('Postpaid', 'postpaid')),
        ]),
        const SizedBox(height: 24),

        // Meter number
        _label('Meter Number'),
        Row(children: [
          Expanded(
            child: TextField(
              controller: _meterCtrl,
              keyboardType: TextInputType.number,
              onChanged: _onMeterChanged,
              decoration: InputDecoration(
                hintText: 'Meter Number',
                border: const UnderlineInputBorder(),
                enabledBorder: UnderlineInputBorder(
                  borderSide: BorderSide(
                    color: verifiedCustomerName != null
                        ? Colors.green
                        : _verifyError != null
                            ? Colors.red
                            : Colors.grey,
                    width: verifiedCustomerName != null || _verifyError != null ? 2 : 1,
                  ),
                ),
                focusedBorder: UnderlineInputBorder(
                  borderSide: BorderSide(
                    color: verifiedCustomerName != null
                        ? const Color.fromARGB(255, 36, 114, 39)
                        : _verifyError != null
                            ? Colors.red
                            : const Color(0xFF34544A),
                    width: 2,
                  ),
                ),
                suffixIcon: verifiedCustomerName != null
                    ? const Icon(Icons.check_circle, color: Color.fromARGB(255, 35, 109, 37))
                    : _verifyError != null
                        ? const Icon(Icons.cancel, color: Colors.red)
                        : null,
              ),
            ),
          ),
          const SizedBox(width: 8),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: verifiedCustomerName != null
                    ? const Color.fromARGB(255, 42, 114, 44)
                    : _verifyError != null
                        ? Colors.red
                        : const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8)),
                padding: const EdgeInsets.symmetric(
                    horizontal: 12, vertical: 14)),
            onPressed: _isVerifying ? null : _verifyMeter,
            child: _isVerifying
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                        color: Colors.white, strokeWidth: 2))
                : Text(
                    verifiedCustomerName != null ? 'Verified' : 'Verify',
                    style: const TextStyle(color: Colors.white),
                  ),
          ),
        ]),

        // Verified name / error feedback
        if (verifiedCustomerName != null) ...[
          const SizedBox(height: 8),
          Row(children: [
            const Icon(Icons.check_circle, color: Color.fromARGB(255, 34, 104, 37), size: 20),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                verifiedCustomerName!,
                style: const TextStyle(
                  color: Color.fromARGB(255, 39, 110, 42),
                  fontWeight: FontWeight.bold,
                  fontSize: 15,
                ),
              ),
            ),
          ]),
        ] else if (_verifyError != null) ...[
          const SizedBox(height: 6),
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const Icon(Icons.error_outline, color: Colors.red, size: 18),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                _verifyError!,
                style: const TextStyle(color: Colors.red, fontSize: 13),
              ),
            ),
          ]),
        ],

        const SizedBox(height: 16),
        _label('Amount (₦)'),
        _underlineField(_amountCtrl, 'Amount', TextInputType.number),
        const SizedBox(height: 16),

        _label('Phone Number'),
        _underlineField(_phoneCtrl, 'Phone Number', TextInputType.phone),
        const SizedBox(height: 32),

        SizedBox(
          width: double.infinity,
          height: 48,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12))),
            onPressed: _proceed,
            child: const Text('Continue',
                style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                    color: Colors.white)),
          ),
        ),
      ]),
    );
  }

  Widget _label(String text) => Padding(
        padding: const EdgeInsets.only(bottom: 6),
        child: Text(text,
            style: TextStyle(
                fontWeight: FontWeight.w500,
                fontSize: 13.sp,
                color: Colors.grey[600])),
      );

  Widget _underlineField(TextEditingController ctrl, String hint,
      TextInputType type) {
    return TextField(
      controller: ctrl,
      keyboardType: type,
      decoration: InputDecoration(
        hintText: hint,
        border: const UnderlineInputBorder(),
        enabledBorder: const UnderlineInputBorder(),
        focusedBorder: const UnderlineInputBorder(
            borderSide:
                BorderSide(color: Color(0xFF34544A), width: 2)),
      ),
    );
  }

  Widget _meterTypeBtn(String label, String value) {
    final selected = _meterType == value;
    return GestureDetector(
      onTap: () => setState(() {
        _meterType = value;
        verifiedCustomerName = null;
        _verifyError = null;
      }),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFF34544A) : Colors.white,
          border: Border.all(
              color: selected
                  ? const Color(0xFF34544A)
                  : Colors.grey[300]!),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Center(
            child: Text(label,
                style: TextStyle(
                    color: selected ? Colors.white : Colors.black,
                    fontWeight: FontWeight.bold))),
      ),
    );
  }
}
