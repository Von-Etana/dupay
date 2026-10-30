// lib/screen/card/add_funds_screen.dart
//
// NGN card  → goes to FundWalletScreen (fund main NGN wallet via bank transfer)
// USD card  → goes to FundUsdCardScreen (convert NGN/USD/EUR → USD virtual card)

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/card_provider.dart';
import '../fund_wallet/fund_wallet.dart';
import 'ss.dart';

class AddFundsScreen extends ConsumerWidget {
  final VirtualCardModel card;
  const AddFundsScreen({super.key, required this.card});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Immediately replace this route with the correct screen.
    // Using addPostFrameCallback so we're not navigating inside build().
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!context.mounted) return;
      if (card.currency == 'NGN') {
        // NGN card → fund the main NGN wallet (bank transfer / virtual account)
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const FundWalletScreen()),
        );
      } else {
        // USD card → convert from any currency and top up the USD virtual card
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => FundUsdCardScreen(card: card)),
        );
      }
    });

    // Thin splash while redirect happens
    return const Scaffold(
      backgroundColor: Color(0xFF34544A),
      body: Center(child: CircularProgressIndicator(color: Colors.white)),
    );
  }
}