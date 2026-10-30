// lib/screen/atm_request/widgets/manage_card_widget.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../providers/physical_card_provider.dart';

/// Change physical card PIN.
///
/// Wires to POST /card/change-pin via [PhysicalCardNotifier.changePin].
/// Body sent:
///   { old_pin, new_pin, confirm_pin,
///     physical_card_id (int DB id, taken from loaded card),
///     fingerPrint, transaction_pin }
class ManageCardWidget extends ConsumerStatefulWidget {
  const ManageCardWidget({super.key});

  @override
  ConsumerState<ManageCardWidget> createState() => _ManageCardWidgetState();
}

class _ManageCardWidgetState extends ConsumerState<ManageCardWidget> {
  final _oldPinCtrl         = TextEditingController();
  final _newPinCtrl         = TextEditingController();
  final _confirmPinCtrl     = TextEditingController();
  final _transactionPinCtrl = TextEditingController();

  bool _obscureOld         = true;
  bool _obscureNew         = true;
  bool _obscureConfirm     = true;
  bool _obscureTransaction = true;
 
  bool _submitting         = false;

  @override
  void dispose() {
    _oldPinCtrl.dispose();
    _newPinCtrl.dispose();
    _confirmPinCtrl.dispose();
    _transactionPinCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final old     = _oldPinCtrl.text;
    final newPin  = _newPinCtrl.text;
    final confirm = _confirmPinCtrl.text;
    final txPin   = _transactionPinCtrl.text;

    if (old.length != 4) {
      _snack('Old PIN must be 4 digits'); return;
    }
    if (newPin.length != 4) {
      _snack('New PIN must be 4 digits'); return;
    }
    if (confirm != newPin) {
      _snack('PINs do not match'); return;
    }
    if (txPin.length != 4) {
      _snack('Transaction PIN must be 4 digits'); return;
    }

    setState(() => _submitting = true);

    final response = await ref
        .read(physicalCardNotifierProvider.notifier)
        .changePin(
          oldPin:         old,
          newPin:         newPin,
          confirmPin:     confirm,
          transactionPin: txPin,
        
        );

    if (!mounted) return;
    setState(() => _submitting = false);

    if (response.success) {
      _snack('PIN changed successfully', success: true);
      Navigator.pop(context);
    } else {
      _snack(response.message.isNotEmpty
          ? response.message
          : 'Failed to change PIN');
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
        automaticallyImplyLeading: true,
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Change Card PIN',
          style: TextStyle(color: Colors.black, fontWeight: FontWeight.w500),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 24.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (card == null) ...[
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.orange.shade50,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.orange.shade200),
                ),
                child: const Row(children: [
                  Icon(Icons.warning_amber_rounded, color: Colors.orange),
                  SizedBox(width: 8),
                  Expanded(
                      child: Text(
                          'No card loaded. Pull to refresh on the card screen.')),
                ]),
              ),
              SizedBox(height: 16.h),
            ],
            _PinField(
              controller: _oldPinCtrl,
              label: 'Old PIN',
              obscureText: _obscureOld,
              onToggle: () => setState(() => _obscureOld = !_obscureOld),
            ),
            SizedBox(height: 20.h),
            _PinField(
              controller: _newPinCtrl,
              label: 'New PIN',
              obscureText: _obscureNew,
              onToggle: () => setState(() => _obscureNew = !_obscureNew),
            ),
            SizedBox(height: 20.h),
            _PinField(
              controller: _confirmPinCtrl,
              label: 'Confirm New PIN',
              obscureText: _obscureConfirm,
              onToggle:
                  () => setState(() => _obscureConfirm = !_obscureConfirm),
            ),
            SizedBox(height: 20.h),
            _PinField(
              controller: _transactionPinCtrl,
              label: 'Transaction PIN',
              obscureText: _obscureTransaction,
              onToggle: () =>
                  setState(() => _obscureTransaction = !_obscureTransaction),
            ),
            SizedBox(height: 16.h),
           
            SizedBox(height: 30.h),
            SizedBox(
              width: double.infinity,
              height: 56.h,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                  elevation: 0,
                ),
                onPressed: (_submitting || card == null) ? null : _submit,
                child: _submitting
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text(
                        'Change PIN',
                        style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
              ),
            ),
            SizedBox(height: 16.h),
            const Center(
              child: Text(
                'Note: Your PIN must be 4 digits. Do not share your PIN with anyone.',
                style: TextStyle(color: Colors.black54, fontSize: 14),
                textAlign: TextAlign.center,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PinField extends StatelessWidget {
  final TextEditingController controller;
  final String label;
  final bool obscureText;
  final VoidCallback onToggle;

  const _PinField({
    required this.controller,
    required this.label,
    required this.obscureText,
    required this.onToggle,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: TextInputType.number,
      maxLength: 4,
      decoration: InputDecoration(
        labelText: label,
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
            obscureText ? Icons.visibility_off : Icons.visibility,
            color: Colors.grey,
          ),
          onPressed: onToggle,
        ),
      ),
      style: const TextStyle(fontSize: 16),
    );
  }
}