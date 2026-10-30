import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/transaction_provider.dart';

class CreatePinScreen extends ConsumerStatefulWidget {
  const CreatePinScreen({super.key});

  @override
  ConsumerState<CreatePinScreen> createState() => _CreatePinScreenState();
}

class _CreatePinScreenState extends ConsumerState<CreatePinScreen> {
  final TextEditingController _pinController = TextEditingController();
  final TextEditingController _confirmPinController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();

  bool _obscurePin = true;
  bool _obscureConfirmPin = true;
  bool _obscurePassword = true;

  @override
  void dispose() {
    _pinController.dispose();
    _confirmPinController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _onSubmit() async {
    // Validate inputs
    if (_pinController.text.length != 4) {
      _showError('PIN must be 4 digits');
      return;
    }

    if (_confirmPinController.text != _pinController.text) {
      _showError('PINs do not match');
      return;
    }

    if (_passwordController.text.isEmpty) {
      _showError('Password is required');
      return;
    }

    // Create PIN
    final response = await ref
        .read(transactionNotifierProvider.notifier)
        .createTransactionPin(pin: _pinController.text);

    if (response.success) {
      _showSuccess('Transaction PIN created successfully');
      Navigator.pop(context);
    } else {
      _showError(response.message ?? 'Failed to create PIN');
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
          'Create Transaction PIN',
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
                controller: _pinController,
                label: 'Enter 4-digit PIN',
                obscureText: _obscurePin,
                onToggle: () => setState(() => _obscurePin = !_obscurePin),
                maxLength: 4,
                keyboardType: TextInputType.number,
              ),
              SizedBox(height: 24.h),
              _PinField(
                controller: _confirmPinController,
                label: 'Confirm PIN',
                obscureText: _obscureConfirmPin,
                onToggle:
                    () => setState(
                      () => _obscureConfirmPin = !_obscureConfirmPin,
                    ),
                maxLength: 4,
                keyboardType: TextInputType.number,
              ),
              SizedBox(height: 24.h),
              _PinField(
                controller: _passwordController,
                label: 'Enter Password',
                obscureText: _obscurePassword,
                onToggle:
                    () => setState(() => _obscurePassword = !_obscurePassword),
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
                  child:
                      isLoading
                          ? const CircularProgressIndicator(color: Colors.white)
                          : const Text(
                            'Create PIN',
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
                  'Note: Your PIN must be 4 digits. Do not share your PIN with anyone.',
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
  final int? maxLength;
  final TextInputType? keyboardType;

  const _PinField({
    required this.controller,
    required this.label,
    required this.obscureText,
    required this.onToggle,
    this.maxLength,
    this.keyboardType,
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
            keyboardType: keyboardType,
            decoration: InputDecoration(
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
