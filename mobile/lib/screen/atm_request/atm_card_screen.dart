// lib/screen/atm_request/atm_card_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/physical_card_provider.dart';
import '../../widgets/pin_entry_screen.dart';
import 'atm_request_screen.dart';
import 'widgets/card_widget.dart';
import 'widgets/channel_services_widget.dart';
import 'widgets/enable_2fa_widget.dart';
import 'widgets/enable_card_widget.dart';
import 'widgets/manage_card_widget.dart';

class AtmCardScreen extends ConsumerStatefulWidget {
  const AtmCardScreen({super.key});

  @override
  ConsumerState<AtmCardScreen> createState() => _AtmCardScreenState();
}

class _AtmCardScreenState extends ConsumerState<AtmCardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(physicalCardNotifierProvider.notifier).fetchCard();
    });
  }

  // ── Block / Unblock — go straight to PIN entry ─────────────────────────────
  Future<void> _showBlockDialog(PhysicalCardModel card) async {
    final isFreezing = card.isActive; // freeze if currently active
    final pin = await showPinEntry(
      context,
      title: isFreezing ? 'Freeze Card' : 'Unfreeze Card',
      subtitle: isFreezing
          ? 'Freezing your card will prevent all transactions. '
              'Enter your transaction PIN to continue.'
          : 'Enter your transaction PIN to reactivate the card.',
    );
    if (pin == null || pin.length < 4) return;
    _processBlockToggle(pin);
  }

  Future<void> _processBlockToggle(String pin) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) =>
          const Center(child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );
    final response = await ref
        .read(physicalCardNotifierProvider.notifier)
        .toggleFreeze(transactionPin: pin);
    if (!mounted) return;
    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(response.success
          ? 'Card updated successfully'
          : (response.message.isNotEmpty ? response.message : 'Failed')),
      backgroundColor: response.success ? Colors.green : Colors.red,
    ));
  }

  @override
  Widget build(BuildContext context) {
    final cardState = ref.watch(physicalCardNotifierProvider);

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Row(children: [
            IconButton(
              icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
              onPressed: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: Text('Physical Card',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 25.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.white)),
            ),
            const SizedBox(width: 48),
          ]),
          const SizedBox(height: 30),
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
                  width: double.infinity,
                  decoration: const BoxDecoration(
                      color: Color(0xFFF4F7FA),
                      borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20))),
                  child: cardState.isLoading && !cardState.hasChecked
                      ? const Center(
                          child: CircularProgressIndicator(
                              color: Color(0xFF34544A)))

                      // No card record at all — offer to request one.
                      : !cardState.hasCard
                          ? _NoCardView(
                              onRequest: () => Navigator.push(
                                context,
                                MaterialPageRoute(
                                    builder: (_) => AtmRequestScreen()),
                              ),
                            )

                          // A card was ordered but the issuer hasn't assigned a
                          // number yet. Rendering CardWidget here drew an empty
                          // card — masked dots, no expiry, no CVV — which reads
                          // as a broken card rather than one that doesn't exist
                          // yet. Show the awaiting-card screen instead.
                          : !cardState.card!.isAssigned
                              ? _AwaitingCardView(
                                  onEnable: () => Navigator.push(
                                    context,
                                    MaterialPageRoute(
                                        builder: (_) =>
                                            const EnableCardWidget()),
                                  ),
                                )

                          // Card assigned — show it.
                          : SingleChildScrollView(
                              child: Column(children: [
                                const SizedBox(height: 32),
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16.0),
                                  child: CardWidget(card: cardState.card!),
                                ),
                                const SizedBox(height: 20),

                                // Action buttons (first row)
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 50.0),
                                  child: Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      _CardAction(
                                        icon: Icons.credit_card,
                                        label: 'Change PIN',
                                        onTap: () => Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                              builder: (_) =>
                                                  const ManageCardWidget()),
                                        ),
                                      ),
                                      _CardAction(
                                        icon: Icons.settings_input_component,
                                        label: 'Channel',
                                        onTap: () => Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                              builder: (_) =>
                                                  ChannelServicesWidget(
                                                      card: cardState.card!)),
                                        ),
                                      ),
                                      _CardAction(
                                        icon: cardState.card!.isActive
                                            ? Icons.pause_circle_outline
                                            : Icons.play_circle_outline,
                                        label: cardState.card!.isActive
                                            ? 'Freeze'
                                            : 'Unfreeze',
                                        iconColor: cardState.card!.isActive
                                            ? Colors.orange
                                            : const Color(0xFF51B595),
                                        onTap: () =>
                                            _showBlockDialog(cardState.card!),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 16),

                                // Action buttons (second row): 2FA or Enable.
                                // "Enable Card" is the one-time activation, so
                                // it only makes sense while no card number has
                                // been assigned; every other action needs a
                                // real card. Keying off the card number rather
                                // than `status` means Enable stays hidden even
                                // while the card is frozen (freeze flips status
                                // to inactive but keeps the number).
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 50.0),
                                  child: Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceEvenly,
                                    children: [
                                      _CardAction(
                                        icon: Icons.shield_outlined,
                                        label: 'Enable 2FA',
                                        onTap: () => Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                              builder: (_) =>
                                                  const Enable2FAWidget()),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 24),

                                // Card info tiles
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                      horizontal: 16),
                                  child: _InfoCard(card: cardState.card!),
                                ),
                                const SizedBox(height: 80),
                              ]),
                            ),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }
}

// ─── No card view ─────────────────────────────────────────────────────────────

class _NoCardView extends StatelessWidget {
  final VoidCallback onRequest;
  const _NoCardView({required this.onRequest});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(Icons.credit_card_off, size: 80, color: Colors.grey[400]),
          const SizedBox(height: 20),
          Text("You don't have a physical card yet",
              style: TextStyle(
                  fontSize: 18.sp, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center),
          const SizedBox(height: 8),
          Text('Request one to use at ATMs and POS terminals.',
              style:
                  TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
              textAlign: TextAlign.center),
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              onPressed: onRequest,
              child: const Text('Request Physical Card',
                  style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 16)),
            ),
          ),
        ]),
      ),
    );
  }
}

// ─── Awaiting card view ───────────────────────────────────────────────────────

/// Shown when a physical card has been ordered but the issuer hasn't assigned a
/// card number yet.
///
/// Previously this state rendered the normal card UI, which meant an empty card
/// — masked dots for the number, `**/**` expiry, no CVV — plus a default daily
/// limit that wasn't real. That looks like a broken card rather than one that
/// hasn't arrived, so nothing card-shaped is drawn until a card actually exists.
class _AwaitingCardView extends StatelessWidget {
  final VoidCallback onEnable;
  const _AwaitingCardView({required this.onEnable});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(Icons.credit_card_off, size: 80, color: Colors.grey[400]),
          const SizedBox(height: 20),
          Text('No card assigned yet',
              style: TextStyle(fontSize: 18.sp, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center),
          const SizedBox(height: 8),
          Text(
            'Your physical card request has been received. Once your card '
            'arrives, tap below to activate it with the details printed on it.',
            style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                padding: const EdgeInsets.symmetric(vertical: 14),
              ),
              onPressed: onEnable,
              child: const Text('Enable Card',
                  style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 16)),
            ),
          ),
        ]),
      ),
    );
  }
}

// ─── Info card ────────────────────────────────────────────────────────────────

class _InfoCard extends StatelessWidget {
  final PhysicalCardModel card;
  const _InfoCard({required this.card});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: const [
          BoxShadow(color: Colors.black12, blurRadius: 4, offset: Offset(0, 2))
        ],
      ),
      child: Column(children: [
        // _Row('Brand',    card.brand   ?? '—'),
        // _Row('Currency', card.currency),
        // _Row('Expiry',   card.displayExpiry),
        // _Row('Spending limit',
        //     '₦${card.spendingLimit.toStringAsFixed(0)} / ${card.spendingLimitInterval}'),
        // _Row('ATM',    card.atm    ? 'Enabled' : 'Disabled'),
        // _Row('POS',    card.pos    ? 'Enabled' : 'Disabled'),
        // _Row('Web',    card.web    ? 'Enabled' : 'Disabled'),
        // _Row('Mobile', card.mobile ? 'Enabled' : 'Disabled'),
      ]),
    );
  }
}

class _Row extends StatelessWidget {
  final String label;
  final String value;
  const _Row(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style:
                  TextStyle(color: Colors.grey[600], fontSize: 14.sp)),
          Text(value,
              style: const TextStyle(
                  fontWeight: FontWeight.w600, fontSize: 14)),
        ],
      ),
    );
  }
}

// ─── Action button ────────────────────────────────────────────────────────────

class _CardAction extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final Color? iconColor;
  const _CardAction(
      {required this.icon, required this.label, this.onTap, this.iconColor});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Column(children: [
        Icon(icon,
            color: iconColor ?? const Color(0xFF51B595)),
        const SizedBox(height: 8),
        Text(label,
            style: TextStyle(
                color: Colors.black,
                fontWeight: FontWeight.w500,
                fontSize: 14.sp)),
      ]),
    );
  }
}