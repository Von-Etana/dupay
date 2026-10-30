import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/cable_repository.dart';
import '../models/cable/cable_model.dart';
import '../models/cable/buy_cable_request.dart';
import '../models/cable/verify_cable_request.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import '../services/cache/cache_service.dart';
import 'auth_provider.dart';

// State class for cable operations
class CableState {
  final bool isLoading;
  final bool isRefreshing;
  final String? error;
  final List<CableProviderModel>? cableProviders;
  final Map<String, dynamic>? lastTransaction;
  final Map<String, dynamic>? verificationResult;
  final DateTime? lastUpdated;
  final bool isLoadedFromCache;

  CableState({
    this.isLoading = false,
    this.isRefreshing = false,
    this.error,
    this.cableProviders,
    this.lastTransaction,
    this.verificationResult,
    this.lastUpdated,
    this.isLoadedFromCache = false,
  });

  CableState copyWith({
    bool? isLoading,
    bool? isRefreshing,
    String? error,
    List<CableProviderModel>? cableProviders,
    Map<String, dynamic>? lastTransaction,
    Map<String, dynamic>? verificationResult,
    DateTime? lastUpdated,
    bool? isLoadedFromCache,
  }) {
    return CableState(
      isLoading: isLoading ?? this.isLoading,
      isRefreshing: isRefreshing ?? this.isRefreshing,
      error: error ?? this.error,
      cableProviders: cableProviders ?? this.cableProviders,
      lastTransaction: lastTransaction ?? this.lastTransaction,
      verificationResult: verificationResult ?? this.verificationResult,
      lastUpdated: lastUpdated ?? this.lastUpdated,
      isLoadedFromCache: isLoadedFromCache ?? this.isLoadedFromCache,
    );
  }

  /// Check if cache is still valid (5 minutes by default)
  bool isCacheValid({Duration cacheValidity = const Duration(minutes: 5)}) {
    if (lastUpdated == null || cableProviders == null) return false;
    return DateTime.now().difference(lastUpdated!) < cacheValidity;
  }

  /// Check if data exists (either from cache or network)
  bool get hasData => cableProviders != null && cableProviders!.isNotEmpty;
}

// StateNotifier for cable operations
class CableNotifier extends StateNotifier<CableState> {
  final CableRepository _cableRepository;
  final Ref _ref;

  CableNotifier(this._cableRepository, this._ref) : super(CableState()) {
    // Initialize data from cache when provider is created
    _initializeFromCache();
  }

  /// Refresh wallet balances into the auth state after a purchase so the home
  /// screen updates on its own. Fire-and-forget — the money has already moved.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  /// Initialize data from cache on startup
  Future<void> _initializeFromCache() async {
    print('📺 CableProvider: Initializing data from cache...');

    try {
      final cachedNetworks = await CacheService().getCachedCableNetworks();
      if (cachedNetworks != null && cachedNetworks.isNotEmpty) {
        print('📺 CableProvider: Found cached cable networks, restoring state');

        final networks =
            cachedNetworks
                .map((network) => CableProviderModel.fromJson(network))
                .toList();

        // Get the cache timestamp if available
        final cacheTimestamp = await CacheService().getCacheTimestamp(
          'cable_networks',
        );

        state = state.copyWith(
          cableProviders: networks,
          lastUpdated: cacheTimestamp ?? DateTime.now(),
          isLoadedFromCache: true,
        );

        print(
          '📺 CableProvider: Restored ${networks.length} cable networks from cache',
        );
      }
    } catch (e) {
      print('📺 CableProvider: Error restoring from cache: $e');
    }
  }

  /// Get cables with smart caching
  Future<void> getCablesWithCache({
    Duration cacheValidity = const Duration(minutes: 5),
    bool showRefreshingIndicator = false,
  }) async {
    print('📺 CableProvider: Getting cables with cache check');

    // If we have valid cached data, return early
    if (state.isCacheValid(cacheValidity: cacheValidity)) {
      print('📺 CableProvider: Using valid cached data');
      return;
    }

    // If we have data but it's stale, show refreshing indicator instead of loading
    if (state.hasData && showRefreshingIndicator) {
      state = state.copyWith(isRefreshing: true, error: null);
    } else {
      state = state.copyWith(isLoading: true, error: null);
    }

    await _fetchCablesFromNetwork();
  }

  /// Get cables (force refresh from network)
  Future<void> getCables({bool forceRefresh = false}) async {
    print(
      '📺 CableProvider: Getting cables ${forceRefresh ? '(force refresh)' : ''}',
    );

    // If force refresh, always fetch from network
    if (forceRefresh) {
      state = state.copyWith(isRefreshing: true, error: null);
      await _fetchCablesFromNetwork();
      return;
    }

    // Otherwise use smart caching
    await getCablesWithCache();
  }

  /// Fetch cables from network
  Future<void> _fetchCablesFromNetwork() async {
    try {
      print('📺 CableProvider: Fetching cables from network');
      final response = await _cableRepository.getCables();

      if (response.success && response.data != null) {
        print(
          '📺 CableProvider: Successfully got ${response.data!.data!.length} cable providers',
        );

        // Cache the cable networks with timestamp
        final networksToCache =
            response.data!.data!.map((provider) => provider.toJson()).toList();
        await CacheService().cacheCableNetworks(networksToCache);
        await CacheService().setCacheTimestamp(
          'cable_networks',
          DateTime.now(),
        );

        state = state.copyWith(
          isLoading: false,
          isRefreshing: false,
          cableProviders: response.data!.data,
          lastUpdated: DateTime.now(),
          isLoadedFromCache: false,
          error: null,
        );
      } else {
        print('📺 CableProvider: Failed to get cables - ${response.message}');

        // If we have cached data, keep it and just show error briefly
        if (state.hasData) {
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
            error: response.message ?? 'Failed to get cables',
          );
        }
      }
    } catch (e) {
      print('📺 CableProvider: Error getting cables - $e');

      // Same logic as above - preserve existing data if available
      if (state.hasData) {
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

  /// Buy cable
  Future<ApiResponse<Map<String, dynamic>>> buyCable({
    required String phoneNumber,
    required String planId,
    required String cableId,
    required String decoderNumber,
    String? transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('📺 CableProvider: Buying cable');
      final request = BuyCableRequest(
        phoneNumber: phoneNumber,
        planId: planId,
        cableId: cableId,
        decoderNumber: decoderNumber,
        transactionPin: transactionPin,
      );

      final response = await _cableRepository.buyCable(request);

      if (response.success) {
        print('📺 CableProvider: Cable purchase successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
        _refreshBalances();
      } else {
        print('📺 CableProvider: Cable purchase failed - ${response.message}');
        state = state.copyWith(isLoading: false, error: response.message);
      }

      return response;
    } catch (e) {
      print('📺 CableProvider: Error buying cable - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Verify cable number
  Future<ApiResponse<Map<String, dynamic>>> verifyCableNumber({
    required String planId,
    required String cableId,
    required String decoderNumber,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('📺 CableProvider: Verifying cable number');
      final request = VerifyCableRequest(
        planId: planId,
        cableId: cableId,
        decoderNumber: decoderNumber,
      );

      final response = await _cableRepository.verifyCableNumber(request);

      if (response.success) {
        print('📺 CableProvider: Cable verification successful');
        state = state.copyWith(
          isLoading: false,
          verificationResult: response.data,
          error: null,
        );
      } else {
        print(
          '📺 CableProvider: Cable verification failed - ${response.message}',
        );
        state = state.copyWith(isLoading: false, error: response.message);
      }

      return response;
    } catch (e) {
      print('📺 CableProvider: Error verifying cable number - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Refresh data (force refresh with refreshing indicator)
  Future<void> refresh() async {
    print('📺 CableProvider: Refreshing cable data');
    await getCables(forceRefresh: true);
  }

  /// Clear cache and reload
  Future<void> clearCacheAndReload() async {
    print('📺 CableProvider: Clearing cache and reloading');
    await CacheService().clearCableNetworks();
    state = state.copyWith(
      cableProviders: null,
      lastUpdated: null,
      isLoadedFromCache: false,
    );
    await getCables(forceRefresh: true);
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

  /// Clear verification result
  void clearVerificationResult() {
    state = state.copyWith(verificationResult: null);
  }
}

// Providers
final cableRepositoryProvider = Provider<CableRepository>((ref) {
  return CableRepository(ApiService.instance);
});

final cableNotifierProvider = StateNotifierProvider<CableNotifier, CableState>((
  ref,
) {
  final cableRepository = ref.watch(cableRepositoryProvider);
  return CableNotifier(cableRepository, ref);
});

// Convenience providers
final cableProvidersProvider = Provider<List<CableProviderModel>?>((ref) {
  return ref.watch(cableNotifierProvider).cableProviders;
});

final cableLoadingProvider = Provider<bool>((ref) {
  return ref.watch(cableNotifierProvider).isLoading;
});

final cableRefreshingProvider = Provider<bool>((ref) {
  return ref.watch(cableNotifierProvider).isRefreshing;
});

final cableErrorProvider = Provider<String?>((ref) {
  return ref.watch(cableNotifierProvider).error;
});

final cableLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(cableNotifierProvider).lastTransaction;
});

final cableVerificationResultProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(cableNotifierProvider).verificationResult;
});

final cableLastUpdatedProvider = Provider<DateTime?>((ref) {
  return ref.watch(cableNotifierProvider).lastUpdated;
});

final cableCacheStatusProvider = Provider<bool>((ref) {
  return ref.watch(cableNotifierProvider).isLoadedFromCache;
});