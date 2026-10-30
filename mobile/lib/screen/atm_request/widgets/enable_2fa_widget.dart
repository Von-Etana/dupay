// lib/screen/atm_request/widgets/enable_2fa_widget.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../providers/physical_card_provider.dart';

/// Enable 2FA on the user's physical card.
///
/// Wires to POST /enable2fa via [PhysicalCardNotifier.enable2FA].
/// Body sent: { card_id (string UUID), transaction_pin }
class Enable2FAWidget extends ConsumerStatefulWidget {
  const Enable2FAWidget({super.key});

  @override
  ConsumerState<Enable2FAWidget> createState() => _Enable2FAWidgetState();
}

class _Enable2FAWidgetState extends ConsumerState<Enable2FAWidget> {
  final _pinCtrl = TextEditingController();
  bool _obscure = true;
  bool _submitting = false;

  @override
  void dispose() {
    _pinCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final pin = _pinCtrl.text;
    if (pin.length != 4) {
      _snack('Transaction PIN must be 4 digits');
      return;
    }
    setState(() => _submitting = true);

    final response = await ref
        .read(physicalCardNotifierProvider.notifier)
        .enable2FA(transactionPin: pin);

    if (!mounted) return;
    setState(() => _submitting = false);

    if (response.success) {
      _snack('2FA enabled successfully', success: true);
      Navigator.pop(context);
    } else {
      _snack(response.message.isNotEmpty
          ? response.message
          : 'Failed to enable 2FA');
    }
  }

  void _snack(String msg, {bool success = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: success ? Colors.green : Colors.red,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final card = ref.watch(physicalCardNotifierProvider).card;

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Enable 2FA',
          style: TextStyle(color: Colors.black, fontWeight: FontWeight.w500),
        ),
        centerTitle: true,
      ),
      body: Padding(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 24.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Info banner
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFF34544A).withOpacity(0.06),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                    color: const Color(0xFF34544A).withOpacity(0.2)),
              ),
              child: Row(children: [
                const Icon(Icons.security, color: Color(0xFF34544A)),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Two-factor authentication adds an extra confirmation step '
                    'whenever your card is used for online purchases.',
                    style:
                        TextStyle(fontSize: 13.sp, color: Colors.grey[800]),
                  ),
                ),
              ]),
            ),
            SizedBox(height: 16.h),

            if (card?.maskedPan != null) ...[
              Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 14, vertical: 12),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Card',
                        style: TextStyle(color: Colors.black54)),
                    Text(card!.maskedPan!,
                        style:
                            const TextStyle(fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
              SizedBox(height: 20.h),
            ],

            TextFormField(
              controller: _pinCtrl,
              obscureText: _obscure,
              keyboardType: TextInputType.number,
              maxLength: 4,
              decoration: InputDecoration(
                labelText: 'Transaction PIN',
                counterText: '',
                filled: true,
                fillColor: Colors.white,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(color: Colors.grey.shade300),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(color: Colors.grey.shade300),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Color(0xFF34544A)),
                ),
                suffixIcon: IconButton(
                  icon: Icon(
                    _obscure ? Icons.visibility_off : Icons.visibility,
                    color: Colors.grey,
                  ),
                  onPressed: () => setState(() => _obscure = !_obscure),
                ),
              ),
            ),
            SizedBox(height: 30.h),
            SizedBox(
              width: double.infinity,
              height: 56.h,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                ),
                onPressed: (_submitting || card == null) ? null : _submit,
                child: _submitting
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text(
                        'Enable 2FA',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
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