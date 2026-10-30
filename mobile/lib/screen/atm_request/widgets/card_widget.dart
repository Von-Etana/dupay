// lib/screen/atm_request/widgets/card_widget.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../../providers/physical_card_provider.dart';

class CardWidget extends StatefulWidget {
  final PhysicalCardModel card;
  const CardWidget({super.key, required this.card});

  @override
  State<CardWidget> createState() => _CardWidgetState();
}

class _CardWidgetState extends State<CardWidget> {
  bool _showDetails = false;

  void _copyToClipboard(String text, String label) {
    Clipboard.setData(ClipboardData(text: text));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('$label copied'),
        duration: const Duration(seconds: 2),
        backgroundColor: const Color(0xFF34544A),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final card     = widget.card;
    final isActive = card.isActive;

    // Three states, in priority order: blocked → not yet assigned (ordered but
    // the issuer hasn't released a card number) → active/frozen. A card that
    // hasn't been assigned must never read as "Active".
    final String  statusLabel;
    final Color   statusColor;
    if (card.blocked) {
      statusLabel = 'Blocked';
      statusColor = Colors.red;
    } else if (!card.isAssigned) {
      statusLabel = 'Not Assigned';
      statusColor = Colors.amber;
    } else if (isActive) {
      statusLabel = 'Active';
      statusColor = Colors.green;
    } else {
      statusLabel = 'Inactive';
      statusColor = Colors.red;
    }

    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        // Always green — active/inactive state is shown by the status badge,
        // not by greying out the whole card.
        gradient: const LinearGradient(
          colors: [Color(0xFF34544A), Color(0xFF1B2B23)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: const [
          BoxShadow(color: Colors.black26, blurRadius: 8, offset: Offset(0, 4))
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top row: brand + status
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                // The spending limit belongs to a real card — while nothing is
                // assigned it's just a default the backend hands back, so hide
                // it rather than quote a limit the user doesn't have.
                if (card.isAssigned)
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Card Daily Limit',
                        style: TextStyle(
                            color: Colors.white54,
                            fontSize: 11.sp,
                            fontWeight: FontWeight.w500),
                      ),
                      Text(
                        '₦${_formatAmount(card.spendingLimit)}',
                        style: TextStyle(
                            color: Colors.white70,
                            fontSize: 13.sp,
                            fontWeight: FontWeight.w600),
                      ),
                    ],
                  )
                else
                  // Keeps the status badge hard right once the limit is gone.
                  const Spacer(),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: statusColor, width: 1),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(
                      color: statusColor,
                      fontWeight: FontWeight.bold,
                      fontSize: 12.sp,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Card number row
            Row(children: [
              Text('Card Number',
                  style: TextStyle(color: Colors.white70, fontSize: 13.sp)),
              const SizedBox(width: 8),
              GestureDetector(
                onTap: card.isAssigned
                    ? () => _copyToClipboard(card.displayPan, 'Card number')
                    : null,
                child: const Icon(Icons.copy, color: Colors.white70, size: 16),
              ),
              const Spacer(),
              GestureDetector(
                onTap: () => setState(() => _showDetails = !_showDetails),
                child: Icon(
                  _showDetails ? Icons.visibility_off : Icons.visibility,
                  color: Colors.orange[300],
                  size: 20,
                ),
              ),
            ]),
            const SizedBox(height: 4),
            Text(
              card.displayPan,
              style: TextStyle(
                color: Colors.amber[300],
                fontWeight: FontWeight.bold,
                fontSize: 17.sp,
                letterSpacing: 2,
              ),
            ),
            const SizedBox(height: 18),

            // Card holder / currency
            Text(
              card.currency == 'NGN' ? 'NGN Card' : '${card.currency} Card',
              style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                  fontSize: 18.sp),
            ),
            const SizedBox(height: 10),

            // Expiry + CVV
            Row(children: [
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('exp',
                    style: TextStyle(color: Colors.white70, fontSize: 13.sp)),
                Text(
                  card.displayExpiry,
                  style: TextStyle(
                      color: Colors.amber[300],
                      fontWeight: FontWeight.bold,
                      fontSize: 15.sp),
                ),
              ]),
              const SizedBox(width: 32),
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('cvv',
                    style: TextStyle(color: Colors.white70, fontSize: 13.sp)),
                GestureDetector(
                  onTap: () {
                    if (_showDetails && card.cvv != null) {
                      _copyToClipboard(card.cvv!, 'CVV');
                    }
                  },
                  child: Text(
                    _showDetails ? (card.cvv ?? '●●●') : '●●●',
                    style: TextStyle(
                        color: Colors.amber[300],
                        fontWeight: FontWeight.bold,
                        fontSize: 15.sp),
                  ),
                ),
              ]),
              const Spacer(),
              // Brand chip
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  card.brand ?? 'Physical Card',
                  style: TextStyle(color: Colors.white70, fontSize: 11.sp),
                ),
              ),
            ]),
          ],
        ),
      ),
    );
  }

  String _formatAmount(double amount) {
    final isNegative = amount < 0;
    final fixed = amount.abs().toStringAsFixed(2);
    final parts = fixed.split('.');
    final whole = parts[0];
    final decimals = parts[1];

    final buffer = StringBuffer();
    final reversedDigits = whole.split('').reversed.toList();
    for (int i = 0; i < reversedDigits.length; i++) {
      if (i != 0 && i % 3 == 0) buffer.write(',');
      buffer.write(reversedDigits[i]);
    }
    final formattedWhole = buffer.toString().split('').reversed.join();

    return '${isNegative ? '-' : ''}$formattedWhole.$decimals';
  }

  

  
}