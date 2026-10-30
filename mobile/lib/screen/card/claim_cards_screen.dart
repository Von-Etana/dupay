// lib/screen/card/claim_cards_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/card_provider.dart';
import '../../widgets/pin_entry_screen.dart';

class ClaimCardsScreen extends ConsumerStatefulWidget {
  /// When provided, show only this currency's claim page (no tabs)
  final String? currency;
  const ClaimCardsScreen({super.key, this.currency});

  @override
  ConsumerState<ClaimCardsScreen> createState() => _ClaimCardsScreenState();
}

class _ClaimCardsScreenState extends ConsumerState<ClaimCardsScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _showClaimDialog(String cardType) {
    final isNGN = cardType == 'NGN';
    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => Dialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(
            horizontal: 24, vertical: 40),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ── Header ──────────────────────────────────────────
              Row(children: [
                Expanded(
                  child: Text('Claim ${cardType} Card',
                      style: TextStyle(
                          fontSize: 20.sp,
                          fontWeight: FontWeight.bold)),
                ),
                GestureDetector(
                  onTap: () => Navigator.of(ctx).pop(),
                  child: Container(
                    width: 28, height: 28,
                    decoration: const BoxDecoration(
                        color: Colors.red, shape: BoxShape.circle),
                    child: const Icon(Icons.close,
                        color: Colors.white, size: 16),
                  ),
                ),
              ]),
              const SizedBox(height: 20),

              // ── Fee rows ─────────────────────────────────────────
              _FeeRow(
                label: 'Claim Fee:',
                value: isNGN ? '₦2,000' : '\$3.00',
              ),
              const SizedBox(height: 10),
              _FeeRow(
                label: 'Minimum Deposit:',
                value: isNGN ? '' : '\$3.00',
              ),
              const SizedBox(height: 24),

              // ── Buttons ──────────────────────────────────────────
              Row(children: [
                Expanded(
                  child: OutlinedButton(
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(color: Colors.grey[300]!),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(32)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    onPressed: () => Navigator.of(ctx).pop(),
                    child: const Text('Cancel',
                        style: TextStyle(color: Colors.black54)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF34544A),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(32)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    onPressed: () async {
                      Navigator.of(ctx).pop();
                      // Open the shared full-screen PIN entry.
                      final pin = await showPinEntry(
                        context,
                        title: 'Claim ${cardType} Card',
                        subtitle:
                            'Enter your transaction PIN to confirm the claim.',
                      );
                      if (pin == null || pin.length < 4) return;
                      await _processClaim(cardType, pin);
                    },
                    child: const Text('Proceed',
                        style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ]),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _processClaim(String currency, String pin) async {
    // Keep an animated loading dialog up the WHOLE time the card is being
    // created. USD creation runs in the background (create-user + create-card)
    // and can take up to ~1.5 min for a new account. Capture the dialog context
    // so we can close it even after CardScreen swaps this screen out for the
    // real card once it appears.
    BuildContext? loadingCtx;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (ctx) {
        loadingCtx = ctx;
        return _CreatingCardDialog(currency: currency);
      },
    );

    void closeLoading() {
      if (loadingCtx != null) {
        Navigator.of(loadingCtx!).pop();
        loadingCtx = null;
      }
    }

    final response = await ref
        .read(cardNotifierProvider.notifier)
        .createCard(currency: currency, transactionPin: pin);

    if (!mounted) {
      closeLoading();
      return;
    }

    if (!response.success) {
      closeLoading();
      _snack(response.message.isNotEmpty ? response.message : 'Failed',
          error: true);
      return;
    }

    // Poll until the card appears. CardScreen watches the provider and flips to
    // the real card automatically once it does. NGN shows on the first poll;
    // USD lands once the background job finishes.
    bool appeared =
        ref.read(cardNotifierProvider).cards.any((c) => c.currency == currency);
    for (var attempt = 0; attempt < 18 && !appeared; attempt++) {
      await Future.delayed(const Duration(seconds: 5));
      if (!mounted) {
        closeLoading();
        return;
      }
      await ref.read(cardNotifierProvider.notifier).fetchCards();
      appeared = ref
          .read(cardNotifierProvider)
          .cards
          .any((c) => c.currency == currency);
    }

    closeLoading();
    if (!mounted) return;
    _snack(
      appeared
          ? '$currency card is ready!'
          : 'Your card is being created and will appear shortly. Pull down to refresh.',
    );
  }

  void _snack(String msg, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(
        content: Text(msg),
        backgroundColor: error ? Colors.red : const Color(0xFF1DBF73),
      ));
  }

  @override
  Widget build(BuildContext context) {
    // When currency is provided (from CardScreen tabs), show only that currency
    final specificCurrency = widget.currency;

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Text(
            specificCurrency != null
                ? 'Claim Your ${specificCurrency} Card'
                : 'Claim Your Card',
            textAlign: TextAlign.center,
            style: TextStyle(
                fontSize: 25.sp,
                fontWeight: FontWeight.w700,
                color: Colors.white)),
          const SizedBox(height: 30),

          // When called from CardScreen (currency specified), show single page
          if (specificCurrency != null)
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(20),
                      topRight: Radius.circular(20)),
                ),
                child: specificCurrency == 'NGN'
                    ? _ClaimTab(
                        title: 'NGN Cards Available Now',
                        buttonText: 'Claim NGN Card',
                        features: const [
                          _Feat('Card Creation Fee',
                              '₦2,000 creation fee'),
                          _Feat('Online Payments',
                              'Make secure online payments with your NGN card'),
                          _Feat('Local Transactions',
                              'Use for local transactions and subscriptions'),
                        ],
                        onClaim: () => _showClaimDialog('NGN'),
                      )
                    : _ClaimTab(
                        title: 'USD Cards Available Now',
                        buttonText: 'Claim USD Card',
                        features: const [
                          _Feat('Card Creation Fee',
                              '\$3 creation fee + \$3 minimum deposit'),
                          _Feat('Currency Conversion',
                              'Convert from NGN to USD at lowest rates'),
                          _Feat('International Payments',
                              'Globally accepted on all your favourite stores'),
                        ],
                        onClaim: () => _showClaimDialog('USD'),
                      ),
              ),
            )
          else
          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                color: Color(0xFF7CA183),
                borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20)),
              ),
              child: Padding(
                padding: const EdgeInsets.only(top: 20),
                child: Container(
                  decoration: const BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20)),
                  ),
                  child: Column(children: [
                    SizedBox(height: 16.h),
                    TabBar(
                      controller: _tabController,
                      indicatorColor: const Color(0xFF34544A),
                      labelColor: const Color(0xFF34544A),
                      unselectedLabelColor: Colors.grey,
                      labelStyle: TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 16.sp),
                      tabs: const [Tab(text: 'NGN Card'), Tab(text: 'USD Card')],
                    ),
                    Expanded(
                      child: TabBarView(
                        controller: _tabController,
                        children: [
                          _ClaimTab(
                            title: 'NGN Cards Available Now',
                            buttonText: 'Claim NGN Card',
                            features: const [
                              _Feat('Card Creation Fee',
                                  '₦3,500 creation fee + ₦1,000 minimum deposit'),
                              _Feat('Online Payments',
                                  'Make secure online payments with your NGN card'),
                              _Feat('Local Transactions',
                                  'Use for local transactions and subscriptions'),
                            ],
                            onClaim: () => _showClaimDialog('NGN'),
                          ),
                          _ClaimTab(
                            title: 'USD Cards Available Now',
                            buttonText: 'Claim USD Card',
                            features: const [
                              _Feat('Card Creation Fee',
                                  '\$3 creation fee + \$3 minimum deposit'),
                              _Feat('Currency Conversion',
                                  'Convert from NGN to USD at lowest rates'),
                              _Feat('International Payments',
                                  'Globally accepted on all your favourite stores'),
                            ],
                            onClaim: () => _showClaimDialog('USD'),
                          ),
                        ],
                      ),
                    ),
                  ]),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }
}

class _Feat {
  final String title;
  final String desc;
  const _Feat(this.title, this.desc);
}

class _ClaimTab extends StatelessWidget {
  final String title;
  final String buttonText;
  final List<_Feat> features;
  final VoidCallback onClaim;
  const _ClaimTab(
      {required this.title,
      required this.buttonText,
      required this.features,
      required this.onClaim});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.symmetric(horizontal: 24),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        SizedBox(height: 20.h),
        Center(
            child: Image.asset('assets/images/virtualcard.png',
                height: 160, fit: BoxFit.contain,
                errorBuilder: (_, __, ___) => Container(
                    height: 160,
                    decoration: BoxDecoration(
                        color: const Color(0xFF34544A),
                        borderRadius: BorderRadius.circular(16)),
                    child: const Center(
                        child: Icon(Icons.credit_card,
                            color: Colors.white, size: 80))))),
        SizedBox(height: 20.h),
        Text(title,
            style: TextStyle(
                fontSize: 22.sp,
                fontWeight: FontWeight.w500,
                color: Colors.black)),
        SizedBox(height: 16.h),
        ...features.map((f) => Container(
              margin: EdgeInsets.only(bottom: 16.h),
              padding:
                  EdgeInsets.symmetric(horizontal: 12.w, vertical: 14.h),
              decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: const [
                    BoxShadow(
                        color: Colors.black12,
                        blurRadius: 2,
                        offset: Offset(0, 1))
                  ]),
              child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                        width: 24,
                        height: 24,
                        decoration: const BoxDecoration(
                            color: Color(0xFFCBDDC0),
                            shape: BoxShape.circle),
                        child: const Icon(Icons.check,
                            size: 14, color: Color(0xFF34544A))),
                    SizedBox(width: 12.w),
                    Expanded(
                        child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                          Text(f.title,
                              style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 15.sp)),
                          SizedBox(height: 2.h),
                          Text(f.desc,
                              style: TextStyle(
                                  fontSize: 13.sp,
                                  color: Colors.grey[600])),
                        ])),
                  ]),
            )),
        SizedBox(height: 32.h),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(32.r)),
                padding: EdgeInsets.symmetric(vertical: 16.h)),
            onPressed: onClaim,
            child: Text(buttonText,
                style: TextStyle(
                    fontSize: 18.sp,
                    color: Colors.white,
                    fontWeight: FontWeight.bold)),
          ),
        ),
        SizedBox(height: 24.h),
      ]),
    );
  }
}




// ─── Fee Row ──────────────────────────────────────────────────────────────────
class _FeeRow extends StatelessWidget {
  final String label;
  final String value;
  const _FeeRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: TextStyle(fontSize: 14, color: Colors.grey[600])),
        Text(value,
            style: const TextStyle(
                fontSize: 14, fontWeight: FontWeight.w600)),
      ],
    );
  }
}
/// Animated loading dialog shown while a virtual card is being created.
/// The spinner keeps moving so the user can see something is happening, and the
/// copy sets the expectation that it may take up to a minute.
class _CreatingCardDialog extends StatelessWidget {
  final String currency;
  const _CreatingCardDialog({required this.currency});

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 40),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 28, horizontal: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(
              width: 48,
              height: 48,
              child: CircularProgressIndicator(
                strokeWidth: 3,
                valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF34544A)),
              ),
            ),
            SizedBox(height: 20.h),
            Text(
              'Creating your $currency card…',
              textAlign: TextAlign.center,
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16.sp),
            ),
            SizedBox(height: 8.h),
            Text(
              'This can take up to a minute. Please keep this screen open.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey[600], fontSize: 13.sp),
            ),
          ],
        ),
      ),
    );
  }
}
