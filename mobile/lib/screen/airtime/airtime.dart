import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:intl/intl.dart';
import '../../providers/airtime_provider.dart';
import '../../models/airtime/airtime_network_model.dart';
import 'order_summary_screen.dart';
import '../../core/utils/formatters.dart';
import '../../core/utils/phone_validator.dart';

class AirtimeScreen extends ConsumerStatefulWidget {
  const AirtimeScreen({super.key});

  @override
  ConsumerState<AirtimeScreen> createState() => _AirtimeScreenState();
}

class _AirtimeScreenState extends ConsumerState<AirtimeScreen> {
  int selectedNetwork = 0;
  final TextEditingController amountController = TextEditingController();

  @override
  void initState() {
    super.initState();
    // Only refresh user data if we don't have it already
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final airtimeState = ref.read(airtimeNotifierProvider);
      if (airtimeState.networks == null || airtimeState.networks!.isEmpty) {
        print('📱 AirtimeScreen: No network data, refreshing...');
        ref.read(airtimeNotifierProvider.notifier).getAirtimeNetworks();
      } else {
        print(
          '📱 AirtimeScreen: Network data already exists: ${airtimeState.networks?.length} networks',
        );
      }
    });
  }

  @override
  void dispose() {
    amountController.dispose();
    super.dispose();
  }

  void _showPhoneNumberModal(String amount, AirtimeNetworkModel network) {
    final TextEditingController phoneController = TextEditingController();
    // Inline validation message for the phone field — null while the number is
    // acceptable (or hasn't been submitted yet).
    String? phoneError;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 24,
            bottom: MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    GestureDetector(
                      onTap: () => Navigator.of(context).pop(),
                      child: const Icon(
                        Icons.close,
                        color: Colors.red,
                        size: 28,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  'Airtime',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.black54,
                    fontSize: 16.sp,
                  ),
                ),
                const SizedBox(height: 8),
                CircleAvatar(
                  backgroundImage:
                      network.image != null
                          ? NetworkImage(network.image!)
                          : null,
                  radius: 40,
                  backgroundColor: Colors.white,
                  child:
                      network.image == null
                          ? const Icon(
                            Icons.phone_android,
                            size: 40,
                            color: Color(0xFF34544A),
                          )
                          : null,
                ),
                const SizedBox(height: 12),
                Text(
                  network.name ?? 'Unknown Network',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF34544A),
                    fontSize: 18.sp,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Airtime',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: const Color(0xFF23235B),
                    fontSize: 22.sp,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  formatCurrency(amount),
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    color: Colors.black,
                    fontSize: 20.sp,
                  ),
                ),
                const SizedBox(height: 24),
                Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'Phone Number',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Colors.black,
                      fontSize: 15.sp,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: phoneController,
                  keyboardType: TextInputType.phone,
                  maxLength: 11,
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                  ],
                  onChanged: (_) {
                    // Clear the error as soon as the user starts fixing it.
                    if (phoneError != null) {
                      setModalState(() => phoneError = null);
                    }
                  },
                  decoration: InputDecoration(
                    counterText: '',
                    errorText: phoneError,
                    prefixIcon:
                        const Icon(Icons.phone, color: Colors.grey),
                    hintText: 'Enter phone number',
                    border: const UnderlineInputBorder(),
                    enabledBorder: const UnderlineInputBorder(),
                    focusedBorder: const UnderlineInputBorder(
                      borderSide: BorderSide(
                        color: Color(0xFF34544A),
                        width: 2,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                SizedBox(
                  width: double.infinity,
                  height: 48,
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF34544A),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                    onPressed: () {
                      final error =
                          validateNigerianPhone(phoneController.text);
                      if (error != null) {
                        setModalState(() => phoneError = error);
                        return;
                      }
                      // Send the tidied 11-digit local form so the provider
                      // always receives 0XXXXXXXXXX.
                      final phone =
                          normalizeNigerianPhone(phoneController.text);
                      Navigator.of(context).pop();
                      Navigator.of(context).push(
                        MaterialPageRoute(
                          builder:
                              (context) => OrderSummaryScreen(
                                network: network.name ?? 'Unknown Network',
                                logo: network.image ?? '',
                                amount: amount,
                                phoneNumber: phone,
                              ),
                        ),
                      );
                    },
                    child: const Text(
                      'Continue',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 18,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
              ],
            ),
          ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final airtimeState = ref.watch(airtimeNotifierProvider);
    final networks = airtimeState.networks ?? [];

    if (networks.isEmpty) {
      return Scaffold(
        backgroundColor: const Color(0xFF34544A),
        body: SafeArea(
          bottom: false,
          child: Column(
            children: [
              // Header
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 16,
                ),
                child: Row(
                  children: [
                    IconButton(
                      onPressed: () {
                        // Dismiss the keyboard first; only leave the screen
                        // once it's closed.
                        if (MediaQuery.of(context).viewInsets.bottom > 0) {
                          FocusScope.of(context).unfocus();
                        } else {
                          Navigator.pop(context);
                        }
                      },
                      icon: const Icon(
                        Icons.arrow_back_ios,
                        color: Colors.white,
                        size: 24,
                      ),
                    ),
                    Expanded(
                      child: Text(
                        'Purchase Airtime',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 20.sp,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    const SizedBox(width: 48),
                  ],
                ),
              ),
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
                      child: Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                           
                            Text(
                              '..',
                              style: TextStyle(
                                fontSize: 18.sp,
                                color: Colors.grey[600],
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Loading',
                              style: TextStyle(
                                fontSize: 14.sp,
                                color: Colors.grey[500],
                              ),
                              textAlign: TextAlign.center,
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

    final network = networks[selectedNetwork];
    return Scaffold(
      resizeToAvoidBottomInset: false,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            // Header
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () {
                      // Dismiss the keyboard first; only leave the screen once
                      // it's closed.
                      if (MediaQuery.of(context).viewInsets.bottom > 0) {
                        FocusScope.of(context).unfocus();
                      } else {
                        Navigator.pop(context);
                      }
                    },
                    icon: const Icon(
                      Icons.arrow_back_ios,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                  Expanded(
                    child: Text(
                      'Purchase Airtime',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(width: 48),
                ],
              ),
            ),
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
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          SizedBox(height: 24.h),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24.0,
                            ),
                            child: Text(
                              'Select Network',
                              style: TextStyle(
                                fontSize: 16.sp,
                                color: Colors.black,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          SizedBox(height: 18.h),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 50.0,
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: List.generate(networks.length, (i) {
                                final isSelected = selectedNetwork == i;
                                return GestureDetector(
                                  onTap: () {
                                    setState(() {
                                      selectedNetwork = i;
                                    });
                                  },
                                  child: Column(
                                    children: [
                                      Stack(
                                        children: [
                                          CircleAvatar(
                                            radius: 28,
                                            backgroundColor: Colors.white,
                                            backgroundImage:
                                                networks[i].image != null
                                                    ? NetworkImage(
                                                      networks[i].image!,
                                                    )
                                                    : null,
                                            child:
                                                networks[i].image == null
                                                    ? const Icon(
                                                      Icons.phone_android,
                                                      size: 28,
                                                      color: Color(0xFF34544A),
                                                    )
                                                    : null,
                                          ),
                                          if (isSelected)
                                            Positioned(
                                              right: 0,
                                              bottom: 0,
                                              child: Container(
                                                decoration: const BoxDecoration(
                                                  color: Color(0xFF34544A),
                                                  shape: BoxShape.circle,
                                                ),
                                                padding: const EdgeInsets.all(
                                                  3,
                                                ),
                                                child: const Icon(
                                                  Icons.check,
                                                  color: Colors.white,
                                                  size: 16,
                                                ),
                                              ),
                                            ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      Text(
                                        networks[i].name ?? 'Unknown Network',
                                        style: TextStyle(
                                          fontSize: 13.sp,
                                          color:
                                              isSelected
                                                  ? const Color(0xFF34544A)
                                                  : Colors.black,
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              }),
                            ),
                          ),
                          const SizedBox(height: 42),
                          Center(
                            child: Column(
                              children: [
                                CircleAvatar(
                                  radius: 48,
                                  backgroundColor: Colors.white,
                                  backgroundImage:
                                      network.image != null
                                          ? NetworkImage(network.image!)
                                          : null,
                                  child:
                                      network.image == null
                                          ? const Icon(
                                            Icons.phone_android,
                                            size: 48,
                                            color: Color(0xFF34544A),
                                          )
                                          : null,
                                ),
                                const SizedBox(height: 16),
                                Text(
                                  network.name ?? 'Unknown Network',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF34544A),
                                    fontSize: 18.sp,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'Airtime',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    color: const Color(0xFF23235B),
                                    fontSize: 22.sp,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 32),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24.0,
                            ),
                            child: Text(
                              'Amount',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                color: Colors.black,
                                fontSize: 15.sp,
                              ),
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 24.0,
                              vertical: 8,
                            ),
                            child: TextField(
                              controller: amountController,
                              keyboardType: TextInputType.number,
                              inputFormatters: [_ThousandsSeparatorFormatter()],
                              decoration: const InputDecoration(
                                prefixIcon: Icon(Icons.code),
                                hintText: 'Enter amount',
                                border: UnderlineInputBorder(
                                  borderSide: BorderSide(
                                    color: Color(0xFF34544A),
                                  ),
                                ),
                                focusedBorder: UnderlineInputBorder(
                                  borderSide: BorderSide(
                                    color: Color(0xFF34544A),
                                    width: 2,
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 30),
                          Center(
                            child: SizedBox(
                              width: 200,
                              height: 48,
                              child: ElevatedButton(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF34544A),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                ),
                                onPressed: () {
                                  if (amountController.text.isNotEmpty) {
                                    _showPhoneNumberModal(
                                      amountController.text.replaceAll(',', ''),
                                      network,
                                    );
                                  }
                                },
                                child: const Text(
                                  'Continue',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 18,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 180),
                          Center(
                            child: SizedBox(
                              height: 2,
                              width: 2,
                              child: Image.asset('assets/images/applogo.jpeg'),
                            ),
                          ),
                          const SizedBox(height: 200),
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
}

/// Live-formats whatever's typed into the amount field with thousand
/// separators (e.g. "12000" -> "12,000"). The comma-free numeric value is
/// recovered downstream by stripping commas before the amount is used in
/// any request — see the "Continue" button's onPressed above.
class _ThousandsSeparatorFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    final raw = newValue.text.replaceAll(RegExp(r'[^\d]'), '');
    if (raw.isEmpty) return newValue.copyWith(text: '');

    final formatted = NumberFormat('#,##0', 'en_US').format(int.parse(raw));
    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}