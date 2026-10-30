import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/utils/formatters.dart';
import '../../core/utils/phone_validator.dart';
import '../../widgets/limit_notice.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/data_provider.dart';
import '../../widgets/pin_entry_screen.dart';
import '../transaction/transaction_receipt_screen.dart';

class BuyDataScreen extends ConsumerStatefulWidget {
  const BuyDataScreen({super.key});

  @override
  ConsumerState<BuyDataScreen> createState() => _BuyDataScreenState();
}

class _BuyDataScreenState extends ConsumerState<BuyDataScreen> {
  int selectedNetwork = 0;
  int? selectedPlan;
  int? selectedDataType;
  final TextEditingController phoneController = TextEditingController();

  // Guards against double-submission (e.g. a fast double-tap on Continue,
  // or the PIN screen's auto-submit firing while a previous purchase for
  // this screen instance is still in flight) which used to stack two
  // loading dialogs on top of each other.
  bool _isPurchasing = false;

  @override
  void initState() {
    super.initState();
    // Cache-first with a 7-day TTL (same idea as the bank list): if we fetched
    // the networks within the last 7 days, show them straight from cache and
    // DON'T hit the network. Only fetch when the cache is missing or older
    // than 7 days. Use the refresh button to force a fresh pull on demand.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(dataNotifierProvider.notifier).getDataNetworksWithCache(
            cacheValidity: const Duration(days: 7),
          );
    });
  }

  @override
  void dispose() {
    phoneController.dispose();
    super.dispose();
  }

  /// Asks the user for their 4-digit transaction PIN, showing a preview of
  /// the transaction (network, plan, phone, total) above the PIN dots so the
  /// user can verify what they're about to pay for. Returns null if the
  /// screen is cancelled or dismissed.
  Future<String?> _promptTransactionPin({
    required String networkName,
    required String planName,
    required String? planSize,
    required String phoneNumber,
    required num price,
  }) async {
    final amountStr = formatCurrency(price);

    // Build a clean plan label: "1.5GB" + " - " + plan.name when both exist.
    String planLabel = planName;
    if (planSize != null && planSize.isNotEmpty) {
      // Avoid duplicating the size if it's already inside the plan name.
      if (!planName.toLowerCase().contains(planSize.toLowerCase())) {
        planLabel = '$planSize · $planName';
      }
    }

    return showPinEntry(
      context,
      amount: amountStr,
      transactionDetails: [
        PinTransactionDetail('Service',  'Data Bundle',
            icon: Icons.wifi),
        PinTransactionDetail('Network',  networkName,
            icon: Icons.sim_card_outlined),
        PinTransactionDetail('Plan',     planLabel),
        PinTransactionDetail('Phone',    phoneNumber,
            icon: Icons.phone_outlined),
        PinTransactionDetail('Total',    amountStr, emphasized: true),
      ],
    );
  }

  void _showPhoneNumberModal() {
    final dataState = ref.read(dataNotifierProvider);
    final networks = dataState.dataNetworks;

    if (networks == null || networks.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No networks available'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    if (selectedPlan == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select a data plan'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final network = networks[selectedNetwork];
    final dataTypes = network.dataType;

    if (dataTypes == null || dataTypes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No data types available for this network'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final dataType = dataTypes[selectedDataType ?? 0];
    final plans = dataType.plan;

    if (plans == null || plans.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No plans available for this data type'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final plan = plans[selectedPlan!];

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
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  GestureDetector(
                    onTap: () => Navigator.of(context).pop(),
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(
                        color: Colors.red,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(
                        Icons.close,
                        color: Colors.white,
                        size: 16,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Text(
                'DIRECT GIFTING',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                  fontSize: 16.sp,
                ),
              ),
              const SizedBox(height: 16),
              CircleAvatar(
                backgroundImage:
                    network.image != null ? NetworkImage(network.image!) : null,
                radius: 40,
                backgroundColor: Colors.white,
                child:
                    network.image == null
                        ? Text(
                          network.name?.substring(0, 1).toUpperCase() ?? 'N',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[600],
                          ),
                        )
                        : null,
              ),
              const SizedBox(height: 12),
              Text(
                '${network.name} | DIRECT GIFTING',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF34544A),
                  fontSize: 16.sp,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                plan.size ?? 'Unknown Size',
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF34544A),
                  fontSize: 24.sp,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                formatCurrency(plan.price ?? 0),
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: const Color(0xFF34544A),
                  fontSize: 18.sp,
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
                inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                onChanged: (_) {
                  // Clear the error as soon as the user starts fixing it.
                  if (phoneError != null) {
                    setModalState(() => phoneError = null);
                  }
                },
                decoration: InputDecoration(
                  counterText: '',
                  errorText: phoneError,
                  prefixIcon: const Icon(Icons.phone, color: Colors.grey),
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
                  onPressed: () async {
                    if (_isPurchasing) return;

                    final error =
                        validateNigerianPhone(phoneController.text);
                    if (error != null) {
                      setModalState(() => phoneError = error);
                      return;
                    }

                    // Tidy up to the 11-digit local form so the PIN preview,
                    // the receipt and the provider all see 0XXXXXXXXXX.
                    phoneController.text =
                        normalizeNigerianPhone(phoneController.text);

                    // Capture a stable reference to the outer screen so we
                    // can show snackbars + close dialogs without depending on
                    // the (about-to-be-disposed) modal's context.
                    final rootMessenger = ScaffoldMessenger.of(this.context);
                    final rootNavigator = Navigator.of(this.context);

                    // Close the phone-number bottom sheet.
                    Navigator.of(context).pop();

                    // Ask the user for their transaction PIN — the PIN
                    // screen also shows a preview of the purchase so they
                    // can verify the network / plan / phone / amount
                    // before committing.
                    final pin = await _promptTransactionPin(
                      networkName: network.name ?? 'Network',
                      planName:    plan.name ?? 'Data Plan',
                      planSize:    plan.size,
                      phoneNumber: phoneController.text,
                      price:       plan.price ?? 0,
                    );
                    if (pin == null || pin.isEmpty) {
                      // User cancelled — nothing else to do.
                      return;
                    }

                    if (!mounted) return;
                    if (_isPurchasing) return;
                    setState(() => _isPurchasing = true);

                    // Show loading dialog using the root navigator + capture
                    // a dedicated context so we can dismiss it safely later.
                    BuildContext? loadingDialogCtx;
                    showDialog(
                      context: this.context,
                      barrierDismissible: false,
                      builder: (ctx) {
                        loadingDialogCtx = ctx;
                        return const Center(
                          child: CircularProgressIndicator(
                            color: Color.fromARGB(255, 90, 99, 96),
                          ),
                        );
                      },
                    );

                    void closeLoading() {
                      if (loadingDialogCtx != null &&
                          Navigator.of(loadingDialogCtx!).canPop()) {
                        Navigator.of(loadingDialogCtx!).pop();
                        loadingDialogCtx = null;
                      }
                    }

                    try {
                      // Diagnostic — paste this in chat if the error returns.
                      print('🩺 BuyData diagnostic:');
                      print('   network.id        = ${network.id}');
                      print('   network.name      = ${network.name}');
                      print('   dataType.id       = ${dataType.id}');
                      print('   plan.id           = ${plan.id}');
                      print('   plan.code         = ${plan.code}');
                      print('   plan.variationCode= ${plan.variationCode}');
                      print('   plan.name         = ${plan.name}');
                      print('   plan.size         = ${plan.size}');
                      print('   plan.price        = ${plan.price}');
                      print('   phone             = ${phoneController.text}');

                      final response = await ref
                          .read(dataNotifierProvider.notifier)
                          .buyData(
                            phoneNumber: phoneController.text,
                            networkId: network.id.toString(),
                            // Backend expects the local DB plan id (and the
                            // owning data_type id) — not the variation_code.
                            planId: plan.id.toString(),
                            dataTypeId: dataType.id.toString(),
                            transactionPin: pin,
                          );

                      closeLoading();
                      if (!mounted) return;
                      setState(() => _isPurchasing = false);

                      if (response.success) {
                        final data = response.data ?? {};
                        String planLabel = plan.name ?? 'Data Plan';
                        if (plan.size != null &&
                            plan.size!.isNotEmpty &&
                            !planLabel.toLowerCase().contains(
                              plan.size!.toLowerCase(),
                            )) {
                          planLabel = '${plan.size} · $planLabel';
                        }

                        rootNavigator.pushReplacement(
                          MaterialPageRoute(
                            builder: (context) => TransactionReceiptScreen(
                              data: TransactionReceiptData(
                                service: 'Data Bundle',
                                type: '${network.name} | $planLabel',
                                description:
                                    'Data purchase for ${phoneController.text}',
                                amount: formatCurrency(plan.price ?? 0),
                                status: 'success',
                                reference:
                                    data['reference']?.toString() ?? '',
                                initialBalance:
                                    data['initial_balance']?.toString() ??
                                    '',
                                finalBalance:
                                    data['final_balance']?.toString() ?? '',
                                date: DateTime.now(),
                                phoneNumber: phoneController.text,
                              ),
                            ),
                          ),
                        );
                      } else {
                        // Failed purchase: the phone-number modal and PIN
                        // screen are already closed at this point, so the
                        // user is already back on the buy-data form. Just
                        // show why it failed — no Retry button, no separate
                        // error screen.
                        if (!showLimitNoticeIfNeeded(
                            this.context, response.message)) {
                          rootMessenger.showSnackBar(
                            SnackBar(
                              content: Text(response.message),
                              backgroundColor: Colors.red,
                            ),
                          );
                        }
                      }
                    } catch (e) {
                      closeLoading();
                      if (!mounted) return;
                      setState(() => _isPurchasing = false);
                      rootMessenger.showSnackBar(
                        SnackBar(
                          content: Text('Error: $e'),
                          backgroundColor: Colors.red,
                        ),
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
              const SizedBox(height: 24),
            ],
          ),
          ),
        );
      },
    );
  }

  // Simple grey placeholder block used to build the loading skeleton.
  Widget _skel(double w, double h, {double r = 6}) => Container(
        width: w,
        height: h,
        decoration: BoxDecoration(
          color: Colors.grey[200],
          borderRadius: BorderRadius.circular(r),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final dataState = ref.watch(dataNotifierProvider);
    final networks = dataState.dataNetworks;

    if (dataState.isLoading) {
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
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(
                        Icons.arrow_back_ios,
                        color: Colors.white,
                        size: 24,
                      ),
                    ),
                    Expanded(
                      child: Text(
                        'Purchase Data',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 20.sp,
                          fontWeight: FontWeight.bold,
                          color: const Color.fromARGB(255, 255, 255, 255),
                        ),
                      ),
                    ),
                    
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
                        physics: const NeverScrollableScrollPhysics(),
                        child: Padding(
                          padding: EdgeInsets.fromLTRB(24.w, 30.h, 24.w, 24.h),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Skeleton that mirrors the real layout so the
                              // page doesn't "jump" when the data lands.
                              _skel(150, 18),
                              SizedBox(height: 24.h),
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: List.generate(
                                  4,
                                  (_) => Column(
                                    children: [
                                      _skel(60, 60, r: 30),
                                      SizedBox(height: 8.h),
                                      _skel(40, 10),
                                    ],
                                  ),
                                ),
                              ),
                              SizedBox(height: 32.h),
                              _skel(120, 16),
                              SizedBox(height: 16.h),
                              ...List.generate(
                                6,
                                (_) => Padding(
                                  padding: EdgeInsets.only(bottom: 12.h),
                                  child: _skel(double.infinity, 60, r: 12),
                                ),
                              ),
                            ],
                          ),
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

    if (dataState.error != null) {
      return Scaffold(
        backgroundColor: Colors.white,
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                Icons.wifi_tethering_error_rounded_outlined,
                size: 64,
                color: Colors.grey[400],
              ),
              const SizedBox(height: 16),
              Text(
                'Error: ${dataState.error}',
                style: TextStyle(color: Colors.grey[800]),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                onPressed: () {
                  ref.read(dataNotifierProvider.notifier).getDataNetworks(forceRefresh: true);
                },
                child: const Text(
                  'Retry',
                  style: TextStyle(color: Colors.white),
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (networks == null || networks.isEmpty) {
      return Scaffold(
        backgroundColor: const Color.fromARGB(255, 235, 239, 237),
        body: const Center(
          child: SizedBox(
            width: 40,
            height: 40,
            child: CircularProgressIndicator(
              strokeWidth: 3,
              valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF34544A)),
            ),
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
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(
                      Icons.arrow_back_ios,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                  Expanded(
                    child: Text(
                      'Purchase Data',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                         color: const Color.fromARGB(255, 255, 255, 255),
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
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            SizedBox(height: 30.h),

                            // Available Network Section
                            Text(
                              'Available Network',
                              style: TextStyle(
                                fontSize: 18.sp,
                                fontWeight: FontWeight.bold,
                                color: Colors.grey[800],
                              ),
                            ),
                            SizedBox(height: 20.h),

                            // Network Selection
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: List.generate(networks.length, (i) {
                                final isSelected = selectedNetwork == i;
                                final currentNetwork = networks[i];
                                return GestureDetector(
                                  onTap: () {
                                    setState(() {
                                      selectedNetwork = i;
                                      selectedPlan = null;
                                      selectedDataType = null;
                                    });
                                  },
                                  child: Column(
                                    children: [
                                      Stack(
                                        children: [
                                          CircleAvatar(
                                            radius: 30,
                                            backgroundColor: Colors.grey[300],
                                            backgroundImage:
                                                currentNetwork.image != null
                                                    ? NetworkImage(
                                                      currentNetwork.image!,
                                                    )
                                                    : null,
                                            child:
                                                currentNetwork.image == null
                                                    ? Text(
                                                      currentNetwork.name
                                                              ?.substring(0, 1)
                                                              .toUpperCase() ??
                                                          'N',
                                                      style: TextStyle(
                                                        fontSize: 16,
                                                        fontWeight:
                                                            FontWeight.bold,
                                                        color: Colors.grey[600],
                                                      ),
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
                                                  4,
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
                                      const SizedBox(height: 8),
                                      Text(
                                        currentNetwork.name ?? 'Unknown',
                                        style: TextStyle(
                                          fontSize: 14.sp,
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

                            SizedBox(height: 30.h),

                            // Select Plan Section
                            Text(
                              'Select Plan',
                              style: TextStyle(
                                fontSize: 18.sp,
                                fontWeight: FontWeight.bold,
                                color: Colors.grey[800],
                              ),
                            ),
                            SizedBox(height: 20.h),

                            // Data Type Selection (if multiple types available)
                            if (network.dataType != null &&
                                network.dataType!.length > 1) ...[
                              Text(
                                'Select Data Type',
                                style: TextStyle(
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.grey[800],
                                ),
                              ),
                              SizedBox(height: 16.h),
                              Row(
                                children: List.generate(
                                  network.dataType!.length,
                                  (i) {
                                    final isSelected = selectedDataType == i;
                                    final dataType = network.dataType![i];
                                    return GestureDetector(
                                      onTap: () {
                                        setState(() {
                                          selectedDataType = i;
                                          selectedPlan = null;
                                        });
                                      },
                                      child: Container(
                                        margin: const EdgeInsets.only(
                                          right: 12,
                                        ),
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 16,
                                          vertical: 8,
                                        ),
                                        decoration: BoxDecoration(
                                          color:
                                              isSelected
                                                  ? const Color(0xFF34544A)
                                                  : Colors.grey[200],
                                          borderRadius: BorderRadius.circular(
                                            20,
                                          ),
                                        ),
                                        child: Text(
                                          dataType.name ?? 'Unknown',
                                          style: TextStyle(
                                            color:
                                                isSelected
                                                    ? Colors.white
                                                    : Colors.black,
                                            fontWeight: FontWeight.w500,
                                          ),
                                        ),
                                      ),
                                    );
                                  },
                                ),
                              ),
                              SizedBox(height: 20.h),
                            ],

                            // Plan Options
                            if (network.dataType != null &&
                                network.dataType!.isNotEmpty) ...[
                              Builder(
                                builder: (context) {
                                  final dataType =
                                      network.dataType![selectedDataType ?? 0];
                                  final plans = dataType.plan;

                                  if (plans == null || plans.isEmpty) {
                                    return Container(
                                      padding: const EdgeInsets.all(20),
                                      child: Text(
                                        'No plans available for this data type',
                                        style: TextStyle(
                                          color: Colors.grey[600],
                                          fontSize: 16.sp,
                                        ),
                                      ),
                                    );
                                  }

                                  return Column(
                                    children: List.generate(plans.length, (i) {
                                      final isSelected = selectedPlan == i;
                                      final plan = plans[i];
                                      return GestureDetector(
                                        onTap: () {
                                          setState(() {
                                            selectedPlan = i;
                                          });
                                          // Open the phone-number / PIN flow
                                          // immediately when a plan is tapped,
                                          // so the user doesn't have to scroll
                                          // down and press Continue.
                                          _showPhoneNumberModal();
                                        },
                                        child: Container(
                                          margin: const EdgeInsets.only(
                                            bottom: 12,
                                          ),
                                          padding: const EdgeInsets.all(16),
                                          decoration: BoxDecoration(
                                            color: Colors.white,
                                            borderRadius: BorderRadius.circular(
                                              12,
                                            ),
                                            border: Border.all(
                                              color:
                                                  isSelected
                                                      ? const Color(0xFF34544A)
                                                      : Colors.white,
                                              width: isSelected ? 2 : 1,
                                            ),
                                            boxShadow: [
                                              BoxShadow(
                                                color: Colors.grey.withOpacity(
                                                  0.1,
                                                ),
                                                spreadRadius: 1,
                                                blurRadius: 3,
                                                offset: const Offset(0, 2),
                                              ),
                                            ],
                                          ),
                                          child: Row(
                                            children: [
                                              CircleAvatar(
                                                radius: 20,
                                                backgroundColor:
                                                    Colors.grey[300],
                                                backgroundImage:
                                                    network.image != null
                                                        ? NetworkImage(
                                                          network.image!,
                                                        )
                                                        : null,
                                                child:
                                                    network.image == null
                                                        ? Text(
                                                          network.name
                                                                  ?.substring(
                                                                    0,
                                                                    1,
                                                                  )
                                                                  .toUpperCase() ??
                                                              'N',
                                                          style: TextStyle(
                                                            fontSize: 14,
                                                            fontWeight:
                                                                FontWeight.bold,
                                                            color:
                                                                Colors
                                                                    .grey[600],
                                                          ),
                                                        )
                                                        : null,
                                              ),
                                              const SizedBox(width: 16),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment:
                                                      CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      '${plan.network} | ${plan.name ?? 'Unknown Plan'}',
                                                      style: TextStyle(
                                                        fontWeight:
                                                            FontWeight.w600,
                                                        fontSize: 14.sp,
                                                        color: Colors.black,
                                                      ),
                                                    ),
                                                    const SizedBox(height: 4),

                                                    // if (plan.size != null) ...[
                                                    //   const SizedBox(height: 2),
                                                    //   Text(
                                                    //     plan.size!,
                                                    //     style: TextStyle(
                                                    //       fontSize: 12.sp,
                                                    //       color:
                                                    //           Colors.grey[600],
                                                    //     ),
                                                    //   ),
                                                    // ],
                                                  ],
                                                ),
                                              ),
                                              Text(
                                                formatCurrency(plan.price ?? 0),
                                                style: TextStyle(
                                                  fontWeight: FontWeight.bold,
                                                  fontSize: 16.sp,
                                                  color: Colors.black,
                                                ),
                                              ),
                                              // Icon(
                                              //   Icons.arrow_forward_ios,
                                              //   color: Colors.grey[400],
                                              //   size: 16,
                                              // ),
                                            ],
                                          ),
                                        ),
                                      );
                                    }),
                                  );
                                },
                              ),
                            ],

                            SizedBox(height: 30.h),
                          ],
                        ),
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