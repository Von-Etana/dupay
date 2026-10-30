// lib/screen/atm_request/widgets/enable_card_widget.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../providers/physical_card_provider.dart';

/// One-time physical card activation screen.
///
/// Wires to POST /create-atm-card via [PhysicalCardNotifier.enableCard].
/// Body sent:
///   { card_pan (last 6 digits, string),
///     cvv (string),
///     card_pin (int),
///     physical_card_id (int DB id, taken from loaded card),
///     transaction_pin }
///
/// Note: this endpoint is a one-shot. The server returns
/// "This card has already been used and cannot be used again." once activated.
class EnableCardWidget extends ConsumerStatefulWidget {
  const EnableCardWidget({super.key});

  @override
  ConsumerState<EnableCardWidget> createState() => _EnableCardWidgetState();
}

class _EnableCardWidgetState extends ConsumerState<EnableCardWidget> {
  final _cardPanCtrl  = TextEditingController();
  final _cvvCtrl      = TextEditingController();
  final _cardPinCtrl  = TextEditingController();
  final _txPinCtrl    = TextEditingController();

  bool _obscurePin   = true;
  bool _obscureTxPin = true;
  bool _submitting   = false;

  @override
  void dispose() {
    _cardPanCtrl.dispose();
    _cvvCtrl.dispose();
    _cardPinCtrl.dispose();
    _txPinCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final pan   = _cardPanCtrl.text.trim();
    final cvv   = _cvvCtrl.text.trim();
    final pin   = _cardPinCtrl.text.trim();
    final txPin = _txPinCtrl.text.trim();

    if (pan.length != 6) {
      _snack('Card PAN must be the last 6 digits'); return;
    }
    if (cvv.length != 3) {
      _snack('CVV must be 3 digits'); return;
    }
    if (pin.length != 4) {
      _snack('Card PIN must be 4 digits'); return;
    }
    if (txPin.length != 4) {
      _snack('Transaction PIN must be 4 digits'); return;
    }

    setState(() => _submitting = true);

    final response = await ref
        .read(physicalCardNotifierProvider.notifier)
        .enableCard(
          cardPan:        pan,
          cvv:            cvv,
          cardPin:        pin,
          transactionPin: txPin,
        );

    if (!mounted) return;
    setState(() => _submitting = false);

    if (response.success) {
      _snack('Card enabled successfully', success: true);
      Navigator.pop(context);
    } else {
      _snack(response.message.isNotEmpty
          ? response.message
          : 'Failed to enable card');
    }
  }

  void _snack(String msg, {bool success = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: success ? Colors.green : Colors.green,
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
          'Enable Card',
          style: TextStyle(color: Colors.black, fontWeight: FontWeight.w500),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 24.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFF34544A).withOpacity(0.06),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                    color: const Color(0xFF34544A).withOpacity(0.2)),
              ),
              child: Row(children: [
                const Icon(Icons.credit_card, color: Color(0xFF34544A)),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Activate your physical card.',
                    style: TextStyle(
                        fontSize: 13.sp, color: Colors.grey[800]),
                  ),
                ),
              ]),
            ),
            SizedBox(height: 20.h),

            _Field(
              controller: _cardPanCtrl,
              label: 'Card PAN (last 6 digits)',
              hint: 'e.g. 121531',
              maxLength: 6,
              digitsOnly: true,
            ),
            SizedBox(height: 16.h),
            _Field(
              controller: _cvvCtrl,
              label: 'CVV',
              hint: '3 digits',
              maxLength: 3,
              digitsOnly: true,
            ),
            SizedBox(height: 16.h),
            _Field(
              controller: _cardPinCtrl,
              label: 'Card PIN',
              hint: '4 digits',
              maxLength: 4,
              digitsOnly: true,
              obscureText: _obscurePin,
              onToggle: () => setState(() => _obscurePin = !_obscurePin),
            ),
            SizedBox(height: 16.h),
            _Field(
              controller: _txPinCtrl,
              label: 'Transaction PIN',
              hint: '4 digits',
              maxLength: 4,
              digitsOnly: true,
              obscureText: _obscureTxPin,
              onToggle: () => setState(() => _obscureTxPin = !_obscureTxPin),
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
                        'Enable Card',
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

class _Field extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final String? hint;
  final int maxLength;
  final bool digitsOnly;
  final bool obscureText;
  final VoidCallback? onToggle;

  const _Field({
    required this.controller,
    required this.label,
    required this.maxLength,
    this.hint,
    this.digitsOnly = false,
    this.obscureText = false,
    this.onToggle,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      keyboardType:
          digitsOnly ? TextInputType.number : TextInputType.text,
      maxLength: maxLength,
      obscureText: obscureText,
      inputFormatters: digitsOnly
          ? [FilteringTextInputFormatter.digitsOnly]
          : null,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
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
        suffixIcon: onToggle == null
            ? null
            : IconButton(
                icon: Icon(
                  obscureText ? Icons.visibility_off : Icons.visibility,
                  color: Colors.grey,
                ),
                onPressed: onToggle,
              ),
      ),
    );
  }
}