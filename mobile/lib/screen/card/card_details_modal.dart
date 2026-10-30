// lib/screen/card/card_details_modal.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:webview_flutter/webview_flutter.dart';
import '../../providers/card_provider.dart';

// ── Shared top-of-screen copy banner ──────────────────────────────────────────
// Inserts an OverlayEntry pinned to the top of the screen so the toast appears
// above the modal sheet (a ScaffoldMessenger snackbar would render at the
// bottom and feel disconnected from the copy action).
void _showCopyBanner(BuildContext context, String label) {
  final overlay = Overlay.of(context, rootOverlay: true);
  late OverlayEntry entry;
  entry = OverlayEntry(
    builder: (_) => Positioned(
      top: MediaQuery.of(context).padding.top + 12,
      left: 24,
      right: 24,
      child: Material(
        color: Colors.transparent,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
          decoration: BoxDecoration(
            color: const Color(0xFF34544A),
            borderRadius: BorderRadius.circular(12),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.18),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(children: [
            const Icon(Icons.check_circle, color: Colors.white, size: 18),
            const SizedBox(width: 10),
            Text(
              '$label copied!',
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w600,
                fontSize: 14,
              ),
            ),
          ]),
        ),
      ),
    ),
  );
  overlay.insert(entry);
  Future.delayed(const Duration(seconds: 2), () {
    if (entry.mounted) entry.remove();
  });
}

void _copyWithBanner(BuildContext ctx, String text, String label) {
  if (text.isEmpty || text == '—') return;
  Clipboard.setData(ClipboardData(text: text));
  _showCopyBanner(ctx, label);
}

class CardDetailsModal extends ConsumerStatefulWidget {
  final VirtualCardModel card;
  const CardDetailsModal({super.key, required this.card});

  @override
  ConsumerState<CardDetailsModal> createState() => _CardDetailsModalState();
}

class _CardDetailsModalState extends ConsumerState<CardDetailsModal> {
  bool _loading = false;
  bool _failed = false;
  WebViewController? _numberCtrl;
  WebViewController? _cvvCtrl;

  @override
  void initState() {
    super.initState();
    // USD cards keep the PAN/CVV in a secure vault: StroWallet returns short-
    // lived URLs (~2 min) whose pages render the number/CVV via JavaScript. The
    // raw digits are never exposed as data, so they can only be shown inside a
    // WebView. Fetch fresh URLs when the sheet opens.
    if (widget.card.currency == 'USD') {
      _reveal();
    }
  }

  Future<void> _reveal() async {
    setState(() {
      _loading = true;
      _failed = false;
    });
    final res = await ref
        .read(cardNotifierProvider.notifier)
        .revealCard(widget.card.currency);
    if (!mounted) return;
    final numberUrl = res?['card_number_url'] ?? '';
    final cvvUrl = res?['cvv_url'] ?? '';
    if (numberUrl.isEmpty) {
      setState(() {
        _loading = false;
        _failed = true;
      });
      return;
    }
    setState(() {
      _loading = false;
      _numberCtrl = _buildWebController(numberUrl);
      _cvvCtrl = cvvUrl.isNotEmpty ? _buildWebController(cvvUrl) : null;
    });
  }

  WebViewController _buildWebController(String url) {
    // The secure page renders the value tiny and its cross-origin content can't
    // be restyled from the app (CSS/zoom injected into it is ignored). So embed
    // it in an iframe on a page WE control — with baseUrl set to strowallet so
    // the iframe is same-origin and loads — then scale that iframe up with a
    // plain CSS transform, which reliably enlarges it whatever the inner markup.
    final html = '''
<!DOCTYPE html><html><head>
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<style>
  html,body{margin:0;padding:0;background:#fff;}
  .row{height:32px;display:flex;align-items:center;overflow:hidden;}
  .scale{transform:scale(1.15);transform-origin:left center;}
  iframe{border:0;display:block;width:200px;height:24px;background:#fff;}
</style></head>
<body><div class="row"><div class="scale"><iframe src="$url"></iframe></div></div></body>
</html>
''';
    return WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..loadHtmlString(html, baseUrl: 'https://strowallet.com');
  }

  @override
  Widget build(BuildContext context) {
    final card = widget.card;
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius:
            BorderRadius.vertical(top: Radius.circular(20)),
      ),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
            margin: EdgeInsets.only(top: 12.h),
            width: 40.w,
            height: 4.h,
            decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2.r))),
        Padding(
          padding: EdgeInsets.all(24.w),
          child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
            Text('Card Details',
                style: TextStyle(
                    fontSize: 24.sp, fontWeight: FontWeight.bold)),
            IconButton(
                onPressed: () => Navigator.of(context).pop(),
                icon: Icon(Icons.close,
                    color: Colors.grey[600], size: 24.sp)),
          ]),
        ),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 24.w),
          child: Column(children: [
            widget.card.currency == 'USD'
                ? _plainRow('Card Name', card.cardName ?? '—')
                : _Row('Card Name', card.cardName ?? '—',
                    () => _copyWithBanner(
                        context, card.cardName ?? '', 'Card name')),
            SizedBox(height: 20.h),
            // Card Number + CVV. USD renders them in a secure WebView (the raw
            // values never reach the app); NGN carries them inline as text.
            widget.card.currency == 'USD'
                ? _secureRow('Card Number', _numberCtrl, fullWidth: true)
                : _Row('Card Number', card.cardNumber ?? '—',
                    () => _copyWithBanner(
                        context, card.cardNumber ?? '', 'Card number')),
            SizedBox(height: 20.h),
            widget.card.currency == 'USD'
                ? _secureRow('CVV', _cvvCtrl, fullWidth: false)
                : _Row('CVV', card.cvv ?? '—',
                    () => _copyWithBanner(context, card.cvv ?? '', 'CVV')),
            SizedBox(height: 20.h),
            widget.card.currency == 'USD'
                ? _plainRow('Expiry Date', card.expiryDate ?? '—')
                : _Row('Expiry Date', card.expiryDate ?? '—',
                    () => _copyWithBanner(
                        context, card.expiryDate ?? '', 'Expiry date')),
            SizedBox(height: 20.h),
            widget.card.currency == 'USD'
                ? _plainRow('Currency', card.currency)
                : _Row('Currency', card.currency,
                    () => _copyWithBanner(context, card.currency, 'Currency')),
            SizedBox(height: 24.h),
            GestureDetector(
              onTap: () {
                Navigator.of(context).pop();
                showModalBottomSheet(
                    context: context,
                    isScrollControlled: true,
                    backgroundColor: Colors.transparent,
                    builder: (_) => BillingAddressModal(card: card));
              },
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(vertical: 16.h),
                decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey[300]!),
                    borderRadius: BorderRadius.circular(12.r)),
                child: Row(
                    mainAxisAlignment:
                        MainAxisAlignment.spaceBetween,
                    children: [
                  Padding(
                    padding: const EdgeInsets.only(left: 12),
                    child: Text('View billing address',
                        style: TextStyle(
                            fontSize: 16.sp,
                            fontWeight: FontWeight.w500)),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(right: 12),
                    child: Icon(Icons.arrow_forward_ios,
                        size: 16.sp, color: Colors.grey[600]),
                  ),
                ]),
              ),
            ),
            SizedBox(height: 24.h),
          ]),
        ),
      ]),
    );
  }

  // Plain value row with no copy button (USD details keep it clean).
  Widget _plainRow(String label, String value) {
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Expanded(
          child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
        Text(label, style: TextStyle(fontSize: 14.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        Text(value,
            style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600)),
      ])),
    ]);
  }

  // Card Number / CVV value: a small WebView that renders the secure number or
  // CVV, a spinner while the fresh URL loads, or a "tap to reveal" retry.
  Widget _secureRow(String label, WebViewController? controller,
      {required bool fullWidth}) {
    Widget value;
    if (controller != null) {
      value = Container(
        width: fullWidth ? double.infinity : 150.w,
        height: 34.h,
        alignment: Alignment.centerLeft,
        child: WebViewWidget(controller: controller),
      );
    } else if (_loading) {
      value = SizedBox(
        width: 20.w,
        height: 20.w,
        child: const CircularProgressIndicator(
            strokeWidth: 2, color: Color(0xFF34544A)),
      );
    } else if (_failed) {
      value = GestureDetector(
        onTap: _reveal,
        child: Row(mainAxisSize: MainAxisSize.min, children: [
          Icon(Icons.refresh, size: 16.sp, color: const Color(0xFF34544A)),
          SizedBox(width: 4.w),
          Text('Tap to reveal',
              style: TextStyle(
                  fontSize: 14.sp,
                  color: const Color(0xFF34544A),
                  fontWeight: FontWeight.w600)),
        ]),
      );
    } else {
      value = Text('—',
          style: TextStyle(fontSize: 16.sp, fontWeight: FontWeight.w600));
    }
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Expanded(
          child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
        Text(label, style: TextStyle(fontSize: 14.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        value,
      ])),
    ]);
  }
}

class _Row extends StatelessWidget {
  final String label;
  final String value;
  final VoidCallback onCopy;
  const _Row(this.label, this.value, this.onCopy);

  @override
  Widget build(BuildContext context) {
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Expanded(
          child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
        Text(label,
            style: TextStyle(
                fontSize: 14.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        Text(value,
            style: TextStyle(
                fontSize: 16.sp, fontWeight: FontWeight.w600)),
      ])),
      GestureDetector(
        onTap: onCopy,
        child: Container(
          padding: EdgeInsets.symmetric(horizontal: 12.w, vertical: 6.h),
          decoration: BoxDecoration(
              color: const Color(0xFF34544A).withOpacity(0.1),
              borderRadius: BorderRadius.circular(8.r),
              border: Border.all(
                  color: const Color(0xFF34544A).withOpacity(0.3))),
          child: Row(mainAxisSize: MainAxisSize.min, children: [
            Icon(Icons.copy, size: 16.sp, color: const Color(0xFF34544A)),
            SizedBox(width: 4.w),
            Text('Copy',
                style: TextStyle(
                    fontSize: 12.sp,
                    color: const Color(0xFF34544A),
                    fontWeight: FontWeight.w600)),
          ]),
        ),
      ),
    ]);
  }
}

class BillingAddressModal extends StatelessWidget {
  final VirtualCardModel card;
  const BillingAddressModal({super.key, required this.card});

  @override
  Widget build(BuildContext context) {
    final street  = card.billingStreet  ?? '—';
    final city    = card.billingCity    ?? '—';
    final state   = card.billingState   ?? '—';
    final country = card.billingCountry ?? '—';
    final zip     = card.billingZip     ?? '—';

    return Container(
      decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Container(
            margin: EdgeInsets.only(top: 12.h),
            width: 40.w,
            height: 4.h,
            decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2.r))),
        Padding(
          padding: EdgeInsets.all(24.w),
          child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
            Text('Billing address',
                style: TextStyle(
                    fontSize: 24.sp, fontWeight: FontWeight.bold)),
            IconButton(
                onPressed: () => Navigator.of(context).pop(),
                icon: Icon(Icons.close,
                    color: Colors.grey[600], size: 24.sp)),
          ]),
        ),
        Padding(
          padding: EdgeInsets.symmetric(horizontal: 24.w),
          child: Column(children: [
            _Row2('Street Address', street,
                () => _copyWithBanner(context, street, 'Street address')),
            SizedBox(height: 16.h),
            _Row2('City', city,
                () => _copyWithBanner(context, city, 'City')),
            SizedBox(height: 16.h),
            _Row2('State', state,
                () => _copyWithBanner(context, state, 'State')),
            SizedBox(height: 16.h),
            _Row2('Country', country,
                () => _copyWithBanner(context, country, 'Country')),
            SizedBox(height: 16.h),
            _Row2('Postal Code', zip,
                () => _copyWithBanner(context, zip, 'Postal code')),
            SizedBox(height: 24.h),
          ]),
        ),
      ]),
    );
  }
}

class _Row2 extends StatelessWidget {
  final String label;
  final String value;
  final VoidCallback onCopy;
  const _Row2(this.label, this.value, this.onCopy);

  @override
  Widget build(BuildContext context) {
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Expanded(
          child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
        Text(label,
            style: TextStyle(
                fontSize: 14.sp, color: Colors.grey[600])),
        SizedBox(height: 4.h),
        Text(value,
            style: TextStyle(
                fontSize: 16.sp, fontWeight: FontWeight.w600)),
      ])),
      GestureDetector(
          onTap: onCopy,
          child: Padding(
              padding: const EdgeInsets.all(8),
              child: Icon(Icons.copy,
                  size: 16.sp, color: Colors.grey[600]))),
    ]);
  }
}