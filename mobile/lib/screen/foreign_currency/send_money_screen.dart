// lib/screen/foreign_currency/send_money_screen.dart
// Replace the existing file with this version.

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/foreign_currency_provider.dart';
import '../transaction/transaction_summary_screen.dart';

class ForeignSendMoneyScreen extends ConsumerStatefulWidget {
  final String currency;
  final String foreignAccountId;

  const ForeignSendMoneyScreen({
    super.key,
    required this.currency,
    required this.foreignAccountId,
  });

  @override
  ConsumerState<ForeignSendMoneyScreen> createState() =>
      _ForeignSendMoneyScreenState();
}

class _ForeignSendMoneyScreenState extends ConsumerState<ForeignSendMoneyScreen> {
  final _recipientNameCtrl = TextEditingController();
  final _recipientAccountCtrl = TextEditingController();
  final _amountCtrl = TextEditingController();
  String _searchQuery = '';

  // Saved beneficiaries (could be fetched from API in a future iteration)
  final List<Map<String, String>> _beneficiaries = [];

  @override
  void dispose() {
    _recipientNameCtrl.dispose();
    _recipientAccountCtrl.dispose();
    _amountCtrl.dispose();
    super.dispose();
  }

  bool get _canSend =>
      _recipientNameCtrl.text.isNotEmpty &&
      _recipientAccountCtrl.text.isNotEmpty &&
      _amountCtrl.text.isNotEmpty;

  void _onSend() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TransactionSummaryScreen(
          data: TransactionSummaryData(
            service: '${widget.currency} Transfer',
            from: '${widget.currency} Wallet',
            accountName: _recipientNameCtrl.text,
            accountNumber: _recipientAccountCtrl.text,
            amount:
                '${widget.currency} ${double.tryParse(_amountCtrl.text)?.toStringAsFixed(2) ?? _amountCtrl.text}',
            onConfirm: (pin) async {
              final response = await ref
                  .read(foreignCurrencyNotifierProvider.notifier)
                  .sendForeignMoney(
                    foreignAccountId: widget.foreignAccountId,
                    recipientName: _recipientNameCtrl.text,
                    recipientAccount: _recipientAccountCtrl.text,
                    currency: widget.currency,
                    amount: double.tryParse(_amountCtrl.text) ?? 0,
                    transactionPin: pin,
                  );
              return {
                'success': response.success,
                'message': response.message,
                'reference': response.data?['reference'] ?? '',
                'description':
                    'Sent ${widget.currency} ${_amountCtrl.text} to ${_recipientNameCtrl.text}',
                'initial_balance': response.data?['initial_balance'] ?? '',
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
    final filtered = _beneficiaries
        .where((b) =>
            b['name']!.toLowerCase().contains(_searchQuery.toLowerCase()))
        .toList();

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 30),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios, color: Colors.black),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'Send ${widget.currency}',
                    textAlign: TextAlign.center,
                    style:
                        TextStyle(fontSize: 20.sp, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
            const SizedBox(height: 10),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20.0),
              child: TextField(
                decoration: InputDecoration(
                  hintText: 'Search beneficiary',
                  filled: true,
                  fillColor: const Color(0xFFF8FAF9),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding:
                      const EdgeInsets.symmetric(vertical: 0, horizontal: 16),
                ),
                onChanged: (v) => setState(() => _searchQuery = v),
              ),
            ),
            if (filtered.isNotEmpty) ...[
              const SizedBox(height: 8),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: const Text('Saved beneficiaries',
                    style: TextStyle(fontWeight: FontWeight.bold)),
              ),
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: filtered.length,
                itemBuilder: (ctx, i) {
                  final b = filtered[i];
                  return ListTile(
                    title: Text(b['name']!,
                        style: const TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: Text('${b['currency']} • ${b['account']}'),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () {
                      _recipientNameCtrl.text = b['name']!;
                      _recipientAccountCtrl.text = b['account']!;
                      setState(() {});
                    },
                  );
                },
              ),
            ],
            const Divider(),
            Expanded(
              child: SingleChildScrollView(
                padding:
                    const EdgeInsets.symmetric(horizontal: 20.0, vertical: 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Send to new recipient',
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16.sp)),
                    const SizedBox(height: 12),
                    // Currency badge
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE3F0FF),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        widget.currency,
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                    ),
                    const SizedBox(height: 12),
                    _buildTextField('Recipient Name', _recipientNameCtrl),
                    const SizedBox(height: 10),
                    _buildTextField(
                        'Account Number / IBAN', _recipientAccountCtrl),
                    const SizedBox(height: 10),
                    _buildTextField('Amount', _amountCtrl,
                        type: TextInputType.number),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _canSend
                              ? const Color(0xFF34544A)
                              : Colors.grey[300],
                          shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: _canSend ? _onSend : null,
                        child: const Text(
                          'Send',
                          style: TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 18,
                              color: Colors.white),
                        ),
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

  Widget _buildTextField(
    String hint,
    TextEditingController controller, {
    TextInputType type = TextInputType.text,
  }) {
    return TextField(
      controller: controller,
      keyboardType: type,
      onChanged: (_) => setState(() {}),
      decoration: InputDecoration(
        hintText: hint,
        filled: true,
        fillColor: const Color(0xFFF8FAF9),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
          borderSide: BorderSide.none,
        ),
      ),
    );
  }
}
