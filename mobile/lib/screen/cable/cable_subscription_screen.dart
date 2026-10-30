// lib/screen/cable/cable_subscription_screen.dart

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../core/utils/formatters.dart';
import '../../providers/cable_provider.dart';
import '../transaction/transaction_summary_screen.dart';

class CableSubscriptionScreen extends ConsumerStatefulWidget {
  const CableSubscriptionScreen({super.key});

  @override
  ConsumerState<CableSubscriptionScreen> createState() =>
      _CableSubscriptionScreenState();
}

class _CableSubscriptionScreenState
    extends ConsumerState<CableSubscriptionScreen> {
  int selectedProvider = 0;
  int? selectedPlan;
  bool _isInitialLoad = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadCableData());
  }

  Future<void> _loadCableData({bool forceRefresh = false}) async {
    final notifier = ref.read(cableNotifierProvider.notifier);
    if (forceRefresh) {
      await notifier.refresh();
    } else {
      await notifier.getCablesWithCache();
    }
    if (_isInitialLoad) setState(() => _isInitialLoad = false);
  }

  Future<void> _onRefresh() => _loadCableData(forceRefresh: true);

  // ── Open plan modal with verify → summary flow ───────────────────────────
  void _showPlanModal(Map<String, dynamic> plan, String providerName,
      String logo, String planId, String cableId) {
    final decoderCtrl = TextEditingController();
    final phoneCtrl   = TextEditingController();
    String? verifiedName;
    String? verifyError;
    bool isVerifying = false;
    Timer? debounce;

    showModalBottomSheet(
      backgroundColor: Colors.white,
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModal) {
            Future<void> verify() async {
              if (decoderCtrl.text.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
                    content: Text('Enter decoder number first'),
                    backgroundColor: Colors.red));
                return;
              }
              setModal(() => isVerifying = true);

              final response = await ref
                  .read(cableNotifierProvider.notifier)
                  .verifyCableNumber(
                    planId: planId,
                    cableId: cableId,
                    decoderNumber: decoderCtrl.text,
                  );

              if (!mounted) return;
              setModal(() => isVerifying = false);

              if (response.success) {
                final dataField = response.data?['data'];
                final data = dataField is Map<String, dynamic>
                    ? dataField
                    : (response.data ?? {});
                // Prefer the actual customer name (the most informative thing
                // we can show), then fall back to the API's success message,
                // and finally a generic "Verified" so we always show *some*
                // confirmation rather than the static placeholder we used to
                // show even when the API gave us a real message.
                final name = data['customer_name']?.toString() ??
                    data['name']?.toString() ??
                    (response.message.isNotEmpty ? response.message : 'Verified');
                setModal(() => verifiedName = name);
              } else {
                setModal(() {
                  verifiedName = null;
                  verifyError = response.message.isNotEmpty
                      ? response.message
                      : 'Decoder / IUC number not found. Please check and retry.';
                });
              }
            }

            void proceed() {
              if (verifiedName == null) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
                    content: Text('Please verify decoder number first'),
                    backgroundColor: Colors.orange));
                return;
              }
              if (phoneCtrl.text.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
                    content: Text('Enter phone number'),
                    backgroundColor: Colors.red));
                return;
              }

              final decoder = decoderCtrl.text;
              final phone   = phoneCtrl.text;
              final amount  = plan['price'] ?? plan['amount'];

              Navigator.of(ctx).pop(); // close sheet

              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => TransactionSummaryScreen(
                    data: TransactionSummaryData(
                      service: 'Cable Subscription',
                      from: 'Wallet',
                      accountName: '$providerName • $verifiedName',
                      accountNumber: decoder,
                      amount: '₦$amount',
                      description: '${plan['name']} • $phone',
                      onConfirm: (pin) async {
                        final response = await ref
                            .read(cableNotifierProvider.notifier)
                            .buyCable(
                              phoneNumber: phone,
                              planId: planId,
                              cableId: cableId,
                              decoderNumber: decoder,
                              transactionPin: pin,
                            );
                        return {
                          'success': response.success,
                          'message': response.message,
                          'reference': response.data?['reference']
                                  ?.toString() ??
                              '',
                          'description':
                              '$providerName ${plan['name']} for $decoder',
                          'initial_balance':
                              response.data?['initial_balance']?.toString() ??
                                  '',
                          'final_balance':
                              response.data?['final_balance']?.toString() ?? '',
                        };
                      },
                    ),
                  ),
                ),
              );
            }

            return Padding(
              padding: EdgeInsets.only(
                  left: 24,
                  right: 24,
                  top: 24,
                  bottom: MediaQuery.of(ctx).viewInsets.bottom + 24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Close button
                  Row(mainAxisAlignment: MainAxisAlignment.end, children: [
                    GestureDetector(
                      onTap: () => Navigator.of(ctx).pop(),
                      child: const Icon(Icons.close,
                          color: Colors.red, size: 28),
                    ),
                  ]),

                  // Provider logo + name
                  CircleAvatar(
                    backgroundImage:
                        logo.isNotEmpty ? NetworkImage(logo) : null,
                    radius: 32,
                    backgroundColor: Colors.white,
                    child: logo.isEmpty
                        ? Text(providerName.substring(0, 1).toUpperCase(),
                            style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Colors.grey[600]))
                        : null,
                  ),
                  const SizedBox(height: 8),
                  Text(providerName,
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blueGrey,
                          fontSize: 15.sp)),
                  const SizedBox(height: 4),
                  Text(plan['name'] as String,
                      style: TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 17.sp),
                      textAlign: TextAlign.center),
                  Text(formatCurrency(plan['price'] ?? plan['amount'] ?? 0),
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 20.sp,
                          color: Colors.black)),
                  const SizedBox(height: 20),

                  // Decoder number + verify
                  Row(children: [
                    Expanded(
                      child: TextField(
                        controller: decoderCtrl,
                        keyboardType: TextInputType.number,
                        onChanged: (val) {
                          setModal(() { verifiedName = null; verifyError = null; });
                          debounce?.cancel();
                          // DSTV/GOTV IUC numbers are 10 digits
                          if (val.length >= 10) {
                            debounce = Timer(
                              const Duration(milliseconds: 800),
                              () => verify(),
                            );
                          }
                        },
                        decoration: InputDecoration(
                          prefixIcon: const Icon(
                              Icons.confirmation_number_outlined),
                          hintText: 'Decoder / IUC number',
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12)),
                          filled: true,
                          fillColor: const Color(0xFFF8FAF9),
                          suffixIcon: isVerifying
                              ? const Padding(
                                  padding: EdgeInsets.all(12),
                                  child: SizedBox(
                                    width: 18, height: 18,
                                    child: CircularProgressIndicator(
                                        strokeWidth: 2,
                                        color: Color(0xFF34544A)),
                                  ))
                              : verifiedName != null
                                  ? const Icon(Icons.check_circle,
                                      color: Colors.green)
                                  : verifyError != null
                                      ? const Icon(Icons.cancel,
                                          color: Colors.red)
                                      : null,
                        ),
                      ),
                    ),

                  ]),

                  // Verified name or error — shown inline under the field
                  if (verifiedName != null) ...[
                    const SizedBox(height: 8),
                    Row(children: [
                      const Icon(Icons.check_circle,
                          color: Colors.green, size: 18),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(verifiedName!,
                            style: const TextStyle(
                                color: Colors.green,
                                fontWeight: FontWeight.bold)),
                      ),
                    ]),
                  ] else if (verifyError != null) ...[
                    const SizedBox(height: 8),
                    Row(crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                      const Icon(Icons.error_outline,
                          color: Colors.red, size: 18),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(verifyError!,
                            style: const TextStyle(
                                color: Colors.red, fontSize: 13)),
                      ),
                    ]),
                  ],
                  const SizedBox(height: 16),

                  // Phone number
                  TextField(
                    controller: phoneCtrl,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      prefixIcon: const Icon(Icons.phone),
                      hintText: 'Phone number',
                      border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12)),
                      filled: true,
                      fillColor: const Color(0xFFF8FAF9),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Continue button — only active after verification
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: verifiedName != null
                            ? const Color(0xFF34544A)
                            : Colors.grey[300],
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                      onPressed: verifiedName != null ? proceed : null,
                      child: Text(
                        verifiedName == null
                            ? 'Verify decoder first'
                            : 'Continue',
                        style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                            color: verifiedName != null
                                ? Colors.white
                                : Colors.grey[600]),
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  // ── State builders ────────────────────────────────────────────────────────

  Widget _header({bool showRefresh = true}) => Row(children: [
        IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.arrow_back_ios,
                color: Colors.white, size: 24)),
        Expanded(
            child: Text('Cable Subscription',
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 20.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.white))),
        const SizedBox(width: 48),
      ]);

  Widget _shell({required Widget child}) => Scaffold(
        backgroundColor: const Color(0xFF34544A),
        body: SafeArea(
          bottom: false,
          child: Column(children: [
            _header(),
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                    color: Color(0xFF7CA183),
                    borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20))),
                child: Padding(
                  padding: const EdgeInsets.only(top: 20),
                  child: Container(
                    decoration: const BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.only(
                            topLeft: Radius.circular(20),
                            topRight: Radius.circular(20))),
                    child: child,
                  ),
                ),
              ),
            ),
          ]),
        ),
      );

  @override
  Widget build(BuildContext context) {
    final cableState = ref.watch(cableNotifierProvider);
    final providers  = cableState.cableProviders;

    if (cableState.isLoading && !cableState.hasData) {
      return _shell(
          child: const Center(
              child: CircularProgressIndicator(color: Color(0xFF34544A))));
    }

    if (cableState.error != null && !cableState.hasData) {
      return _shell(
          child: Center(
              child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
            Icon(Icons.error_outline, size: 64, color: Colors.red[300]),
            const SizedBox(height: 16),
            Text(cableState.error!,
                textAlign: TextAlign.center,
                style:
                    TextStyle(fontSize: 14.sp, color: Colors.grey[600])),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () => _loadCableData(forceRefresh: true),
              icon: const Icon(Icons.refresh, color: Colors.white),
              label: const Text('Try Again',
                  style: TextStyle(color: Colors.white)),
              style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF34544A),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12))),
            ),
          ])));
    }

    if (providers == null || providers.isEmpty) {
      return _shell(
          child: Center(
              child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
            Icon(Icons.tv_off, size: 64, color: Colors.grey[400]),
            const SizedBox(height: 16),
            Text('No cable providers available',
                style:
                    TextStyle(fontSize: 16.sp, color: Colors.grey[600])),
          ])));
    }

    final provider = providers[selectedProvider];
    final plans    = provider.cableList ?? [];

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Row(children: [
            IconButton(
                icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                onPressed: () => Navigator.of(context).pop()),
            Expanded(
                child: Text('Cable Subscription',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 25.sp,
                        fontWeight: FontWeight.w700,
                        color: Colors.white))),
            const SizedBox(width: 48),
          ]),
          const SizedBox(height: 20),
          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(20),
                      topRight: Radius.circular(20))),
              child: Padding(
                padding: const EdgeInsets.only(top: 20),
                child: Container(
                  decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20))),
                  child: RefreshIndicator(
                    onRefresh: _onRefresh,
                    color: const Color(0xFF34544A),
                    child: SingleChildScrollView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                        SizedBox(height: 24.h),
                        Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 24.0),
                          child: Text('Select TV',
                              style: TextStyle(
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.bold)),
                        ),
                        SizedBox(height: 18.h),

                        // Provider logos
                        Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16.0),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: List.generate(providers.length,
                                (i) {
                              final cur = providers[i];
                              final isSelected = selectedProvider == i;
                              return GestureDetector(
                                onTap: () => setState(() {
                                  selectedProvider = i;
                                  selectedPlan = null;
                                }),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 18.0),
                                  child: Column(children: [
                                    Container(
                                      decoration: BoxDecoration(
                                          border: Border.all(
                                              color: isSelected
                                                  ? const Color(
                                                      0xFF34544A)
                                                  : Colors.grey,
                                              width: isSelected ? 2 : 1),
                                          shape: BoxShape.circle),
                                      child: CircleAvatar(
                                        radius: 28,
                                        backgroundColor: Colors.white,
                                        backgroundImage:
                                            cur.image != null
                                                ? NetworkImage(cur.image!)
                                                : null,
                                        child: cur.image == null
                                            ? Text(
                                                cur.name
                                                        ?.substring(0, 1)
                                                        .toUpperCase() ??
                                                    'C',
                                                style: const TextStyle(
                                                    fontSize: 16,
                                                    fontWeight:
                                                        FontWeight.bold))
                                            : null,
                                      ),
                                    ),
                                    const SizedBox(height: 10),
                                    Text(cur.name ?? 'Unknown',
                                        style: TextStyle(
                                            fontSize: 15.sp,
                                            color: isSelected
                                                ? const Color(0xFF34544A)
                                                : Colors.grey[500],
                                            fontWeight: FontWeight.w500)),
                                  ]),
                                ),
                              );
                            }),
                          ),
                        ),
                        SizedBox(height: 24.h),

                        Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 24.0),
                          child: Text('Select Plan',
                              style: TextStyle(
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.bold)),
                        ),
                        const SizedBox(height: 12),

                        // Plan list
                        SizedBox(
                          height:
                              MediaQuery.of(context).size.height * 0.5,
                          child: ListView.separated(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16.0, vertical: 8),
                            itemCount: plans.length,
                            separatorBuilder: (_, __) =>
                                const SizedBox(height: 12),
                            itemBuilder: (context, idx) {
                              final plan     = plans[idx];
                              final isSelected = selectedPlan == idx;
                              // Tapping anywhere on the tile immediately
                              // opens the decoder/phone modal
                              return GestureDetector(
                                onTap: () {
                                  setState(() => selectedPlan = idx);
                                  _showPlanModal(
                                    {
                                      'name': plan.name,
                                      'price': plan.amount,
                                    },
                                    provider.name ?? 'Unknown',
                                    provider.image ?? '',
                                    plan.id.toString(),
                                    provider.id.toString(),
                                  );
                                },
                                child: Container(
                                  decoration: BoxDecoration(
                                      color: Colors.white,
                                      borderRadius:
                                          BorderRadius.circular(12),
                                      border: Border.all(
                                          color: isSelected
                                              ? const Color(0xFF34544A)
                                              : Colors.transparent,
                                          width: 2),
                                      boxShadow: const [
                                        BoxShadow(
                                            color: Colors.black12,
                                            blurRadius: 4,
                                            offset: Offset(0, 2))
                                      ]),
                                  child: ListTile(
                                    leading: Icon(
                                        isSelected
                                            ? Icons.radio_button_checked
                                            : Icons.radio_button_off,
                                        color: const Color(0xFF34544A)),
                                    title: Text(
                                        plan.name ?? 'Unknown Plan',
                                        style: TextStyle(
                                            fontWeight: FontWeight.w600,
                                            fontSize: 15.sp)),
                                    subtitle: Text(
                                        formatCurrency(plan.amount ?? 0),
                                        style: TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 18.sp,
                                            color: Colors.black)),
                                    trailing: const Icon(
                                        Icons.arrow_forward_ios,
                                        color: Color(0xFF34544A),
                                        size: 16),
                                  ),
                                ),
                              );
                            },
                          ),
                        ),
                      ]),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  String _formatLastUpdated(DateTime t) {
    final diff = DateTime.now().difference(t);
    if (diff.inMinutes < 1) return 'Just now';
    if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
    if (diff.inHours < 24) return '${diff.inHours}h ago';
    return '${diff.inDays}d ago';
  }
}