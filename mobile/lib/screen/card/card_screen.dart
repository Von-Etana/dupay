// lib/screen/card/card_screen.dart

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/card_provider.dart';
import 'card_management_screen.dart';
import 'claim_cards_screen.dart';

class CardScreen extends ConsumerStatefulWidget {
  const CardScreen({super.key});

  @override
  ConsumerState<CardScreen> createState() => _CardScreenState();
}

class _CardScreenState extends ConsumerState<CardScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabCtrl;

  // A card created via the async flow lands a little after the request returns.
  // Poll while the Card tab is open so it appears WITHOUT the user having to
  // leave and come back. Only runs while this screen is mounted (i.e. the Card
  // tab is active) and stops fetching once both cards exist.
  Timer? _pollTimer;
  bool _isFetching = false;
  int _pollCount = 0;
  // ~8 min at an 8s cadence — stop chasing a card that never provisions.
  static const int _maxPolls = 60;

  @override
  void initState() {
    super.initState();
    _tabCtrl = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _refresh());
    _pollTimer =
        Timer.periodic(const Duration(seconds: 8), (_) => _refresh());
  }

  Future<void> _refresh() async {
    if (_isFetching || !mounted) return;

    // Keep polling until every card that exists has its number, not merely
    // until the row appears. A freshly-created card shows up BEFORE the issuer
    // releases the PAN, so stopping at "row exists" would strand it masked.
    final cards = ref.read(cardNotifierProvider).cards;
    final allProvisioned =
        cards.isNotEmpty && cards.every((c) => !c.isProvisioning);
    // Safety cap so a card stuck provisioning doesn't poll forever.
    if (allProvisioned || _pollCount >= _maxPolls) return;
    _pollCount++;

    _isFetching = true;
    try {
      await ref.read(cardNotifierProvider.notifier).fetchCards();
    } catch (_) {
      // Ignore — a background refresh failure shouldn't surface to the user.
    } finally {
      _isFetching = false;
    }
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _tabCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cardState = ref.watch(cardNotifierProvider);

    // Show spinner on first load only
    if (cardState.isLoading && !cardState.hasChecked) {
      return const Scaffold(
        backgroundColor: Color(0xFF34544A),
        body: Center(
            child: CircularProgressIndicator(color: Colors.white)),
      );
    }

    final ngnCard = cardState.cards
        .where((c) => c.currency == 'NGN')
        .firstOrNull;
    final usdCard = cardState.cards
        .where((c) => c.currency == 'USD')
        .firstOrNull;

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          // ── Header ──────────────────────────────────────────────────────
          Padding(
            padding: const EdgeInsets.symmetric(
                horizontal: 16, vertical: 16),
            child: Text('My Cards',
                textAlign: TextAlign.center,
                style: TextStyle(
                    fontSize: 22.sp,
                    fontWeight: FontWeight.bold,
                    color: Colors.white)),
          ),

          // ── NGN / USD tab bar ────────────────────────────────────────────
          Container(
            margin: const EdgeInsets.symmetric(
                horizontal: 20, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: TabBar(
              controller: _tabCtrl,
              indicator: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
              ),
              indicatorSize: TabBarIndicatorSize.tab,
              dividerColor: Colors.transparent,
              labelColor: const Color(0xFF34544A),
              unselectedLabelColor: Colors.white,
              labelStyle: const TextStyle(
                  fontWeight: FontWeight.bold, fontSize: 14),
              tabs: const [
                Tab(text: 'NGN Card'),
                Tab(text: 'USD Card'),
              ],
            ),
          ),

          // ── Tab views ────────────────────────────────────────────────────
          Expanded(
            child: TabBarView(
              controller: _tabCtrl,
              children: [
                // NGN tab — pass currency only, screen reads card from provider
                ngnCard != null
                    ? CardManagementScreen(currency: 'NGN')
                    : ClaimCardsScreen(currency: 'NGN'),

                // USD tab — pass currency only, screen reads card from provider
                usdCard != null
                    ? CardManagementScreen(currency: 'USD')
                    : ClaimCardsScreen(currency: 'USD'),
              ],
            ),
          ),
        ]),
      ),
    );
  }
}