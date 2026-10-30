import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../../providers/airtime_provider.dart';
import '../../models/airtime/airtime_to_cash_networks_response.dart';
import '../../core/utils/formatters.dart';

class AirtimeToCashScreen extends ConsumerStatefulWidget {
  const AirtimeToCashScreen({super.key});

  @override
  ConsumerState<AirtimeToCashScreen> createState() =>
      _AirtimeToCashScreenState();
}

class _AirtimeToCashScreenState extends ConsumerState<AirtimeToCashScreen> {
  int selectedNetwork = 0;
  final TextEditingController amountController = TextEditingController();
  final TextEditingController airtimeDetailsController =
      TextEditingController();

  @override
  void initState() {
    super.initState();
    // Load airtime to cash networks when screen initializes
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(airtimeNotifierProvider.notifier).getAirtimeToCashNetworks();
    });
  }

  @override
  void dispose() {
    amountController.dispose();
    airtimeDetailsController.dispose();
    super.dispose();
  }

  void _showConversionModal() {
    final airtimeState = ref.read(airtimeNotifierProvider);
    final networks = airtimeState.airtimeToCashNetworks ?? [];

    if (networks.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('No networks available for conversion')),
      );
      return;
    }

    final network = networks[selectedNetwork];

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            left: 24,
            right: 24,
            top: 24,
            bottom: MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  GestureDetector(
                    onTap: () => Navigator.of(context).pop(),
                    child: const Icon(Icons.close, color: Colors.red, size: 28),
                  ),
                ],
              ),
              SizedBox(height: 8),
              Text(
                'Convert Airtime to Cash',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.black54,
                  fontSize: 16.sp,
                ),
              ),
              SizedBox(height: 8),
              CircleAvatar(
                backgroundImage:
                    network.image != null ? NetworkImage(network.image!) : null,
                radius: 40,
                backgroundColor: Colors.white,
                child:
                    network.image == null
                        ? Icon(
                          Icons.phone_android,
                          size: 40,
                          color: Color(0xFF34544A),
                        )
                        : null,
              ),
              SizedBox(height: 12),
              Text(
                network.name ?? 'Unknown Network',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF34544A),
                  fontSize: 18.sp,
                ),
              ),
              SizedBox(height: 4),
              Text(
                formatCurrency(amountController.text),
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                  fontSize: 20.sp,
                ),
              ),
              SizedBox(height: 24),
              TextField(
                controller: airtimeDetailsController,
                decoration: InputDecoration(
                  labelText: 'Airtime Details',
                  hintText: 'Enter airtime details (e.g., PIN, code)',
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
              SizedBox(height: 24),
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
                  onPressed: () async {
                    Navigator.of(context).pop();

                    final response = await ref
                        .read(airtimeNotifierProvider.notifier)
                        .convertAirtimeToCash(
                          network: network.name ?? '',
                          mode: 'convert',
                          amount: amountController.text,
                          airtimeDetails: airtimeDetailsController.text,
                        );

                    if (response.success) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text('Airtime converted successfully!'),
                          backgroundColor: Colors.green,
                        ),
                      );
                      Navigator.of(context).pop(); // Go back to previous screen
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            response.message ?? 'Failed to convert airtime',
                          ),
                          backgroundColor: Colors.red,
                        ),
                      );
                    }
                  },
                  child: const Text(
                    'Convert',
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              SizedBox(height: 24),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final airtimeState = ref.watch(airtimeNotifierProvider);
    final networks = airtimeState.airtimeToCashNetworks ?? [];
    final isLoading = airtimeState.isLoading;
    final error = airtimeState.error;

    // Show loading state
    if (isLoading && networks.isEmpty) {
      return Scaffold(
        backgroundColor: const Color(0xFF34544A),
        body: SafeArea(
          bottom: false,
          child: Column(
            children: [
              // Header
              Container(
                padding: EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                child: Row(
                  children: [
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: Icon(
                        Icons.arrow_back_ios,
                        color: Colors.white,
                        size: 24,
                      ),
                    ),
                    Expanded(
                      child: Text(
                        'Convert Airtime to Cash',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 20.sp,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    // Balances the back button so the title stays centered.
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
                            Icon(
                              Icons.phone_android,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            SizedBox(height: 16),
                            Text(
                              'Loading networks...',
                              style: TextStyle(
                                fontSize: 18.sp,
                                color: Colors.grey[600],
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            SizedBox(height: 8),
                            Text(
                              'Please wait while we fetch available networks',
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

    // Show error state
    if (error != null && networks.isEmpty) {
      return Scaffold(
        appBar: AppBar(
          title: Text('Convert Airtime to Cash'),
          backgroundColor: Color(0xFF34544A),
          foregroundColor: Colors.white,
        ),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline, size: 64, color: Colors.red),
              SizedBox(height: 16),
              Text('Error: $error'),
              SizedBox(height: 16),
              ElevatedButton(
                onPressed: () {
                  ref
                      .read(airtimeNotifierProvider.notifier)
                      .getAirtimeToCashNetworks();
                },
                child: Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    // Show empty state
    if (networks.isEmpty) {
      return Scaffold(
        appBar: AppBar(
          title: Text('Convert Airtime to Cash'),
          backgroundColor: Color(0xFF34544A),
          foregroundColor: Colors.white,
        ),
        body: Center(child: Text('No networks available for conversion')),
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
            SizedBox(height: 30),
            Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                  onPressed: () => Navigator.of(context).pop(),
                ),
                Expanded(
                  child: Text(
                    'Convert Airtime to Cash',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 25.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.refresh, color: Colors.white),
                  onPressed: () {
                    ref
                        .read(airtimeNotifierProvider.notifier)
                        .getAirtimeToCashNetworks();
                  },
                ),
              ],
            ),
            SizedBox(height: 20),
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
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SizedBox(height: 24.h),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 24.0),
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
                          padding: const EdgeInsets.symmetric(horizontal: 50.0),
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
                                                  ? Icon(
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
                                              decoration: BoxDecoration(
                                                color: Color(0xFF34544A),
                                                shape: BoxShape.circle,
                                              ),
                                              padding: EdgeInsets.all(3),
                                              child: Icon(
                                                Icons.check,
                                                color: Colors.white,
                                                size: 16,
                                              ),
                                            ),
                                          ),
                                      ],
                                    ),
                                    SizedBox(height: 6),
                                    Text(
                                      networks[i].name ?? 'Unknown Network',
                                      style: TextStyle(
                                        fontSize: 13.sp,
                                        color:
                                            isSelected
                                                ? Color(0xFF34544A)
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
                        SizedBox(height: 42),
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
                                        ? Icon(
                                          Icons.phone_android,
                                          size: 48,
                                          color: Color(0xFF34544A),
                                        )
                                        : null,
                              ),
                              SizedBox(height: 16),
                              Text(
                                network.name ?? 'Unknown Network',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF34544A),
                                  fontSize: 18.sp,
                                ),
                              ),
                              SizedBox(height: 4),
                              Text(
                                'Airtime to Cash',
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF23235B),
                                  fontSize: 22.sp,
                                ),
                              ),
                            ],
                          ),
                        ),
                        SizedBox(height: 32),
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 24.0),
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
                            decoration: InputDecoration(
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
                        SizedBox(height: 24),
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
                                  _showConversionModal();
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
                        SizedBox(height: 24),
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
}
