// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import '../repositories/data_repository.dart';
// import '../models/data/data_model.dart';
// import '../models/data/buy_data_request.dart';
// import '../models/data/buy_data_card_request.dart';
// import '../models/base/api_response.dart';
// import '../core/network/api_service.dart';
// import '../services/cache/cache_service.dart';

// // Sentinel used by [DataState.copyWith] so callers can explicitly pass
// // `null` to clear a nullable field. Without this, `field: field ?? this.field`
// // can never actually set a field back to null once it's been set — any
// // `copyWith(error: null)` call silently keeps the old value, which is why
// // `clearError()` used to be a no-op and a stale error (e.g. "LOW WALLET
// // BALANCE" from a single failed request) would get stuck on screen forever,
// // surviving every later state update.
// const Object _unset = Object();

// // State class for data operations
// class DataState {
//   final bool isLoading;
//   final bool isRefreshing;
//   final String? error;
//   final List<DataNetworkWithTypesModel>? dataNetworks;
//   final List<DataNetworkModel>? dataCardNetworks;
//   final Map<String, dynamic>? lastTransaction;
//   final DateTime? lastUpdated;
//   final DateTime? dataCardLastUpdated;
//   final bool isLoadedFromCache;
//   final bool isDataCardLoadedFromCache;

//   DataState({
//     this.isLoading = false,
//     this.isRefreshing = false,
//     this.error,
//     this.dataNetworks,
//     this.dataCardNetworks,
//     this.lastTransaction,
//     this.lastUpdated,
//     this.dataCardLastUpdated,
//     this.isLoadedFromCache = false,
//     this.isDataCardLoadedFromCache = false,
//   });

//   DataState copyWith({
//     bool? isLoading,
//     bool? isRefreshing,
//     Object? error = _unset,
//     Object? dataNetworks = _unset,
//     Object? dataCardNetworks = _unset,
//     Object? lastTransaction = _unset,
//     Object? lastUpdated = _unset,
//     Object? dataCardLastUpdated = _unset,
//     bool? isLoadedFromCache,
//     bool? isDataCardLoadedFromCache,
//   }) {
//     return DataState(
//       isLoading: isLoading ?? this.isLoading,
//       isRefreshing: isRefreshing ?? this.isRefreshing,
//       error: error == _unset ? this.error : error as String?,
//       dataNetworks: dataNetworks == _unset
//           ? this.dataNetworks
//           : dataNetworks as List<DataNetworkWithTypesModel>?,
//       dataCardNetworks: dataCardNetworks == _unset
//           ? this.dataCardNetworks
//           : dataCardNetworks as List<DataNetworkModel>?,
//       lastTransaction: lastTransaction == _unset
//           ? this.lastTransaction
//           : lastTransaction as Map<String, dynamic>?,
//       lastUpdated: lastUpdated == _unset
//           ? this.lastUpdated
//           : lastUpdated as DateTime?,
//       dataCardLastUpdated: dataCardLastUpdated == _unset
//           ? this.dataCardLastUpdated
//           : dataCardLastUpdated as DateTime?,
//       isLoadedFromCache: isLoadedFromCache ?? this.isLoadedFromCache,
//       isDataCardLoadedFromCache:
//           isDataCardLoadedFromCache ?? this.isDataCardLoadedFromCache,
//     );
//   }

//   /// Check if data networks cache is still valid (5 minutes by default)
//   bool isDataNetworksCacheValid({
//     Duration cacheValidity = const Duration(minutes: 5),
//   }) {
//     if (lastUpdated == null || dataNetworks == null) return false;
//     return DateTime.now().difference(lastUpdated!) < cacheValidity;
//   }

//   /// Check if data card networks cache is still valid (5 minutes by default)
//   bool isDataCardNetworksCacheValid({
//     Duration cacheValidity = const Duration(minutes: 5),
//   }) {
//     if (dataCardLastUpdated == null || dataCardNetworks == null) return false;
//     return DateTime.now().difference(dataCardLastUpdated!) < cacheValidity;
//   }

//   /// Check if data networks exist (either from cache or network)
//   bool get hasDataNetworks => dataNetworks != null && dataNetworks!.isNotEmpty;

//   /// Check if data card networks exist (either from cache or network)
//   bool get hasDataCardNetworks =>
//       dataCardNetworks != null && dataCardNetworks!.isNotEmpty;
// }

// // StateNotifier for data operations
// class DataNotifier extends StateNotifier<DataState> {
//   final DataRepository _dataRepository;

//   DataNotifier(this._dataRepository) : super(DataState()) {
//     // Initialize data from cache when provider is created
//     _initializeFromCache();
//   }

//   /// Initialize data from cache on startup
//   Future<void> _initializeFromCache() async {
//     print('📶 DataProvider: Initializing data from cache...');

//     try {
//       // Restore data networks from cache
//       final cachedNetworks = await CacheService().getCachedDataNetworks();
//       if (cachedNetworks != null && cachedNetworks.isNotEmpty) {
//         print('📶 DataProvider: Found cached data networks, restoring state');

//         final networks =
//             cachedNetworks
//                 .map((network) => DataNetworkWithTypesModel.fromJson(network))
//                 .toList();

//         // Get the cache timestamp if available
//         final cacheTimestamp = await CacheService().getCacheTimestamp(
//           'data_networks',
//         );

//         state = state.copyWith(
//           dataNetworks: networks,
//           lastUpdated: cacheTimestamp ?? DateTime.now(),
//           isLoadedFromCache: true,
//         );

//         print(
//           '📶 DataProvider: Restored ${networks.length} data networks from cache',
//         );
//       }

//       // Restore data card networks from cache (assuming you have a similar cache method)
//       final cachedCardNetworks =
//           await CacheService().getCachedDataCardNetworks();
//       if (cachedCardNetworks != null && cachedCardNetworks.isNotEmpty) {
//         print(
//           '📶 DataProvider: Found cached data card networks, restoring state',
//         );

//         final cardNetworks =
//             cachedCardNetworks
//                 .map((network) => DataNetworkModel.fromJson(network))
//                 .toList();

//         // Get the cache timestamp if available
//         final cardCacheTimestamp = await CacheService().getCacheTimestamp(
//           'data_card_networks',
//         );

//         state = state.copyWith(
//           dataCardNetworks: cardNetworks,
//           dataCardLastUpdated: cardCacheTimestamp ?? DateTime.now(),
//           isDataCardLoadedFromCache: true,
//         );

//         print(
//           '📶 DataProvider: Restored ${cardNetworks.length} data card networks from cache',
//         );
//       }
//     } catch (e) {
//       print('📶 DataProvider: Error restoring from cache: $e');
//     }
//   }

//   /// Get data networks with smart caching
//   Future<void> getDataNetworksWithCache({
//     Duration cacheValidity = const Duration(minutes: 5),
//     bool showRefreshingIndicator = false,
//   }) async {
//     print('📶 DataProvider: Getting data networks with cache check');

//     // If we have valid cached data, return early
//     if (state.isDataNetworksCacheValid(cacheValidity: cacheValidity)) {
//       print('📶 DataProvider: Using valid cached data networks');
//       return;
//     }

//     // If we have data but it's stale, show refreshing indicator instead of loading
//     if (state.hasDataNetworks && showRefreshingIndicator) {
//       state = state.copyWith(isRefreshing: true, error: null);
//     } else {
//       state = state.copyWith(isLoading: true, error: null);
//     }

//     await _fetchDataNetworksFromNetwork();
//   }

//   /// Get data networks (force refresh from network)
//   Future<void> getDataNetworks({bool forceRefresh = false}) async {
//     print(
//       '📶 DataProvider: Getting data networks ${forceRefresh ? '(force refresh)' : ''}',
//     );

//     // If force refresh, always fetch from network
//     if (forceRefresh) {
//       state = state.copyWith(isRefreshing: true, error: null);
//       await _fetchDataNetworksFromNetwork();
//       return;
//     }

//     // Otherwise use smart caching
//     await getDataNetworksWithCache();
//   }

//   /// Fetch data networks from network
//   Future<void> _fetchDataNetworksFromNetwork() async {
//     try {
//       print('📶 DataProvider: Fetching data networks from network');
//       final response = await _dataRepository.getDataNetworks();

//       if (response.success && response.data != null) {
//         print(
//           '📶 DataProvider: Successfully got ${response.data!.data!.length} data networks',
//         );

//         // Cache the data networks with timestamp
//         final networksToCache =
//             response.data!.data!.map((network) => network.toJson()).toList();
//         await CacheService().cacheDataNetworks(networksToCache);
//         await CacheService().setCacheTimestamp('data_networks', DateTime.now());

//         state = state.copyWith(
//           isLoading: false,
//           isRefreshing: false,
//           dataNetworks: response.data!.data,
//           lastUpdated: DateTime.now(),
//           isLoadedFromCache: false,
//           error: null,
//         );
//       } else {
//         print(
//           '📶 DataProvider: Failed to get data networks - ${response.message}',
//         );

//         // If we have cached data, keep it and just show error briefly
//         if (state.hasDataNetworks) {
//           state = state.copyWith(
//             isLoading: false,
//             isRefreshing: false,
//             // Don't clear existing data, just show error temporarily
//           );

//           // Clear error after 3 seconds to not persist the error state
//           Future.delayed(const Duration(seconds: 3), () {
//             if (mounted) {
//               clearError();
//             }
//           });
//         } else {
//           state = state.copyWith(
//             isLoading: false,
//             isRefreshing: false,
//             error: response.message ?? 'Failed to get data networks',
//           );
//         }
//       }
//     } catch (e) {
//       print('📶 DataProvider: Error getting data networks - $e');

//       // Same logic as above - preserve existing data if available
//       if (state.hasDataNetworks) {
//         state = state.copyWith(isLoading: false, isRefreshing: false);

//         // Clear error after 3 seconds
//         Future.delayed(const Duration(seconds: 3), () {
//           if (mounted) {
//             clearError();
//           }
//         });
//       } else {
//         state = state.copyWith(
//           isLoading: false,
//           isRefreshing: false,
//           error: e.toString(),
//         );
//       }
//     }
//   }

//   /// Get data card networks with smart caching
//   Future<void> getDataCardNetworksWithCache({
//     Duration cacheValidity = const Duration(minutes: 5),
//     bool showRefreshingIndicator = false,
//   }) async {
//     print('📶 DataProvider: Getting data card networks with cache check');

//     // If we have valid cached data, return early
//     if (state.isDataCardNetworksCacheValid(cacheValidity: cacheValidity)) {
//       print('📶 DataProvider: Using valid cached data card networks');
//       return;
//     }

//     // If we have data but it's stale, show refreshing indicator instead of loading
//     if (state.hasDataCardNetworks && showRefreshingIndicator) {
//       state = state.copyWith(isRefreshing: true, error: null);
//     } else {
//       state = state.copyWith(isLoading: true, error: null);
//     }

//     await _fetchDataCardNetworksFromNetwork();
//   }

//   /// Get data card networks (force refresh from network)
//   Future<void> getDataCardNetworks({bool forceRefresh = false}) async {
//     print(
//       '📶 DataProvider: Getting data card networks ${forceRefresh ? '(force refresh)' : ''}',
//     );

//     // If force refresh, always fetch from network
//     if (forceRefresh) {
//       state = state.copyWith(isRefreshing: true, error: null);
//       await _fetchDataCardNetworksFromNetwork();
//       return;
//     }

//     // Otherwise use smart caching
//     await getDataCardNetworksWithCache();
//   }

//   /// Fetch data card networks from network
//   Future<void> _fetchDataCardNetworksFromNetwork() async {
//     try {
//       print('📶 DataProvider: Fetching data card networks from network');
//       final response = await _dataRepository.getDataCardNetworks();

//       if (response.success && response.data != null) {
//         print(
//           '📶 DataProvider: Successfully got ${response.data!.data!.length} data card networks',
//         );

//         // Cache the data card networks with timestamp
//         final networksToCache =
//             response.data!.data!.map((network) => network.toJson()).toList();
//         await CacheService().cacheDataCardNetworks(networksToCache);
//         await CacheService().setCacheTimestamp(
//           'data_card_networks',
//           DateTime.now(),
//         );

//         state = state.copyWith(
//           isLoading: false,
//           isRefreshing: false,
//           dataCardNetworks: response.data!.data,
//           dataCardLastUpdated: DateTime.now(),
//           isDataCardLoadedFromCache: false,
//           error: null,
//         );
//       } else {
//         print(
//           '📶 DataProvider: Failed to get data card networks - ${response.message}',
//         );

//         // If we have cached data, keep it and just show error briefly
//         if (state.hasDataCardNetworks) {
//           state = state.copyWith(
//             isLoading: false,
//             isRefreshing: false,
//             // Don't clear existing data, just show error temporarily
//           );

//           // Clear error after 3 seconds to not persist the error state
//           Future.delayed(const Duration(seconds: 3), () {
//             if (mounted) {
//               clearError();
//             }
//           });
//         } else {
//           state = state.copyWith(
//             isLoading: false,
//             isRefreshing: false,
//             error: response.message ?? 'Failed to get data card networks',
//           );
//         }
//       }
//     } catch (e) {
//       print('📶 DataProvider: Error getting data card networks - $e');

//       // Same logic as above - preserve existing data if available
//       if (state.hasDataCardNetworks) {
//         state = state.copyWith(isLoading: false, isRefreshing: false);

//         // Clear error after 3 seconds
//         Future.delayed(const Duration(seconds: 3), () {
//           if (mounted) {
//             clearError();
//           }
//         });
//       } else {
//         state = state.copyWith(
//           isLoading: false,
//           isRefreshing: false,
//           error: e.toString(),
//         );
//       }
//     }
//   }

//   /// Buy data
//   ///
//   /// Note: deliberately does NOT touch `state.isLoading` / `state.error`.
//   /// Those fields drive the screen-level "fetching networks" loading/error
//   /// views in BuyDataScreen. Writing to them here used to cause two problems
//   /// during a purchase: (1) the screen's own network-loading spinner would
//   /// pop up underneath the purchase's loading dialog, showing two loaders
//   /// at once, and (2) a failed purchase would set `state.error`, which made
//   /// the whole screen fall back to the "Retry" (re-fetch networks) error
//   /// view instead of just showing a snackbar on the purchase screen.
//   /// Callers should rely solely on the returned ApiResponse to know the
//   /// purchase outcome.
//   Future<ApiResponse<Map<String, dynamic>>> buyData({
//     required String phoneNumber,
//     required String networkId,
//     required String planId,
//     required String dataTypeId,
//     required String transactionPin,
//   }) async {
//     try {
//       print('📶 DataProvider: Buying data');
//       final request = BuyDataRequest(
//         phoneNumber: phoneNumber,
//         networkId: networkId,
//         planId: planId,
//         dataTypeId: dataTypeId,
//         transactionPin: transactionPin,
//       );

//       final response = await _dataRepository.buyData(request);

//       if (response.success) {
//         print('📶 DataProvider: Data purchase successful');
//         state = state.copyWith(lastTransaction: response.data);
//       } else {
//         print('📶 DataProvider: Data purchase failed - ${response.message}');
//       }

//       return response;
//     } catch (e) {
//       print('📶 DataProvider: Error buying data - $e');
//       return ApiResponse<Map<String, dynamic>>(
//         success: false,
//         message: e.toString(),
//       );
//     }
//   }

//   /// Buy data card
//   ///
//   /// See the note on [buyData] above — this intentionally avoids touching
//   /// `state.isLoading` / `state.error` for the same reasons.
//   Future<ApiResponse<Map<String, dynamic>>> buyDataCard({
//     required String name,
//     required String quantity,
//     required String networkId,
//     required String planId,
//     required String transactionPin,
//   }) async {
//     try {
//       print('📶 DataProvider: Buying data card');
//       final request = BuyDataCardRequest(
//         name: name,
//         quantity: quantity,
//         networkId: networkId,
//         planId: planId,
//         transactionPin: transactionPin,
//       );

//       final response = await _dataRepository.buyDataCard(request);

//       if (response.success) {
//         print('📶 DataProvider: Data card purchase successful');
//         state = state.copyWith(lastTransaction: response.data);
//       } else {
//         print(
//           '📶 DataProvider: Data card purchase failed - ${response.message}',
//         );
//       }

//       return response;
//     } catch (e) {
//       print('📶 DataProvider: Error buying data card - $e');
//       return ApiResponse<Map<String, dynamic>>(
//         success: false,
//         message: e.toString(),
//       );
//     }
//   }

//   /// Refresh data networks (force refresh with refreshing indicator)
//   Future<void> refreshDataNetworks() async {
//     print('📶 DataProvider: Refreshing data networks');
//     await getDataNetworks(forceRefresh: true);
//   }

//   /// Refresh data card networks (force refresh with refreshing indicator)
//   Future<void> refreshDataCardNetworks() async {
//     print('📶 DataProvider: Refreshing data card networks');
//     await getDataCardNetworks(forceRefresh: true);
//   }

//   /// Refresh all data (both data networks and data card networks)
//   Future<void> refreshAll() async {
//     print('📶 DataProvider: Refreshing all data');
//     await Future.wait([
//       getDataNetworks(forceRefresh: true),
//       getDataCardNetworks(forceRefresh: true),
//     ]);
//   }

//   /// Clear data networks cache and reload
//   Future<void> clearDataNetworksCacheAndReload() async {
//     print('📶 DataProvider: Clearing data networks cache and reloading');
//     await CacheService().clearDataNetworks();
//     state = state.copyWith(
//       dataNetworks: null,
//       lastUpdated: null,
//       isLoadedFromCache: false,
//     );
//     await getDataNetworks(forceRefresh: true);
//   }

//   /// Clear data card networks cache and reload
//   Future<void> clearDataCardNetworksCacheAndReload() async {
//     print('📶 DataProvider: Clearing data card networks cache and reloading');
//     await CacheService().clearDataCardNetworks();
//     state = state.copyWith(
//       dataCardNetworks: null,
//       dataCardLastUpdated: null,
//       isDataCardLoadedFromCache: false,
//     );
//     await getDataCardNetworks(forceRefresh: true);
//   }

//   /// Clear all cache and reload
//   Future<void> clearAllCacheAndReload() async {
//     print('📶 DataProvider: Clearing all cache and reloading');
//     await Future.wait([
//       CacheService().clearDataNetworks(),
//       CacheService().clearDataCardNetworks(),
//     ]);
//     state = state.copyWith(
//       dataNetworks: null,
//       dataCardNetworks: null,
//       lastUpdated: null,
//       dataCardLastUpdated: null,
//       isLoadedFromCache: false,
//       isDataCardLoadedFromCache: false,
//     );
//     await refreshAll();
//   }

//   /// Clear error
//   void clearError() {
//     if (state.error != null) {
//       state = state.copyWith(error: null);
//     }
//   }

//   /// Clear last transaction
//   void clearLastTransaction() {
//     state = state.copyWith(lastTransaction: null);
//   }
// }

// // Providers
// final dataRepositoryProvider = Provider<DataRepository>((ref) {
//   return DataRepository(ApiService.instance);
// });

// final dataNotifierProvider = StateNotifierProvider<DataNotifier, DataState>((
//   ref,
// ) {
//   final dataRepository = ref.watch(dataRepositoryProvider);
//   return DataNotifier(dataRepository);
// });

// // Convenience providers
// final dataNetworksProvider = Provider<List<DataNetworkWithTypesModel>?>((ref) {
//   return ref.watch(dataNotifierProvider).dataNetworks;
// });

// final dataCardNetworksProvider = Provider<List<DataNetworkModel>?>((ref) {
//   return ref.watch(dataNotifierProvider).dataCardNetworks;
// });

// final dataLoadingProvider = Provider<bool>((ref) {
//   return ref.watch(dataNotifierProvider).isLoading;
// });

// final dataRefreshingProvider = Provider<bool>((ref) {
//   return ref.watch(dataNotifierProvider).isRefreshing;
// });

// final dataErrorProvider = Provider<String?>((ref) {
//   return ref.watch(dataNotifierProvider).error;
// });

// final dataLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
//   return ref.watch(dataNotifierProvider).lastTransaction;
// });

// final dataLastUpdatedProvider = Provider<DateTime?>((ref) {
//   return ref.watch(dataNotifierProvider).lastUpdated;
// });

// final dataCardLastUpdatedProvider = Provider<DateTime?>((ref) {
//   return ref.watch(dataNotifierProvider).dataCardLastUpdated;
// });

// final dataCacheStatusProvider = Provider<bool>((ref) {
//   return ref.watch(dataNotifierProvider).isLoadedFromCache;
// });

// final dataCardCacheStatusProvider = Provider<bool>((ref) {
//   return ref.watch(dataNotifierProvider).isDataCardLoadedFromCache;
// });

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/data_repository.dart';
import '../models/data/data_model.dart';
import '../models/data/buy_data_request.dart';
import '../models/data/buy_data_card_request.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import '../services/cache/cache_service.dart';
import 'auth_provider.dart';

// Sentinel used by [DataState.copyWith] so callers can explicitly pass
// `null` to clear a nullable field. Without this, `field: field ?? this.field`
// can never actually set a field back to null once it's been set — any
// `copyWith(error: null)` call silently keeps the old value, which is why
// `clearError()` used to be a no-op and a stale error (e.g. "LOW WALLET
// BALANCE" from a single failed request) would get stuck on screen forever,
// surviving every later state update.
const Object _unset = Object();

// State class for data operations
class DataState {
  final bool isLoading;
  final bool isRefreshing;
  final String? error;
  final List<DataNetworkWithTypesModel>? dataNetworks;
  final List<DataNetworkModel>? dataCardNetworks;
  final Map<String, dynamic>? lastTransaction;
  final DateTime? lastUpdated;
  final DateTime? dataCardLastUpdated;
  final bool isLoadedFromCache;
  final bool isDataCardLoadedFromCache;

  DataState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.error,
    this.dataNetworks,
    this.dataCardNetworks,
    this.lastTransaction,
    this.lastUpdated,
    this.dataCardLastUpdated,
    this.isLoadedFromCache = false,
    this.isDataCardLoadedFromCache = false,
  });

  DataState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    Object? error = _unset,
    Object? dataNetworks = _unset,
    Object? dataCardNetworks = _unset,
    Object? lastTransaction = _unset,
    Object? lastUpdated = _unset,
    Object? dataCardLastUpdated = _unset,
    bool? isLoadedFromCache,
    bool? isDataCardLoadedFromCache,
  }) {
    return DataState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      error: error == _unset ? this.error : error as String?,
      dataNetworks: dataNetworks == _unset
          ? this.dataNetworks
          : dataNetworks as List<DataNetworkWithTypesModel>?,
      dataCardNetworks: dataCardNetworks == _unset
          ? this.dataCardNetworks
          : dataCardNetworks as List<DataNetworkModel>?,
      lastTransaction: lastTransaction == _unset
          ? this.lastTransaction
          : lastTransaction as Map<String, dynamic>?,
      lastUpdated: lastUpdated == _unset
          ? this.lastUpdated
          : lastUpdated as DateTime?,
      dataCardLastUpdated: dataCardLastUpdated == _unset
          ? this.dataCardLastUpdated
          : dataCardLastUpdated as DateTime?,
      isLoadedFromCache: isLoadedFromCache ?? this.isLoadedFromCache,
      isDataCardLoadedFromCache:
          isDataCardLoadedFromCache ?? this.isDataCardLoadedFromCache,
    );
  }

  /// Check if data networks cache is still valid (5 minutes by default)
  bool isDataNetworksCacheValid({
    Duration cacheValidity = const Duration(minutes: 5),
  }) {
    if (lastUpdated == null || dataNetworks == null) return false;
    return DateTime.now().difference(lastUpdated!) < cacheValidity;
  }

  /// Check if data card networks cache is still valid (5 minutes by default)
  bool isDataCardNetworksCacheValid({
    Duration cacheValidity = const Duration(minutes: 5),
  }) {
    if (dataCardLastUpdated == null || dataCardNetworks == null) return false;
    return DateTime.now().difference(dataCardLastUpdated!) < cacheValidity;
  }

  /// Check if data networks exist (either from cache or network)
  bool get hasDataNetworks => dataNetworks != null && dataNetworks!.isNotEmpty;

  /// Check if data card networks exist (either from cache or network)
  bool get hasDataCardNetworks =>
      dataCardNetworks != null && dataCardNetworks!.isNotEmpty;
}

// StateNotifier for data operations
class DataNotifier extends StateNotifier<DataState> {
  final DataRepository _dataRepository;
  final Ref _ref;

  // Async SharedPreferences restore — see getDataNetworksWithCache() for why
  // this is stored and awaited rather than just fired-and-forgotten.
  late final Future<void> _ready;

  DataNotifier(this._dataRepository, this._ref) : super(DataState()) {
    // Initialize data from cache when provider is created
    _ready = _initializeFromCache();
  }

  /// Pull fresh wallet balances into the auth state after a purchase so the
  /// home screen updates on its own — no manual pull-to-refresh needed.
  /// Fire-and-forget: the money has already moved, so a refresh failure must
  /// never surface as a failed purchase.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  /// Initialize data from cache on startup
  Future<void> _initializeFromCache() async {
    print('📶 DataProvider: Initializing data from cache...');

    try {
      // Restore data networks from cache
      final cachedNetworks = await CacheService().getCachedDataNetworks();
      if (cachedNetworks != null && cachedNetworks.isNotEmpty) {
        print('📶 DataProvider: Found cached data networks, restoring state');

        final networks =
            cachedNetworks
                .map((network) => DataNetworkWithTypesModel.fromJson(network))
                .toList();

        // Get the cache timestamp if available
        final cacheTimestamp = await CacheService().getCacheTimestamp(
          'data_networks',
        );

        state = state.copyWith(
          dataNetworks: networks,
          lastUpdated: cacheTimestamp ?? DateTime.now(),
          isLoadedFromCache: true,
        );

        print(
          '📶 DataProvider: Restored ${networks.length} data networks from cache',
        );
      }

      // Restore data card networks from cache (assuming you have a similar cache method)
      final cachedCardNetworks =
          await CacheService().getCachedDataCardNetworks();
      if (cachedCardNetworks != null && cachedCardNetworks.isNotEmpty) {
        print(
          '📶 DataProvider: Found cached data card networks, restoring state',
        );

        final cardNetworks =
            cachedCardNetworks
                .map((network) => DataNetworkModel.fromJson(network))
                .toList();

        // Get the cache timestamp if available
        final cardCacheTimestamp = await CacheService().getCacheTimestamp(
          'data_card_networks',
        );

        state = state.copyWith(
          dataCardNetworks: cardNetworks,
          dataCardLastUpdated: cardCacheTimestamp ?? DateTime.now(),
          isDataCardLoadedFromCache: true,
        );

        print(
          '📶 DataProvider: Restored ${cardNetworks.length} data card networks from cache',
        );
      }
    } catch (e) {
      print('📶 DataProvider: Error restoring from cache: $e');
    }
  }

  /// Get data networks with smart caching
  Future<void> getDataNetworksWithCache({
    Duration cacheValidity = const Duration(minutes: 5),
    bool showRefreshingIndicator = false,
  }) async {
    print('📶 DataProvider: Getting data networks with cache check');

    // Let the constructor-time cache restore land in state first. Without
    // this, a screen calling this from initState can run before the
    // SharedPreferences read resolves, see dataNetworks == null, and fall
    // through to a full loading spinner even though valid cache exists.
    await _ready;

    // If we have valid cached data, return early
    if (state.isDataNetworksCacheValid(cacheValidity: cacheValidity)) {
      print('📶 DataProvider: Using valid cached data networks');
      return;
    }

    // If we have data but it's stale, show refreshing indicator instead of loading
    if (state.hasDataNetworks && showRefreshingIndicator) {
      state = state.copyWith(isRefreshing: true, error: null);
    } else {
      state = state.copyWith(isLoading: true, error: null);
    }

    await _fetchDataNetworksFromNetwork();
  }

  /// Get data networks (force refresh from network)
  Future<void> getDataNetworks({bool forceRefresh = false}) async {
    print(
      '📶 DataProvider: Getting data networks ${forceRefresh ? '(force refresh)' : ''}',
    );

    // If force refresh, always fetch from network — but still let any
    // cached data land in state first, so the screen has something to show
    // while the refresh runs instead of a blank/spinner state.
    if (forceRefresh) {
      await _ready;
      state = state.copyWith(
        isRefreshing: state.hasDataNetworks,
        isLoading: !state.hasDataNetworks,
        error: null,
      );
      await _fetchDataNetworksFromNetwork();
      return;
    }

    // Otherwise use smart caching
    await getDataNetworksWithCache();
  }

  /// Fetch data networks from network
  Future<void> _fetchDataNetworksFromNetwork() async {
    try {
      print('📶 DataProvider: Fetching data networks from network');
      final response = await _dataRepository.getDataNetworks();

      if (response.success && response.data != null) {
        print(
          '📶 DataProvider: Successfully got ${response.data!.data!.length} data networks',
        );

        // Cache the data networks (this also stamps the cache timestamp —
        // no need to set it again separately, which was previously writing
        // it twice with two slightly different times)
        final networksToCache =
            response.data!.data!.map((network) => network.toJson()).toList();
        await CacheService().cacheDataNetworks(networksToCache);

        state = state.copyWith(
          isLoading: false,
          isRefreshing: false,
          dataNetworks: response.data!.data,
          lastUpdated: DateTime.now(),
          isLoadedFromCache: false,
          error: null,
        );
      } else {
        print(
          '📶 DataProvider: Failed to get data networks - ${response.message}',
        );

        // If we have cached data, keep it and just show error briefly
        if (state.hasDataNetworks) {
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            // Don't clear existing data, just show error temporarily
          );

          // Clear error after 3 seconds to not persist the error state
          Future.delayed(const Duration(seconds: 3), () {
            if (mounted) {
              clearError();
            }
          });
        } else {
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            error: response.message ?? 'Failed to get data networks',
          );
        }
      }
    } catch (e) {
      print('📶 DataProvider: Error getting data networks - $e');

      // Same logic as above - preserve existing data if available
      if (state.hasDataNetworks) {
        state = state.copyWith(isLoading: false, isRefreshing: false);

        // Clear error after 3 seconds
        Future.delayed(const Duration(seconds: 3), () {
          if (mounted) {
            clearError();
          }
        });
      } else {
        state = state.copyWith(
          isLoading: false,
          isRefreshing: false,
          error: e.toString(),
        );
      }
    }
  }

  /// Get data card networks with smart caching
  Future<void> getDataCardNetworksWithCache({
    Duration cacheValidity = const Duration(minutes: 5),
    bool showRefreshingIndicator = false,
  }) async {
    print('📶 DataProvider: Getting data card networks with cache check');

    // See getDataNetworksWithCache() — wait for the constructor's cache
    // restore so we don't race ahead of it into a spinner.
    await _ready;

    // If we have valid cached data, return early
    if (state.isDataCardNetworksCacheValid(cacheValidity: cacheValidity)) {
      print('📶 DataProvider: Using valid cached data card networks');
      return;
    }

    // If we have data but it's stale, show refreshing indicator instead of loading
    if (state.hasDataCardNetworks && showRefreshingIndicator) {
      state = state.copyWith(isRefreshing: true, error: null);
    } else {
      state = state.copyWith(isLoading: true, error: null);
    }

    await _fetchDataCardNetworksFromNetwork();
  }

  /// Get data card networks (force refresh from network)
  Future<void> getDataCardNetworks({bool forceRefresh = false}) async {
    print(
      '📶 DataProvider: Getting data card networks ${forceRefresh ? '(force refresh)' : ''}',
    );

    // If force refresh, always fetch from network — but still let cached
    // data land in state first so there's something on screen meanwhile.
    if (forceRefresh) {
      await _ready;
      state = state.copyWith(
        isRefreshing: state.hasDataCardNetworks,
        isLoading: !state.hasDataCardNetworks,
        error: null,
      );
      await _fetchDataCardNetworksFromNetwork();
      return;
    }

    // Otherwise use smart caching
    await getDataCardNetworksWithCache();
  }

  /// Fetch data card networks from network
  Future<void> _fetchDataCardNetworksFromNetwork() async {
    try {
      print('📶 DataProvider: Fetching data card networks from network');
      final response = await _dataRepository.getDataCardNetworks();

      if (response.success && response.data != null) {
        print(
          '📶 DataProvider: Successfully got ${response.data!.data!.length} data card networks',
        );

        // Cache the data card networks (this also stamps the timestamp —
        // see the note in _fetchDataNetworksFromNetwork above)
        final networksToCache =
            response.data!.data!.map((network) => network.toJson()).toList();
        await CacheService().cacheDataCardNetworks(networksToCache);

        state = state.copyWith(
          isLoading: false,
          isRefreshing: false,
          dataCardNetworks: response.data!.data,
          dataCardLastUpdated: DateTime.now(),
          isDataCardLoadedFromCache: false,
          error: null,
        );
      } else {
        print(
          '📶 DataProvider: Failed to get data card networks - ${response.message}',
        );

        // If we have cached data, keep it and just show error briefly
        if (state.hasDataCardNetworks) {
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            // Don't clear existing data, just show error temporarily
          );

          // Clear error after 3 seconds to not persist the error state
          Future.delayed(const Duration(seconds: 3), () {
            if (mounted) {
              clearError();
            }
          });
        } else {
          state = state.copyWith(
            isLoading: false,
            isRefreshing: false,
            error: response.message ?? 'Failed to get data card networks',
          );
        }
      }
    } catch (e) {
      print('📶 DataProvider: Error getting data card networks - $e');

      // Same logic as above - preserve existing data if available
      if (state.hasDataCardNetworks) {
        state = state.copyWith(isLoading: false, isRefreshing: false);

        // Clear error after 3 seconds
        Future.delayed(const Duration(seconds: 3), () {
          if (mounted) {
            clearError();
          }
        });
      } else {
        state = state.copyWith(
          isLoading: false,
          isRefreshing: false,
          error: e.toString(),
        );
      }
    }
  }

  /// Buy data
  ///
  /// Note: deliberately does NOT touch `state.isLoading` / `state.error`.
  /// Those fields drive the screen-level "fetching networks" loading/error
  /// views in BuyDataScreen. Writing to them here used to cause two problems
  /// during a purchase: (1) the screen's own network-loading spinner would
  /// pop up underneath the purchase's loading dialog, showing two loaders
  /// at once, and (2) a failed purchase would set `state.error`, which made
  /// the whole screen fall back to the "Retry" (re-fetch networks) error
  /// view instead of just showing a snackbar on the purchase screen.
  /// Callers should rely solely on the returned ApiResponse to know the
  /// purchase outcome.
  Future<ApiResponse<Map<String, dynamic>>> buyData({
    required String phoneNumber,
    required String networkId,
    required String planId,
    required String dataTypeId,
    required String transactionPin,
  }) async {
    try {
      print('📶 DataProvider: Buying data');
      final request = BuyDataRequest(
        phoneNumber: phoneNumber,
        networkId: networkId,
        planId: planId,
        dataTypeId: dataTypeId,
        transactionPin: transactionPin,
      );

      final response = await _dataRepository.buyData(request);

      if (response.success) {
        print('📶 DataProvider: Data purchase successful');
        state = state.copyWith(lastTransaction: response.data);
        _refreshBalances();
      } else {
        print('📶 DataProvider: Data purchase failed - ${response.message}');
      }

      return response;
    } catch (e) {
      print('📶 DataProvider: Error buying data - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Buy data card
  ///
  /// See the note on [buyData] above — this intentionally avoids touching
  /// `state.isLoading` / `state.error` for the same reasons.
  Future<ApiResponse<Map<String, dynamic>>> buyDataCard({
    required String name,
    required String quantity,
    required String networkId,
    required String planId,
    required String transactionPin,
  }) async {
    try {
      print('📶 DataProvider: Buying data card');
      final request = BuyDataCardRequest(
        name: name,
        quantity: quantity,
        networkId: networkId,
        planId: planId,
        transactionPin: transactionPin,
      );

      final response = await _dataRepository.buyDataCard(request);

      if (response.success) {
        print('📶 DataProvider: Data card purchase successful');
        state = state.copyWith(lastTransaction: response.data);
        _refreshBalances();
      } else {
        print(
          '📶 DataProvider: Data card purchase failed - ${response.message}',
        );
      }

      return response;
    } catch (e) {
      print('📶 DataProvider: Error buying data card - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Refresh data networks (force refresh with refreshing indicator)
  Future<void> refreshDataNetworks() async {
    print('📶 DataProvider: Refreshing data networks');
    await getDataNetworks(forceRefresh: true);
  }

  /// Refresh data card networks (force refresh with refreshing indicator)
  Future<void> refreshDataCardNetworks() async {
    print('📶 DataProvider: Refreshing data card networks');
    await getDataCardNetworks(forceRefresh: true);
  }

  /// Refresh all data (both data networks and data card networks)
  Future<void> refreshAll() async {
    print('📶 DataProvider: Refreshing all data');
    await Future.wait([
      getDataNetworks(forceRefresh: true),
      getDataCardNetworks(forceRefresh: true),
    ]);
  }

  /// Clear data networks cache and reload
  Future<void> clearDataNetworksCacheAndReload() async {
    print('📶 DataProvider: Clearing data networks cache and reloading');
    await CacheService().clearDataNetworks();
    state = state.copyWith(
      dataNetworks: null,
      lastUpdated: null,
      isLoadedFromCache: false,
    );
    await getDataNetworks(forceRefresh: true);
  }

  /// Clear data card networks cache and reload
  Future<void> clearDataCardNetworksCacheAndReload() async {
    print('📶 DataProvider: Clearing data card networks cache and reloading');
    await CacheService().clearDataCardNetworks();
    state = state.copyWith(
      dataCardNetworks: null,
      dataCardLastUpdated: null,
      isDataCardLoadedFromCache: false,
    );
    await getDataCardNetworks(forceRefresh: true);
  }

  /// Clear all cache and reload
  Future<void> clearAllCacheAndReload() async {
    print('📶 DataProvider: Clearing all cache and reloading');
    await Future.wait([
      CacheService().clearDataNetworks(),
      CacheService().clearDataCardNetworks(),
    ]);
    state = state.copyWith(
      dataNetworks: null,
      dataCardNetworks: null,
      lastUpdated: null,
      dataCardLastUpdated: null,
      isLoadedFromCache: false,
      isDataCardLoadedFromCache: false,
    );
    await refreshAll();
  }

  /// Clear error
  void clearError() {
    if (state.error != null) {
      state = state.copyWith(error: null);
    }
  }

  /// Clear last transaction
  void clearLastTransaction() {
    state = state.copyWith(lastTransaction: null);
  }
}

// Providers
final dataRepositoryProvider = Provider<DataRepository>((ref) {
  return DataRepository(ApiService.instance);
});

final dataNotifierProvider = StateNotifierProvider<DataNotifier, DataState>((
  ref,
) {
  final dataRepository = ref.watch(dataRepositoryProvider);
  return DataNotifier(dataRepository, ref);
});

// Convenience providers
final dataNetworksProvider = Provider<List<DataNetworkWithTypesModel>?>((ref) {
  return ref.watch(dataNotifierProvider).dataNetworks;
});

final dataCardNetworksProvider = Provider<List<DataNetworkModel>?>((ref) {
  return ref.watch(dataNotifierProvider).dataCardNetworks;
});

final dataLoadingProvider = Provider<bool>((ref) {
  return ref.watch(dataNotifierProvider).isLoading;
});

final dataRefreshingProvider = Provider<bool>((ref) {
  return ref.watch(dataNotifierProvider).isRefreshing;
});

final dataErrorProvider = Provider<String?>((ref) {
  return ref.watch(dataNotifierProvider).error;
});

final dataLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(dataNotifierProvider).lastTransaction;
});

final dataLastUpdatedProvider = Provider<DateTime?>((ref) {
  return ref.watch(dataNotifierProvider).lastUpdated;
});

final dataCardLastUpdatedProvider = Provider<DateTime?>((ref) {
  return ref.watch(dataNotifierProvider).dataCardLastUpdated;
});

final dataCacheStatusProvider = Provider<bool>((ref) {
  return ref.watch(dataNotifierProvider).isLoadedFromCache;
});

final dataCardCacheStatusProvider = Provider<bool>((ref) {
  return ref.watch(dataNotifierProvider).isDataCardLoadedFromCache;
});