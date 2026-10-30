// lib/widgets/pin_entry_screen.dart
//
// Shared full-screen PIN entry. Replaces the per-flow showDialog PIN prompts.
//
// Usage (simple — existing callers unchanged):
//   final pin = await showPinEntry(context, title: 'Freeze Card');
//   if (pin == null) return; // user backed out
//
// Usage (with transaction preview — used by buy_data and similar flows):
//   final pin = await showPinEntry(
//     context,
//     title: 'Confirm Purchase',
//     amount: '₦200',
//     transactionDetails: [
//       PinTransactionDetail('Network',  'MTN'),
//       PinTransactionDetail('Plan',     '1GB - 30 Days'),
//       PinTransactionDetail('Phone',    '08031234567'),
//     ],
//   );
//
// Visual: white screen with optional preview card showing the transaction
// being confirmed, then 4 PIN boxes with dots that fill as the user types.
// A hidden TextField pulls up the native numeric keyboard.

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// A single (label, value) row displayed in the transaction preview card.
class PinTransactionDetail {
  final String label;
  final String value;
  /// Optional icon shown beside the row.
  final IconData? icon;
  /// If true, the value is rendered in the brand color / bold (used for
  /// emphasis on things like the total amount).
  final bool emphasized;

  const PinTransactionDetail(
    this.label,
    this.value, {
    this.icon,
    this.emphasized = false,
  });
}

class PinEntryScreen extends StatefulWidget {
  final String title;
  final String? subtitle;
  final int length;

  /// Big amount shown above the details (e.g. "₦200.00"). When null, the
  /// amount row is hidden.
  final String? amount;

  /// Optional list of (label, value) rows shown in the preview card. When
  /// null or empty, the preview card is hidden and the screen reverts to
  /// the minimal "enter PIN" layout used everywhere else.
  final List<PinTransactionDetail>? transactionDetails;

  const PinEntryScreen({
    super.key,
    this.title = 'Enter your 4-digit transaction PIN',
    this.subtitle,
    this.length = 4,
    this.amount,
    this.transactionDetails,
  });

  @override
  State<PinEntryScreen> createState() => _PinEntryScreenState();
}

class _PinEntryScreenState extends State<PinEntryScreen> {
  // Brand palette (matches the rest of the app).
  static const Color _brand     = Color(0xFF34544A);
  static const Color _surface   = Color(0xFFF6F8F7);

  final _controller = TextEditingController();
  final _focusNode  = FocusNode();
  bool _submitted = false;

  @override
  void initState() {
    super.initState();
    // Ensure the native keyboard opens as soon as the screen appears.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) _focusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  /// Re-open the soft keyboard. On Android the keyboard can be dismissed
  /// (back button / swipe-down) WITHOUT the TextField losing focus, so a
  /// plain requestFocus() does nothing and the keyboard stays hidden. When
  /// the node already has focus we ask the platform to show it directly;
  /// otherwise we request focus normally.
  void _openKeyboard() {
    if (!mounted) return;
    if (_focusNode.hasFocus) {
      SystemChannels.textInput.invokeMethod('TextInput.show');
    } else {
      FocusScope.of(context).requestFocus(_focusNode);
    }
  }

  void _onChanged(String value) {
    if (_submitted) return;
    if (value.length >= widget.length) {
      _submitted = true;
      final pin = value.substring(0, widget.length);
      // Slight delay so the final dot animates in before the screen pops.
      Future.delayed(const Duration(milliseconds: 120), () {
        if (mounted) Navigator.of(context).pop(pin);
      });
    }
    setState(() {});
  }

  bool get _hasPreview =>
      widget.amount != null ||
      (widget.transactionDetails != null &&
          widget.transactionDetails!.isNotEmpty);

  @override
  Widget build(BuildContext context) {
    final filledCount = _controller.text.length;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
        title: _hasPreview
            ? const Text(
                'Confirm Transaction',
                style: TextStyle(
                  fontSize: 17,
                  fontWeight: FontWeight.w600,
                  color: Colors.black87,
                ),
              )
            : null,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.close),
          tooltip: 'Cancel',
          onPressed: () => Navigator.of(context).pop(null),
        ),
      ),
      // Tap anywhere to re-open the keyboard if it was dismissed.
      body: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: _openKeyboard,
        child: SafeArea(
          child: SingleChildScrollView(
            physics: const ClampingScrollPhysics(),
            child: ConstrainedBox(
              constraints: BoxConstraints(
                minHeight: MediaQuery.of(context).size.height -
                    kToolbarHeight -
                    MediaQuery.of(context).padding.top,
              ),
              child: IntrinsicHeight(
                child: Column(
                  children: [
                    if (_hasPreview) ...[
                      const SizedBox(height: 8),
                      _buildTransactionPreview(),
                      const SizedBox(height: 24),
                    ] else
                      const SizedBox(height: 28),

                    // ── Title / subtitle ──────────────────────────────
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 24),
                      child: Text(
                        _hasPreview
                            ? 'Enter your transaction PIN'
                            : widget.title,
                        style: const TextStyle(
                          fontSize: 16,
                          color: Colors.black87,
                          fontWeight: FontWeight.w600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                    if (widget.subtitle != null) ...[
                      const SizedBox(height: 8),
                      Padding(
                        padding:
                            const EdgeInsets.symmetric(horizontal: 32),
                        child: Text(
                          widget.subtitle!,
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.grey[600],
                            height: 1.4,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ],
                    const SizedBox(height: 28),

                    // ── Dot boxes ─────────────────────────────────────
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: List.generate(widget.length, (i) {
                        final filled = i < filledCount;
                        return Container(
                          width: 56,
                          height: 56,
                          margin:
                              const EdgeInsets.symmetric(horizontal: 8),
                          decoration: BoxDecoration(
                            color: _surface,
                            border: Border.all(
                              color: filled
                                  ? _brand
                                  : Colors.grey.shade300,
                              width: filled ? 1.5 : 1,
                            ),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Center(
                            child: AnimatedContainer(
                              duration: const Duration(milliseconds: 150),
                              width: filled ? 16 : 14,
                              height: filled ? 16 : 14,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: filled
                                    ? _brand
                                    : Colors.transparent,
                                border: filled
                                    ? null
                                    : Border.all(
                                        color: Colors.grey.shade400,
                                        width: 1.5,
                                      ),
                              ),
                            ),
                          ),
                        );
                      }),
                    ),

                    if (_hasPreview) ...[
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.lock_outline,
                              size: 14, color: Colors.grey[600]),
                          const SizedBox(width: 6),
                          Text(
                            'Secured by end-to-end encryption',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    ],

                    // ── Hidden input that drives the dots ─────────────
                    SizedBox(
                      width: 1,
                      height: 1,
                      child: Opacity(
                        opacity: 0,
                        child: TextField(
                          controller: _controller,
                          focusNode: _focusNode,
                          keyboardType: TextInputType.number,
                          autofocus: true,
                          obscureText: true,
                          enableInteractiveSelection: false,
                          showCursor: false,
                          inputFormatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            LengthLimitingTextInputFormatter(
                                widget.length),
                          ],
                          onChanged: _onChanged,
                        ),
                      ),
                    ),

                    const Spacer(),
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  // ─── Transaction preview card ─────────────────────────────────────────────
  Widget _buildTransactionPreview() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Container(
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.black, width: 1.5),
        ),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 18, 20, 18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (widget.amount != null) ...[
               
                const SizedBox(height: 14),
                Container(
                  height: 1,
                  color: Colors.black.withOpacity(0.12),
                ),
                const SizedBox(height: 14),
              ],
              if (widget.transactionDetails != null) ...[
                for (int i = 0;
                    i < widget.transactionDetails!.length;
                    i++) ...[
                  _previewRow(widget.transactionDetails![i]),
                  if (i < widget.transactionDetails!.length - 1)
                    const SizedBox(height: 10),
                ],
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _previewRow(PinTransactionDetail d) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (d.icon != null) ...[
          Icon(d.icon,
              size: 14, color: Colors.black.withOpacity(0.65)),
          const SizedBox(width: 8),
        ],
        Expanded(
          flex: 4,
          child: Text(
            d.label,
            style: TextStyle(
              fontSize: 12.5,
              color: Colors.black.withOpacity(0.6),
              fontWeight: FontWeight.w500,
            ),
          ),
        ),
        Expanded(
          flex: 6,
          child: Text(
            d.value,
            textAlign: TextAlign.right,
            style: TextStyle(
              fontSize: 13.5,
              color: Colors.black,
              fontWeight:
                  d.emphasized ? FontWeight.w700 : FontWeight.w600,
            ),
          ),
        ),
      ],
    );
  }
}

/// Open the shared PIN entry screen and return the entered PIN, or `null` if
/// the user backed out without entering one.
///
/// Pass [amount] and/or [transactionDetails] to render a transaction preview
/// card above the PIN dots. When both are omitted the screen falls back to
/// the original minimal layout used by existing callers (card freeze, ATM
/// claim, channel toggle, etc.).
Future<String?> showPinEntry(
  BuildContext context, {
  String title = 'Enter your 4-digit transaction PIN',
  String? subtitle,
  int length = 4,
  String? amount,
  List<PinTransactionDetail>? transactionDetails,
}) {
  return Navigator.of(context).push<String>(
    MaterialPageRoute(
      builder: (_) => PinEntryScreen(
        title: title,
        subtitle: subtitle,
        length: length,
        amount: amount,
        transactionDetails: transactionDetails,
      ),
      fullscreenDialog: true,
    ),
  );
}