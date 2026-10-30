// lib/services/data_preloader.dart
//
// Warms the caches for the data the user is most likely to need right after
// landing on the home screen, so that the relevant pages (Recharge Card,
// Fund Wallet, Foreign Account, Bank List, Airtime and Data) already have
// their data cached BEFORE the user navigates to them — no spinner, no
// "first tap shows nothing" race.
//
// Design notes:
//  * Fire-and-forget: callers use DataPreloader.run(ref) and never await it,
//    so the UI is never blocked.
//  * Each task is isolated in its own try/catch, so one failing endpoint can
//    never abort the rest.
//  * Every underlying fetch is already cache-aware (TTL handled in the
//    providers / CacheService), so calling this repeatedly is cheap — a warm
//    cache short-circuits the network call.

import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'package:dupay/core/network/api_service.dart';
import 'package:dupay/services/cache/cache_service.dart';
import 'package:dupay/providers/recharge_card_provider.dart';
import 'package:dupay/providers/fund_wallet_provider.dart';
import 'package:dupay/providers/foreign_currency_provider.dart';
import 'package:dupay/providers/airtime_provider.dart';
import 'package:dupay/providers/data_provider.dart';

class DataPreloader {
  DataPreloader._();

  static bool _inFlight = false;

  /// Fire-and-forget entry point. Safe to call multiple times; overlapping
  /// runs are ignored and warm caches make repeat runs essentially free.
  static void run(WidgetRef ref) {
    if (_inFlight) return;
    _inFlight = true;
    _run(ref).whenComplete(() => _inFlight = false);
  }

  /// Call on logout so the next login triggers a fresh warm-up.
  static void reset() {
    _inFlight = false;
  }

  static Future<void> _run(WidgetRef ref) async {
    print('⚡ DataPreloader: warming caches after login');

    await Future.wait([
      _guard('recharge-card', () =>
          ref.read(rechargeCardNotifierProvider.notifier).fetchEpins()),

      _guard('fund-wallet', () =>
          ref.read(fundWalletNotifierProvider.notifier).loadVirtualAccount()),

      _guard('foreign-account-USD', () =>
          ref.read(foreignCurrencyNotifierProvider.notifier).fetchAccount('USD')),
      _guard('foreign-account-EUR', () =>
          ref.read(foreignCurrencyNotifierProvider.notifier).fetchAccount('EUR')),
      _guard('foreign-rates', () =>
          ref.read(foreignCurrencyNotifierProvider.notifier).fetchRates()),

      _guard('bank-list', _preloadBankList),

      _guard('airtime-networks', () =>
          ref.read(airtimeNotifierProvider.notifier).getAirtimeNetworks()),

      _guard('data-networks', () =>
          ref.read(dataNotifierProvider.notifier).getDataNetworksWithCache()),
    ]);

    print('⚡ DataPreloader: cache warm-up finished');
  }

  /// Runs a single preload task, swallowing any error so the rest continue.
  static Future<void> _guard(String label, Future<void> Function() task) async {
    try {
      await task();
    } catch (e) {
      print('⚡ DataPreloader: "$label" failed (ignored): $e');
    }
  }

  /// The bank list isn't behind a provider — the transfer screen fetches it
  /// inline — so we warm its cache here using the same endpoint + cache key.
  /// Skips the network entirely when a valid cached list already exists.
  static Future<void> _preloadBankList() async {
    final cache = CacheService();
    final cached = await cache.getCachedBankList();
    if (cached != null && cached.isNotEmpty) {
      print('⚡ DataPreloader: bank list already warm — skipping network');
      return;
    }

    final response = await ApiService.instance.get<Map<String, dynamic>>(
      '/bank-transfer/banklist',
      fromJson: (j) => j,
    );
    if (response.success && response.data != null) {
      final raw = response.data!['data'];
      if (raw is List) {
        final banks = raw.whereType<Map<String, dynamic>>().toList();
        if (banks.isNotEmpty) {
          await cache.cacheBankList(banks);
          print('⚡ DataPreloader: cached ${banks.length} banks');
        }
      }
    }
  }
}