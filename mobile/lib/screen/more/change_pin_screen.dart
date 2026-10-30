import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/transaction_provider.dart';

/// Change an EXISTING transaction PIN.
///
/// Unlike [CreatePinScreen] (which only sets a brand-new PIN), this requires the
/// user's current PIN and calls [changeTransactionPin]. The two used to point at
/// the same screen, so "Change Pin" never actually asked for the old PIN.
class ChangePinScreen extends ConsumerStatefulWidget {
  const ChangePinScreen({super.key});

  @override
  ConsumerState<ChangePinScreen> createState() => _ChangePinScreenState();
}

class _ChangePinScreenState extends ConsumerState<ChangePinScreen> {
  final TextEditingController _oldPinController = TextEditingController();
  final TextEditingController _newPinController = TextEditingController();
  final TextEditingController _confirmPinController = TextEditingController();

  bool _obscureOldPin = true;
  bool _obscureNewPin = true;
  bool _obscureConfirmPin = true;

  @override
  void dispose() {
    _oldPinController.dispose();
    _newPinController.dispose();
    _confirmPinController.dispose();
    super.dispose();
  }

  void _onSubmit() async {
    if (_oldPinController.text.length != 4) {
      _showError('Enter your current 4-digit PIN');
      return;
    }
    if (_newPinController.text.length != 4) {
      _showError('New PIN must be 4 digits');
      return;
    }
    if (_confirmPinController.text != _newPinController.text) {
      _showError('New PINs do not match');
      return;
    }
    if (_oldPinController.text == _newPinController.text) {
      _showError('New PIN must be different from your current PIN');
      return;
    }

    final response = await ref
        .read(transactionNotifierProvider.notifier)
        .changeTransactionPin(
          oldPin: _oldPinController.text,
          newPin: _newPinController.text,
        );

    if (!mounted) return;

    if (response.success) {
      _showSuccess('Transaction PIN changed successfully');
      Navigator.pop(context);
    } else {
      _showError(response.message ?? 'Failed to change PIN');
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.green),
    );
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = ref.watch(transactionLoadingProvider);

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        automaticallyImplyLeading: true,
        backgroundColor: Colors.white,
        elevation: 0,
        title: const Text(
          'Change Transaction PIN',
          style: TextStyle(color: Colors.black, fontWeight: FontWeight.w500),
        ),
        centerTitle: true,
      ),
      body: Padding(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 24.h),
        child: SingleChildScrollView(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _PinField(
                controller: _oldPinController,
                label: 'Current PIN',
                obscureText: _obscureOldPin,
                onToggle: () => setState(() => _obscureOldPin = !_obscureOldPin),
              ),
              SizedBox(height: 24.h),
              _PinField(
                controller: _newPinController,
                label: 'New 4-digit PIN',
                obscureText: _obscureNewPin,
                onToggle: () => setState(() => _obscureNewPin = !_obscureNewPin),
              ),
              SizedBox(height: 24.h),
              _PinField(
                controller: _confirmPinController,
                label: 'Confirm New PIN',
                obscureText: _obscureConfirmPin,
                onToggle: () =>
                    setState(() => _obscureConfirmPin = !_obscureConfirmPin),
              ),
              SizedBox(height: 36.h),
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
                  onPressed: isLoading ? null : _onSubmit,
                  child: isLoading
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
              SizedBox(height: 24.h),
              const Center(
                child: Text(
                  'Note: Enter your current PIN, then choose a new 4-digit PIN. '
                  'Do not share your PIN with anyone.',
                  style: TextStyle(color: Colors.black54, fontSize: 14),
                  textAlign: TextAlign.center,
                ),
              ),
            ],
          ),
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
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: TextFormField(
            controller: controller,
            obscureText: obscureText,
            keyboardType: TextInputType.number,
            maxLength: 4,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            decoration: InputDecoration(
              counterText: '',
              border: InputBorder.none,
              contentPadding: EdgeInsets.symmetric(
                horizontal: 16.w,
                vertical: 16.h,
              ),
              suffixIcon: IconButton(
                icon: Icon(
                  obscureText ? Icons.visibility : Icons.visibility_off,
                  color: Colors.grey,
                ),
                onPressed: onToggle,
              ),
            ),
          ),
        ),
      ],
    );
  }
}
