// lib/services/cache/cache_service.dart
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:logger/logger.dart';

class CacheService {
  static final CacheService _instance = CacheService._internal();
  factory CacheService() => _instance;
  CacheService._internal();

  final Logger _logger = Logger();
  static const String _cachePrefix = 'cache_';
  static const String _timestampPrefix = 'timestamp_';

  // Cache TTL durations
  static const Duration _userBalanceTtl = Duration(seconds: 30);
  static const Duration _userProfileTtl = Duration(minutes: 5);
  static const Duration _transactionHistoryTtl = Duration(minutes: 2);
  static const Duration _networkListsTtl = Duration(minutes: 10);
  static const Duration _billCategoriesTtl = Duration(hours: 1);

  // Cache keys
  static const String _userBalanceKey = 'user_balance';
  static const String _userProfileKey = 'user_profile';
  static const String _userStatsKey = 'user_stats';
  static const String _transactionHistoryKey = 'transaction_history';
  static const Set<String> _sensitiveKeys = {
    _userBalanceKey, _userProfileKey, _userStatsKey,
    _transactionHistoryKey, 'recharge_card_epins',
    'foreign_account_USD', 'foreign_account_EUR',
  };
  final Map<String, dynamic> _sensitiveData = {};
  final Map<String, DateTime> _sensitiveTimestamps = {};
  bool _isSensitive(String key) =>
      _sensitiveKeys.contains(key) || key.startsWith('foreign_account_');

  Future<void> _removeLegacySensitiveCache(String key) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('$_cachePrefix$key');
    await prefs.remove('$_timestampPrefix$key');
  }
  static const String _networkListsKey = 'network_lists';
  static const String _billCategoriesKey = 'bill_categories';

  Future<void> _setCache(String key, dynamic data) async {
    if (_isSensitive(key)) {
      _sensitiveData[key] = data;
      _sensitiveTimestamps[key] = DateTime.now();
      await _removeLegacySensitiveCache(key);
      return;
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonData = jsonEncode(data);
      await prefs.setString('$_cachePrefix$key', jsonData);
      await prefs.setInt(
        '$_timestampPrefix$key',
        DateTime.now().millisecondsSinceEpoch,
      );
    } catch (e) {
      _logger.e('Error setting cache for key $key: $e');
    }
  }

  // Enhanced cache timestamp methods
  Future<void> setCacheTimestamp(String key, DateTime timestamp) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('${key}_timestamp', timestamp.toIso8601String());
      print('🔄 CacheService: Set timestamp for $key: $timestamp');
    } catch (e) {
      print('❌ CacheService: Error setting timestamp for $key: $e');
    }
  }

  Future<DateTime?> getCacheTimestamp(String key) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final timestampString = prefs.getString('${key}_timestamp');
      if (timestampString != null) {
        final timestamp = DateTime.parse(timestampString);
        print('🔄 CacheService: Got timestamp for $key: $timestamp');
        return timestamp;
      }
      return null;
    } catch (e) {
      print('❌ CacheService: Error getting timestamp for $key: $e');
      return null;
    }
  }

  Future<void> clearCacheTimestamp(String key) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('${key}_timestamp');
      print('🔄 CacheService: Cleared timestamp for $key');
    } catch (e) {
      print('❌ CacheService: Error clearing timestamp for $key: $e');
    }
  }

  // Method to check if cache is still valid
  Future<bool> isCacheValid(
    String key, {
    Duration validity = const Duration(minutes: 5),
  }) async {
    try {
      final timestamp = await getCacheTimestamp(key);
      if (timestamp == null) return false;

      final isValid = DateTime.now().difference(timestamp) < validity;
      print('🔄 CacheService: Cache $key is ${isValid ? 'valid' : 'expired'}');
      return isValid;
    } catch (e) {
      print('❌ CacheService: Error checking cache validity for $key: $e');
      return false;
    }
  }

  // Method to get cache age
  Future<Duration?> getCacheAge(String key) async {
    try {
      final timestamp = await getCacheTimestamp(key);
      if (timestamp == null) return null;

      final age = DateTime.now().difference(timestamp);
      print('🔄 CacheService: Cache $key age: $age');
      return age;
    } catch (e) {
      print('❌ CacheService: Error getting cache age for $key: $e');
      return null;
    }
  }

  // Utility method to format cache age for display
  String formatCacheAge(Duration age) {
    if (age.inMinutes < 1) {
      return 'Just now';
    } else if (age.inMinutes < 60) {
      return '${age.inMinutes}m ago';
    } else if (age.inHours < 24) {
      return '${age.inHours}h ago';
    } else {
      return '${age.inDays}d ago';
    }
  }

  Future<Map<String, dynamic>?> _getCache(String key) async {
    if (_isSensitive(key)) {
      await _removeLegacySensitiveCache(key);
      return _sensitiveData[key] as Map<String, dynamic>?;
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      final jsonData = prefs.getString('$_cachePrefix$key');
      final timestamp = prefs.getInt('$_timestampPrefix$key');

      if (jsonData != null && timestamp != null) {
        return jsonDecode(jsonData) as Map<String, dynamic>;
      }
      return null;
    } catch (e) {
      _logger.e('Error getting cache for key $key: $e');
      return null;
    }
  }

  Future<bool> _isCacheValid(String key, Duration ttl) async {
    if (_isSensitive(key)) {
      await _removeLegacySensitiveCache(key);
      final timestamp = _sensitiveTimestamps[key];
      return timestamp != null && DateTime.now().difference(timestamp) < ttl;
    }
    try {
      final prefs = await SharedPreferences.getInstance();
      final timestamp = prefs.getInt('$_timestampPrefix$key');

      if (timestamp == null) return false;

      final cacheTime = DateTime.fromMillisecondsSinceEpoch(timestamp);
      final now = DateTime.now();
      final age = now.difference(cacheTime);

      return age < ttl;
    } catch (e) {
      _logger.e('Error checking cache validity for key $key: $e');
      return false;
    }
  }

  Future<void> _clearCache(String key) async {
    _sensitiveData.remove(key);
    _sensitiveTimestamps.remove(key);
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('$_cachePrefix$key');
      await prefs.remove('$_timestampPrefix$key');
    } catch (e) {
      _logger.e('Error clearing cache for key $key: $e');
    }
  }

  // User Balance & Stats Cache
  Future<void> cacheUserBalance(Map<String, dynamic> balanceData) async {
    await _setCache(_userBalanceKey, balanceData);
  }

  Future<Map<String, dynamic>?> getCachedUserBalance() async {
    if (await _isCacheValid(_userBalanceKey, _userBalanceTtl)) {
      return await _getCache(_userBalanceKey);
    }
    return null;
  }

  // User Profile Cache
  Future<void> cacheUserProfile(Map<String, dynamic> profileData) async {
    await _setCache(_userProfileKey, profileData);
  }

  Future<Map<String, dynamic>?> getCachedUserProfile() async {
    if (await _isCacheValid(_userProfileKey, _userProfileTtl)) {
      return await _getCache(_userProfileKey);
    }
    return null;
  }

  // User Stats Cache
  Future<void> cacheUserStats(Map<String, dynamic> statsData) async {
    await _setCache(_userStatsKey, statsData);
  }

  Future<Map<String, dynamic>?> getCachedUserStats() async {
    if (await _isCacheValid(_userStatsKey, _userBalanceTtl)) {
      return await _getCache(_userStatsKey);
    }
    return null;
  }

  // Transaction History Cache
  Future<void> cacheTransactionHistory(
    List<Map<String, dynamic>> transactions,
  ) async {
    await _setCache(_transactionHistoryKey, {'transactions': transactions});
  }

  Future<List<Map<String, dynamic>>?> getCachedTransactionHistory() async {
    if (await _isCacheValid(_transactionHistoryKey, _transactionHistoryTtl)) {
      final data = await _getCache(_transactionHistoryKey);
      // jsonDecode gives a List<dynamic>; a direct `as List<Map<...>>` cast
      // throws at runtime. Use whereType to extract safely.
      final list = data?['transactions'];
      if (list is List) {
        return list.whereType<Map<String, dynamic>>().toList();
      }
    }
    return null;
  }

  // Network Lists Cache
  Future<void> cacheNetworkLists(Map<String, dynamic> networks) async {
    await _setCache(_networkListsKey, networks);
  }

  Future<Map<String, dynamic>?> getCachedNetworkLists() async {
    if (await _isCacheValid(_networkListsKey, _networkListsTtl)) {
      return await _getCache(_networkListsKey);
    }
    return null;
  }

  // Bill Categories Cache
  Future<void> cacheBillCategories(
    List<Map<String, dynamic>> categories,
  ) async {
    await _setCache(_billCategoriesKey, {'categories': categories});
  }

  Future<List<Map<String, dynamic>>?> getCachedBillCategories() async {
    if (await _isCacheValid(_billCategoriesKey, _billCategoriesTtl)) {
      final data = await _getCache(_billCategoriesKey);
      final list = data?['categories'];
      if (list is List) {
        return list.whereType<Map<String, dynamic>>().toList();
      }
    }
    return null;
  }

  // Enhanced Data Networks Cache with timestamp support
  Future<void> cacheDataNetworks(List<Map<String, dynamic>> networks) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final networksJson = json.encode(networks);
      await prefs.setString('data_networks', networksJson);

      // Automatically set timestamp when caching
      await setCacheTimestamp('data_networks', DateTime.now());

      print(
        '📶 CacheService: Cached ${networks.length} data networks with timestamp',
      );
    } catch (e) {
      print('❌ CacheService: Error caching data networks: $e');
    }
  }

  Future<List<Map<String, dynamic>>?> getCachedDataNetworks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final networksJson = prefs.getString('data_networks');
      if (networksJson != null) {
        final networks = json.decode(networksJson) as List<dynamic>;
        final result = networks.cast<Map<String, dynamic>>();
        print(
          '📶 CacheService: Retrieved ${result.length} cached data networks',
        );
        return result;
      }
      return null;
    } catch (e) {
      print('❌ CacheService: Error retrieving cached data networks: $e');
      return null;
    }
  }

  // Enhanced clear method that also clears timestamps
  Future<void> clearDataNetworks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('data_networks');
      await clearCacheTimestamp('data_networks');
      print('📶 CacheService: Cleared data networks and timestamp');
    } catch (e) {
      print('❌ CacheService: Error clearing data networks: $e');
    }
  }

  // Data Card Networks Cache with timestamp support
  Future<void> cacheDataCardNetworks(
    List<Map<String, dynamic>> networks,
  ) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final networksJson = json.encode(networks);
      await prefs.setString('data_card_networks', networksJson);

      // Automatically set timestamp when caching
      await setCacheTimestamp('data_card_networks', DateTime.now());

      print(
        '📶 CacheService: Cached ${networks.length} data card networks with timestamp',
      );
    } catch (e) {
      print('❌ CacheService: Error caching data card networks: $e');
    }
  }

  Future<List<Map<String, dynamic>>?> getCachedDataCardNetworks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final networksJson = prefs.getString('data_card_networks');
      if (networksJson != null) {
        final networks = json.decode(networksJson) as List<dynamic>;
        final result = networks.cast<Map<String, dynamic>>();
        print(
          '📶 CacheService: Retrieved ${result.length} cached data card networks',
        );
        return result;
      }
      return null;
    } catch (e) {
      print('❌ CacheService: Error retrieving cached data card networks: $e');
      return null;
    }
  }

  // Enhanced clear method that also clears timestamps
  Future<void> clearDataCardNetworks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('data_card_networks');
      await clearCacheTimestamp('data_card_networks');
      print('📶 CacheService: Cleared data card networks and timestamp');
    } catch (e) {
      print('❌ CacheService: Error clearing data card networks: $e');
    }
  }

  // Airtime Networks Cache
  // Networks rarely change, so cache for 7 days and skip the network while
  // the cache is still fresh (same approach as the bank list / data networks).
  static const Duration _airtimeNetworksTtl = Duration(days: 7);

  Future<void> cacheAirtimeNetworks(List<Map<String, dynamic>> networks) async {
    await _setCache('airtime_networks', {'networks': networks});
  }

  Future<List<Map<String, dynamic>>?> getCachedAirtimeNetworks() async {
    if (await _isCacheValid('airtime_networks', _airtimeNetworksTtl)) {
      final data = await _getCache('airtime_networks');
      final list = data?['networks'];
      if (list is List) {
        return list.whereType<Map<String, dynamic>>().toList();
      }
    }
    return null;
  }

  /// True when the airtime networks cache exists and is younger than 7 days.
  Future<bool> isAirtimeNetworksFresh() =>
      _isCacheValid('airtime_networks', _airtimeNetworksTtl);

  // Enhanced cable networks caching with automatic timestamp
  Future<void> cacheCableNetworks(List<Map<String, dynamic>> networks) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final networksJson = json.encode(networks);
      await prefs.setString('cable_networks', networksJson);

      // Automatically set timestamp when caching
      await setCacheTimestamp('cable_networks', DateTime.now());

      print(
        '📺 CacheService: Cached ${networks.length} cable networks with timestamp',
      );
    } catch (e) {
      print('❌ CacheService: Error caching cable networks: $e');
    }
  }

  Future<List<Map<String, dynamic>>?> getCachedCableNetworks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final networksJson = prefs.getString('cable_networks');
      if (networksJson != null) {
        final networks = json.decode(networksJson) as List<dynamic>;
        final result = networks.cast<Map<String, dynamic>>();
        print(
          '📺 CacheService: Retrieved ${result.length} cached cable networks',
        );
        return result;
      }
      return null;
    } catch (e) {
      print('❌ CacheService: Error retrieving cached cable networks: $e');
      return null;
    }
  }

  // Enhanced clear method that also clears timestamps
  Future<void> clearCableNetworks() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('cable_networks');
      await clearCacheTimestamp('cable_networks');
      print('📺 CacheService: Cleared cable networks and timestamp');
    } catch (e) {
      print('❌ CacheService: Error clearing cable networks: $e');
    }
  }

  // Bill Networks Cache
  Future<void> cacheBillNetworks(List<Map<String, dynamic>> networks) async {
    await _setCache('bill_networks', {'networks': networks});
  }

  Future<List<Map<String, dynamic>>?> getCachedBillNetworks() async {
    if (await _isCacheValid('bill_networks', _networkListsTtl)) {
      final data = await _getCache('bill_networks');
      final list = data?['networks'];
      if (list is List) {
        return list.whereType<Map<String, dynamic>>().toList();
      }
    }
    return null;
  }

  // ─── Bank List Cache ────────────────────────────────────────────────────────
  // Banks essentially never change. We refresh every 3 weeks (the value the
  // product team asked for) so the picker is instant on every open.
  static const Duration _bankListTtl = Duration(days: 30);
  static const String _bankListKey  = 'bank_list';

  Future<void> cacheBankList(List<Map<String, dynamic>> banks) async {
    await _setCache(_bankListKey, {'banks': banks});
    print('🏦 CacheService: Cached ${banks.length} banks');
  }

  Future<List<Map<String, dynamic>>?> getCachedBankList() async {
    if (await _isCacheValid(_bankListKey, _bankListTtl)) {
      final data = await _getCache(_bankListKey);
      final list = data?['banks'];
      if (list is List) {
        return list.whereType<Map<String, dynamic>>().toList();
      }
    }
    return null;
  }

  Future<void> clearBankList() async {
    await _clearCache(_bankListKey);
  }

  // ─── Recharge Card (E-PIN) Cache ────────────────────────────────────────────
  // The user's purchase history; gets refreshed by the provider after each buy.
  static const Duration _rechargeCardTtl = Duration(minutes: 10);
  static const String _rechargeCardKey  = 'recharge_card_epins';

  Future<void> cacheRechargeCardEpins(List<Map<String, dynamic>> epins) async {
    await _setCache(_rechargeCardKey, {'epins': epins});
    print('💳 CacheService: Cached ${epins.length} recharge card orders');
  }

  Future<List<Map<String, dynamic>>?> getCachedRechargeCardEpins() async {
    if (await _isCacheValid(_rechargeCardKey, _rechargeCardTtl)) {
      final data = await _getCache(_rechargeCardKey);
      final list = data?['epins'];
      if (list is List) {
        return list.whereType<Map<String, dynamic>>().toList();
      }
    }
    return null;
  }

  Future<void> clearRechargeCardEpins() async {
    await _clearCache(_rechargeCardKey);
  }

  // ─── Foreign Currency Account Cache ─────────────────────────────────────────
  // Keyed per currency (USD, EUR, …). Stores the full account payload —
  // account number, account name, bank name, routing/swift/IBAN codes, bank
  // address and beneficiary address. These are essentially static once
  // issued, so we use a long TTL (7 days) and rely on the provider to
  // invalidate the cache after every action that can change the balance
  // (convert / send / create).
  static const Duration _foreignAccountTtl = Duration(days: 7);
  String _foreignAccountKey(String currency) =>
      'foreign_account_${currency.toUpperCase()}';

  Future<void> cacheForeignAccount(
      String currency, Map<String, dynamic> raw) async {
    await _setCache(_foreignAccountKey(currency), raw);
    print('🌍 CacheService: Cached $currency foreign account');
  }

  Future<Map<String, dynamic>?> getCachedForeignAccount(String currency) async {
    final key = _foreignAccountKey(currency);
    if (await _isCacheValid(key, _foreignAccountTtl)) {
      return await _getCache(key);
    }
    return null;
  }

  Future<void> clearForeignAccount(String currency) async {
    await _clearCache(_foreignAccountKey(currency));
  }

  // ─── Last authenticated user marker ─────────────────────────────────────────
  // Stored OUTSIDE the cache_/timestamp_ namespace on purpose so it survives
  // BOTH clearAllCache() AND the secure-storage deleteAll() the 401 interceptor
  // runs. Lets login() tell whether the incoming user is the same account
  // (keep the cache warm) or a different one (wipe it).
  static const String _lastUserIdKey = 'last_authenticated_user_id';

  Future<String?> getLastUserId() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      return prefs.getString(_lastUserIdKey);
    } catch (e) {
      _logger.e('Error reading last user id: $e');
      return null;
    }
  }

  Future<void> setLastUserId(String userId) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_lastUserIdKey, userId);
    } catch (e) {
      _logger.e('Error saving last user id: $e');
    }
  }

  // Clear specific cache
  Future<void> clearUserCache() async {
    await _clearCache(_userBalanceKey);
    await _clearCache(_userProfileKey);
    await _clearCache(_userStatsKey);
  }

  Future<void> clearTransactionCache() async {
    await _clearCache(_transactionHistoryKey);
  }

  Future<void> clearNetworkCache() async {
    await _clearCache(_networkListsKey);
    await clearDataNetworks();
    await clearDataCardNetworks();
    await _clearCache('airtime_networks');
    await clearCableNetworks();
    await _clearCache('bill_networks');
    await clearBankList();
    await clearRechargeCardEpins();
    await clearForeignAccount('USD');
    await clearForeignAccount('EUR');
  }

  Future<void> clearBillCache() async {
    await _clearCache(_billCategoriesKey);
  }

  // ─── Shared reference data (survives logout / account switch) ───────────────
  // Bank list, data plans/networks and airtime networks are NOT tied to any
  // single account — they're identical for every user and only change once in
  // a blue moon (each has its own 7–30 day TTL handling freshness). So they
  // must NOT be wiped on logout, registration, password reset or account
  // switch. Keeping them keeps the bank/data/airtime pickers instant on the
  // very first open after a fresh login.
  //
  // Constant string interpolation is a compile-time constant in Dart, so these
  // stay in sync automatically if the prefixes ever change.
  static const Set<String> _referenceDataKeys = {
    '${_cachePrefix}bank_list',          // bank list payload
    '${_timestampPrefix}bank_list',      // bank list timestamp
    'data_networks',                     // data networks payload
    'data_networks_timestamp',           // data networks timestamp
    'data_card_networks',                // data card networks payload
    'data_card_networks_timestamp',      // data card networks timestamp
    '${_cachePrefix}airtime_networks',   // airtime networks payload
    '${_timestampPrefix}airtime_networks', // airtime networks timestamp
  };

  // Clear all cache including enhanced caches.
  //
  // By default this PRESERVES shared reference data (bank list, data &
  // airtime networks) so it stays cached across logout and account switches.
  // Pass preserveReferenceData: false only when you truly need a full wipe
  // (e.g. a manual "reset app data" action).
  Future<void> clearAllCache({bool preserveReferenceData = true}) async {
    _sensitiveData.clear();
    _sensitiveTimestamps.clear();
    try {
      final prefs = await SharedPreferences.getInstance();
      // Snapshot the keys first — removing while iterating the live set can
      // throw a concurrent-modification error on some platforms.
      final keys = prefs.getKeys().toList();

      for (final key in keys) {
        final isCacheKey = key.startsWith(_cachePrefix) ||
            key.startsWith(_timestampPrefix) ||
            key.endsWith('_timestamp') ||
            key == 'cable_networks' ||
            key == 'data_networks' ||
            key == 'data_card_networks';

        if (!isCacheKey) continue;

        // Keep bank/data/airtime alive unless a full wipe was explicitly asked.
        if (preserveReferenceData && _referenceDataKeys.contains(key)) {
          continue;
        }

        await prefs.remove(key);
      }

      print(
        '🔄 CacheService: Cleared cache '
        '${preserveReferenceData ? '(kept bank/data/airtime reference data)' : '(full wipe)'}',
      );
    } catch (e) {
      _logger.e('Error clearing all cache: $e');
    }
  }
}
