import 'package:dupay/screen/transaction/transaction_receipt_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../../providers/airtime_provider.dart';
import '../../models/airtime/airtime_network_model.dart';
import '../home/home_nav.dart';
import '../../core/utils/formatters.dart';
import '../../widgets/limit_notice.dart';

class OrderSummaryScreen extends ConsumerStatefulWidget {
  final String network;
  final String logo;
  final String amount;
  final String phoneNumber;

  const OrderSummaryScreen({
    super.key,
    required this.network,
    required this.logo,
    required this.amount,
    required this.phoneNumber,
  });

  @override
  ConsumerState<OrderSummaryScreen> createState() => _OrderSummaryScreenState();
}

class _OrderSummaryScreenState extends ConsumerState<OrderSummaryScreen> {
  final List<TextEditingController> pinControllers = List.generate(
    4,
    (_) => TextEditingController(),
  );

  @override
  void dispose() {
    for (final c in pinControllers) {
      c.dispose();
    }
    super.dispose();
  }

  void _onSubmit() async {
    // Validate PIN
    final pin = pinControllers.map((c) => c.text).join();
    if (pin.length != 4) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a 4-digit PIN')),
      );
      return;
    }

    // Get the selected network from the airtime state
    final airtimeState = ref.read(airtimeNotifierProvider);
    final networks = airtimeState.networks ?? [];

    // Find the network that matches the selected network name
    AirtimeNetworkModel? selectedNetwork;
    try {
      selectedNetwork = networks.firstWhere(
        (network) => network.name == widget.network,
      );
    } catch (e) {
      if (networks.isNotEmpty) {
        selectedNetwork = networks.first;
      }
    }

    if (selectedNetwork == null) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Network not found')));
      return;
    }

    // Get the first airtime type (you might want to add a type selector in the future)
    final airtimeType = selectedNetwork.airtimeType?.first;
    if (airtimeType == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Airtime type not available')),
      );
      return;
    }

    // Buy airtime
    final response = await ref
        .read(airtimeNotifierProvider.notifier)
        .buyAirtime(
          phoneNumber: widget.phoneNumber,
          networkId: selectedNetwork.id.toString(),
          typeId: airtimeType.id.toString(),
          amount: widget.amount,
          transactionPin: pin,
        );

    if (response.success) {
      // Navigate to success screen
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (context) => TransactionReceiptScreen(
            data: TransactionReceiptData(
              service: 'Airtime',
              type: widget.network,
              description: 'Airtime recharge for ${widget.phoneNumber}',
              amount: '\u20A6${widget.amount}',
              status: 'success',
              reference: response.data?['reference']?.toString() ?? '',
              initialBalance: response.data?['initial_balance']?.toString() ?? '',
              finalBalance: response.data?['final_balance']?.toString() ?? '',
              date: DateTime.now(),
              phoneNumber: widget.phoneNumber,
            ),
          ),
        ),
      );
    } else if (!showLimitNoticeIfNeeded(context, response.message)) {
      // Show error
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(response.message), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final airtimeState = ref.watch(airtimeNotifierProvider);
    final isLoading = airtimeState.isLoading;

    return Scaffold(
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
                    'Order Summary',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 25.sp,
                      fontWeight: FontWeight.w700,
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
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(
                    left: 16.0,
                    right: 16.0,
                    top: 24,
                    bottom: 24,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                      Text(
                        'Airtime Order Summary',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16.sp,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Container(
                        width: double.infinity,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFE0E0E0)),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _summaryRow('Service', 'Airtime Topup'),
                              _summaryRow('Airtime', widget.network),
                              _summaryRow('Type', 'VTU'),
                              _summaryRow(
                                'Price',
                                formatCurrency(widget.amount),
                              ),
                              _summaryRow('Phone Number', widget.phoneNumber),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 32),
                      Center(
                        child: Text(
                          'Enter Transaction Pin',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15.sp,
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: List.generate(4, (i) {
                          return Container(
                            width: 48,
                            margin: const EdgeInsets.symmetric(horizontal: 8),
                            child: TextField(
                              controller: pinControllers[i],
                              obscureText: true,
                              maxLength: 1,
                              keyboardType: TextInputType.number,
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                fontSize: 24,
                                letterSpacing: 2,
                              ),
                              decoration: InputDecoration(
                                counterText: '',
                                filled: true,
                                fillColor: Colors.white,
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                              ),
                              onChanged: (val) {
                                if (val.length == 1 && i < 3) {
                                  FocusScope.of(context).nextFocus();
                                }
                              },
                            ),
                          );
                        }),
                      ),
                      const SizedBox(height: 24),
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
                            onPressed: isLoading ? null : _onSubmit,
                            child:
                                isLoading
                                    ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        valueColor:
                                            AlwaysStoppedAnimation<Color>(
                                              Colors.white,
                                            ),
                                      ),
                                    )
                                    : const Text(
                                      'Submit',
                                      style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 18,
                                        color: Colors.white,
                                      ),
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
          ],
        ),
      ),
    );
  }

  Widget _summaryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: const Color(0xFF23235B),
              fontSize: 15.sp,
            ),
          ),
          Text(
            value,
            style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15.sp),
          ),
        ],
      ),
    );
  }
}

class SuccessScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Spacer(),
            const Icon(Icons.check_circle, color: Colors.green, size: 100),
            const SizedBox(height: 24),
            Center(
              child: Text(
                'Airtime purchase successful!',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 22.sp,
                  color: Colors.white,
                ),
                textAlign: TextAlign.center,
              ),
            ),
            const SizedBox(height: 32),
            SizedBox(
              width: 200,
              height: 48,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                onPressed: () {
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (context) => const HomeNav()),
                    (route) => false,
                  );
                },
                child: const Text(
                  'Done',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                    color: Color(0xFF34544A),
                  ),
                ),
              ),
            ),
            const Spacer(),
          ],
        ),
      ),
    );
  }
}