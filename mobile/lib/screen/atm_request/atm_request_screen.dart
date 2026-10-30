// lib/screen/atm_request/atm_request_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/physical_card_provider.dart';
import '../../widgets/pin_entry_screen.dart';
import '../../core/utils/formatters.dart';

class AtmRequestScreen extends ConsumerStatefulWidget {
  const AtmRequestScreen({super.key});

  @override
  ConsumerState<AtmRequestScreen> createState() => _AtmRequestScreenState();
}

class _AtmRequestScreenState extends ConsumerState<AtmRequestScreen> {
  final _addressCtrl = TextEditingController();

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    // Load the card creation fee so we can show the user exactly what they'll
    // be charged, both on this screen and in the PIN prompt.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(physicalCardNotifierProvider.notifier).fetchFee();
    });
  }

  @override
  void dispose() {
    _addressCtrl.dispose();

    super.dispose();
  }

  bool get _isValid => _addressCtrl.text.trim().isNotEmpty;

  Future<void> _confirm() async {
    if (!_isValid) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please fill in matching addresses'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    // Ask for transaction PIN
    final pin = await _showPinDialog();
    if (pin == null || pin.length < 4) return;

    setState(() => _isLoading = true);

    final response = await ref
        .read(physicalCardNotifierProvider.notifier)
        .requestCard(
          transactionPin: pin,
          deliveryAddress: _addressCtrl.text.trim(),
        );

    if (!mounted) return;
    setState(() => _isLoading = false);

    if (response.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('ATM card request submitted successfully!'),
          backgroundColor: Colors.green,
          duration: Duration(seconds: 3),
        ),
      );
      Navigator.of(context).pop(); // go back to ATM screen
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            response.message.isNotEmpty
                ? response.message
                : 'Request failed. Please try again.',
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<String?> _showPinDialog() {
    final fee = ref.read(physicalCardNotifierProvider).creationFee;
    final subtitle = (fee != null && fee > 0)
        ? 'You will be charged ${formatCurrency(fee)} to request and deliver '
            'this ATM card. Enter your transaction PIN to confirm.'
        : null;
    return showPinEntry(context, subtitle: subtitle);
  }

  @override
  Widget build(BuildContext context) {
    final fee = ref.watch(physicalCardNotifierProvider).creationFee;
    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            const SizedBox(height: 30),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'New ATM Request',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 22.sp,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(width: 48),
              ],
            ),
            const SizedBox(height: 20),
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(top: 20),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20),
                      ),
                    ),
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(24),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SizedBox(height: 8.h),

                          // Info card
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFF34544A).withOpacity(0.06),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: const Color(0xFF34544A).withOpacity(0.2),
                              ),
                            ),
                            child: Row(
                              children: [
                                const Icon(
                                  Icons.info_outline,
                                  color: Color(0xFF34544A),
                                  size: 20,
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    'Your ATM card will be delivered to the address you provide. '
                                    'Please ensure it is correct.',
                                    style: TextStyle(
                                      fontSize: 13.sp,
                                      color: Colors.grey[700],
                                      height: 1.4,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          SizedBox(height: 16.h),

                          // Card creation fee — shown as soon as it loads so the
                          // user knows the charge before confirming.
                          if (fee != null && fee > 0) ...[
                            Container(
                              width: double.infinity,
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 16, vertical: 14),
                              decoration: BoxDecoration(
                                color: const Color(0xFF34544A),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.sell_outlined,
                                      color: Colors.white, size: 20),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      'Card request fee',
                                      style: TextStyle(
                                        fontSize: 13.sp,
                                        color: Colors.white,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                  Text(
                                    formatCurrency(fee),
                                    style: TextStyle(
                                      fontSize: 15.sp,
                                      color: Colors.white,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(height: 20.h),
                          ],

                          // Delivery address
                          _label('Delivery Address'),
                          _textField(
                            controller: _addressCtrl,
                            hint: 'Please enter delivery address',
                            onChanged: (_) => setState(() {}),
                          ),
                          SizedBox(height: 20.h),


                       
                          SizedBox(height: 36.h),

                          SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor:
                                    _isValid && !_isLoading
                                        ? const Color(0xFF34544A)
                                        : Colors.grey[300],
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(32.r),
                                ),
                                padding: EdgeInsets.symmetric(vertical: 16.h),
                              ),
                              onPressed:
                                  _isValid && !_isLoading ? _confirm : null,
                              child:
                                  _isLoading
                                      ? const SizedBox(
                                        width: 22,
                                        height: 22,
                                        child: CircularProgressIndicator(
                                          color: Colors.white,
                                          strokeWidth: 2,
                                        ),
                                      )
                                      : Text(
                                        'Confirm',
                                        style: TextStyle(
                                          fontSize: 18.sp,
                                          fontWeight: FontWeight.bold,
                                        ),
                                      ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(
      text,
      style: TextStyle(
        fontSize: 13.sp,
        color: Colors.grey[700],
        fontWeight: FontWeight.w600,
      ),
    ),
  );

  Widget _textField({
    required TextEditingController controller,
    required String hint,
    ValueChanged<String>? onChanged,
  }) => TextField(
    controller: controller,
    onChanged: onChanged,
    decoration: InputDecoration(
      hintText: hint,
      hintStyle: TextStyle(color: Colors.grey[400], fontSize: 15.sp),
      filled: true,
      fillColor: const Color(0xFFF8FAF9),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12.r),
        borderSide: BorderSide.none,
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12.r),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12.r),
        borderSide: const BorderSide(color: Color(0xFF34544A), width: 1.5),
      ),
      contentPadding: EdgeInsets.symmetric(vertical: 18.h, horizontal: 16.w),
    ),
  );
}
