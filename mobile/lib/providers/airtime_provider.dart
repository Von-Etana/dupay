// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import '../repositories/airtime_repository.dart';
// import '../models/airtime/airtime_networks_response.dart';
// import '../models/airtime/airtime_to_cash_networks_response.dart';
// import '../models/airtime/airtime_network_model.dart';

// import '../models/airtime/buy_airtime_request.dart';
// import '../models/airtime/convert_airtime_request.dart';
// import '../models/base/api_response.dart';
// import '../core/network/api_service.dart';
// import '../services/cache/cache_service.dart';

// // State class for airtime operations
// class AirtimeState {
//   final bool isLoading;
//   final String? error;
//   final List<AirtimeNetworkModel>? networks;
//   final List<AirtimeToCashNetworkModel>? airtimeToCashNetworks;
//   final Map<String, dynamic>? lastTransaction;

//   AirtimeState({
//     this.isLoading = false,
//     this.error,
//     this.networks,
//     this.airtimeToCashNetworks,
//     this.lastTransaction,
//   });

//   AirtimeState copyWith({
//     bool? isLoading,
//     String? error,
//     List<AirtimeNetworkModel>? networks,
//     List<AirtimeToCashNetworkModel>? airtimeToCashNetworks,
//     Map<String, dynamic>? lastTransaction,
//   }) {
//     return AirtimeState(
//       isLoading: isLoading ?? this.isLoading,
//       error: error ?? this.error,
//       networks: networks ?? this.networks,
//       airtimeToCashNetworks:
//           airtimeToCashNetworks ?? this.airtimeToCashNetworks,
//       lastTransaction: lastTransaction ?? this.lastTransaction,
//     );
//   }
// }

// // StateNotifier for airtime operations
// class AirtimeNotifier extends StateNotifier<AirtimeState> {
//   final AirtimeRepository _airtimeRepository;

//   AirtimeNotifier(this._airtimeRepository) : super(AirtimeState()) {
//     _initializeData();
//   }

//   Future<void> _initializeData() async {
//     print('📱 AirtimeProvider: Initializing airtime state...');

//     // Try to restore from cache first
//     final cachedNetworks = await CacheService().getCachedAirtimeNetworks();
//     if (cachedNetworks != null) {
//       print(
//         '📱 AirtimeProvider: Found cached airtime networks, restoring state',
//       );
//       try {
//         final networks =
//             cachedNetworks
//                 .map((network) => AirtimeNetworkModel.fromJson(network))
//                 .toList();
//         state = state.copyWith(networks: networks);
//         print(
//           '📱 AirtimeProvider: Restored ${networks.length} airtime networks from cache',
//         );
//       } catch (e) {
//         print('📱 AirtimeProvider: Error restoring from cache: $e');
//       }
//     }
//   }

//   /// Get airtime networks
//   Future<void> getAirtimeNetworks() async {
//     state = state.copyWith(isLoading: true, error: null);

//     try {
//       print('📱 AirtimeProvider: Getting airtime networks');
//       final response = await _airtimeRepository.getAirtimeNetworks();

//       if (response.success && response.data != null) {
//         print(
//           '📱 AirtimeProvider: Successfully got ${response.data!.data?.length} networks',
//         );

//         // Cache the networks
//         try {
//           final networksData =
//               response.data!.data?.map((network) => network.toJson()).toList();
//           if (networksData != null) {
//             await CacheService().cacheAirtimeNetworks(networksData);
//             print('📱 AirtimeProvider: Cached airtime networks');
//           }
//         } catch (e) {
//           print('📱 AirtimeProvider: Error caching networks: $e');
//         }

//         state = state.copyWith(
//           isLoading: false,
//           networks: response.data!.data,
//           error: null,
//         );
//       } else {
//         print(
//           '📱 AirtimeProvider: Failed to get networks - ${response.message}',
//         );
//         state = state.copyWith(
//           isLoading: false,
//           error: response.message ?? 'Failed to get airtime networks',
//         );
//       }
//     } catch (e) {
//       print('📱 AirtimeProvider: Error getting networks - $e');
//       state = state.copyWith(isLoading: false, error: e.toString());
//     }
//   }

//   /// Get airtime to cash networks
//   Future<void> getAirtimeToCashNetworks() async {
//     state = state.copyWith(isLoading: true, error: null);

//     try {
//       print('📱 AirtimeProvider: Getting airtime to cash networks');
//       final response = await _airtimeRepository.getAirtimeToCashNetworks();

//       if (response.success && response.data != null) {
//         print(
//           '📱 AirtimeProvider: Successfully got ${response.data!.data?.length} airtime to cash networks',
//         );
//         state = state.copyWith(
//           isLoading: false,
//           airtimeToCashNetworks: response.data!.data,
//           error: null,
//         );
//       } else {
//         print(
//           '📱 AirtimeProvider: Failed to get airtime to cash networks - ${response.message}',
//         );
//         state = state.copyWith(
//           isLoading: false,
//           error: response.message ?? 'Failed to get airtime to cash networks',
//         );
//       }
//     } catch (e) {
//       print('📱 AirtimeProvider: Error getting airtime to cash networks - $e');
//       state = state.copyWith(isLoading: false, error: e.toString());
//     }
//   }

//   /// Buy airtime
//   Future<ApiResponse<Map<String, dynamic>>> buyAirtime({
//     required String phoneNumber,
//     required String networkId,
//     required String typeId,
//     required String amount,
//     required String transactionPin,
//   }) async {
//     state = state.copyWith(isLoading: true, error: null);

//     try {
//       print('📱 AirtimeProvider: Buying airtime');
//       final request = BuyAirtimeRequest(
//         phoneNumber: phoneNumber,
//         networkId: networkId,
//         typeId: typeId,
//         amount: amount,
//         transactionPin: transactionPin,
//       );

//       final response = await _airtimeRepository.buyAirtime(request);

//       if (response.success) {
//         print('📱 AirtimeProvider: Airtime purchase successful');
//         state = state.copyWith(
//           isLoading: false,
//           lastTransaction: response.data,
//           error: null,
//         );
//       } else {
//         print(
//           '📱 AirtimeProvider: Airtime purchase failed - ${response.message}',
//         );
//         state = state.copyWith(
//           isLoading: false,
//           error: response.message ?? 'Failed to buy airtime',
//         );
//       }

//       return response;
//     } catch (e) {
//       print('📱 AirtimeProvider: Error buying airtime - $e');
//       state = state.copyWith(isLoading: false, error: e.toString());
//       return ApiResponse<Map<String, dynamic>>(
//         success: false,
//         message: e.toString(),
//       );
//     }
//   }

//   /// Convert airtime to cash
//   Future<ApiResponse<Map<String, dynamic>>> convertAirtimeToCash({
//     required String network,
//     required String mode,
//     required String amount,
//     required String airtimeDetails,
//   }) async {
//     state = state.copyWith(isLoading: true, error: null);

//     try {
//       print('📱 AirtimeProvider: Converting airtime to cash');
//       final request = ConvertAirtimeRequest(
//         network: network,
//         mode: mode,
//         amount: amount,
//         airtimeDetails: airtimeDetails,
//       );

//       final response = await _airtimeRepository.convertAirtimeToCash(request);

//       if (response.success) {
//         print('📱 AirtimeProvider: Airtime to cash conversion successful');
//         state = state.copyWith(
//           isLoading: false,
//           lastTransaction: response.data,
//           error: null,
//         );
//       } else {
//         print(
//           '📱 AirtimeProvider: Airtime to cash conversion failed - ${response.message}',
//         );
//         state = state.copyWith(
//           isLoading: false,
//           error: response.message ?? 'Failed to convert airtime to cash',
//         );
//       }

//       return response;
//     } catch (e) {
//       print('📱 AirtimeProvider: Error converting airtime to cash - $e');
//       state = state.copyWith(isLoading: false, error: e.toString());
//       return ApiResponse<Map<String, dynamic>>(
//         success: false,
//         message: e.toString(),
//       );
//     }
//   }

//   /// Clear error
//   void clearError() {
//     state = state.copyWith(error: null);
//   }

//   /// Clear last transaction
//   void clearLastTransaction() {
//     state = state.copyWith(lastTransaction: null);
//   }
// }

// // Providers
// final airtimeRepositoryProvider = Provider<AirtimeRepository>((ref) {
//   return AirtimeRepository(ApiService.instance);
// });

// final airtimeNotifierProvider =
//     StateNotifierProvider<AirtimeNotifier, AirtimeState>((ref) {
//       final airtimeRepository = ref.watch(airtimeRepositoryProvider);
//       return AirtimeNotifier(airtimeRepository);
//     });

// // Convenience providers
// final airtimeNetworksProvider = Provider<List<AirtimeNetworkModel>?>((ref) {
//   return ref.watch(airtimeNotifierProvider).networks;
// });

// final airtimeToCashNetworksProvider =
//     Provider<List<AirtimeToCashNetworkModel>?>((ref) {
//       return ref.watch(airtimeNotifierProvider).airtimeToCashNetworks;
//     });

// final airtimeLoadingProvider = Provider<bool>((ref) {
//   return ref.watch(airtimeNotifierProvider).isLoading;
// });

// final airtimeErrorProvider = Provider<String?>((ref) {
//   return ref.watch(airtimeNotifierProvider).error;
// });

// final airtimeLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
//   return ref.watch(airtimeNotifierProvider).lastTransaction;
// });

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repositories/airtime_repository.dart';
import '../models/airtime/airtime_networks_response.dart';
import '../models/airtime/airtime_to_cash_networks_response.dart';
import '../models/airtime/airtime_network_model.dart';

import '../models/airtime/buy_airtime_request.dart';
import '../models/airtime/convert_airtime_request.dart';
import '../models/base/api_response.dart';
import '../core/network/api_service.dart';
import '../services/cache/cache_service.dart';
import 'auth_provider.dart';

// State class for airtime operations
class AirtimeState {
  final bool isLoading;
  final String? error;
  final List<AirtimeNetworkModel>? networks;
  final List<AirtimeToCashNetworkModel>? airtimeToCashNetworks;
  final Map<String, dynamic>? lastTransaction;

  AirtimeState({
    this.isLoading = false,
    this.error,
    this.networks,
    this.airtimeToCashNetworks,
    this.lastTransaction,
  });

  AirtimeState copyWith({
    bool? isLoading,
    String? error,
    List<AirtimeNetworkModel>? networks,
    List<AirtimeToCashNetworkModel>? airtimeToCashNetworks,
    Map<String, dynamic>? lastTransaction,
  }) {
    return AirtimeState(
      isLoading: isLoading ?? this.isLoading,
      error: error ?? this.error,
      networks: networks ?? this.networks,
      airtimeToCashNetworks:
          airtimeToCashNetworks ?? this.airtimeToCashNetworks,
      lastTransaction: lastTransaction ?? this.lastTransaction,
    );
  }
}

// StateNotifier for airtime operations
class AirtimeNotifier extends StateNotifier<AirtimeState> {
  final AirtimeRepository _airtimeRepository;

  // The cache restore below is async (SharedPreferences round-trip), so it
  // doesn't land in `state` the instant this notifier is constructed. A
  // screen that calls getAirtimeNetworks() from initState/postFrameCallback
  // runs essentially in the same tick and can race ahead of it — landing on
  // "networks == null" and triggering a full network-led loading spinner
  // even though valid cache exists on disk a few milliseconds later.
  // Storing the future and awaiting it at the top of getAirtimeNetworks()
  // closes that race without changing when/how the restore itself runs.
  late final Future<void> _ready;

  final Ref _ref;

  AirtimeNotifier(this._airtimeRepository, this._ref) : super(AirtimeState()) {
    _ready = _initializeData();
  }

  /// Refresh wallet balances into the auth state after a transaction so the
  /// home screen updates on its own. Fire-and-forget — the money has already
  /// moved, so a refresh failure must not surface as a failed transaction.
  void _refreshBalances() {
    _ref.read(authNotifierProvider.notifier).refreshBalances().catchError(
      (_) {},
    );
  }

  Future<void> _initializeData() async {
    print('📱 AirtimeProvider: Initializing airtime state...');

    // NOTE: everything here is wrapped so a bad/failed cache read can NEVER
    // leave the `_ready` future in an error state. Previously a throw here
    // permanently broke `await _ready` in getAirtimeNetworks(), leaving the
    // screen stuck on the loading view until the app was reinstalled.
    try {
      // Try to restore from cache first
      final cachedNetworks = await CacheService().getCachedAirtimeNetworks();
      if (cachedNetworks != null) {
        print(
          '📱 AirtimeProvider: Found cached airtime networks, restoring state',
        );
        final networks = cachedNetworks
            .map((network) => AirtimeNetworkModel.fromJson(network))
            .toList();
        state = state.copyWith(networks: networks);
        print(
          '📱 AirtimeProvider: Restored ${networks.length} airtime networks from cache',
        );
      }
    } catch (e) {
      print('📱 AirtimeProvider: Error restoring from cache: $e');
    }
  }

  /// Get airtime networks
  ///
  /// Cache-first: if the constructor-time restore already found a cached
  /// list (or finishes in the next few ms — we wait for it), it's already
  /// on screen, so this just refreshes silently in the background. The
  /// loading spinner only appears when there's genuinely nothing to show.
  Future<void> getAirtimeNetworks({bool forceRefresh = false}) async {
    try {
      await _ready;
    } catch (_) {
      // A failed cache restore must not stop us from hitting the network.
    }

    final hasNetworks = state.networks != null && state.networks!.isNotEmpty;

    // Cache-first with a 7-day TTL: if we already have networks and the cache
    // is still fresh (<7 days), skip the network entirely. The refresh button
    // passes forceRefresh:true to bypass this.
    if (!forceRefresh &&
        hasNetworks &&
        await CacheService().isAirtimeNetworksFresh()) {
      print('📱 AirtimeProvider: Cache fresh (<7d) — skipping network fetch');
      return;
    }

    state = state.copyWith(isLoading: !hasNetworks, error: null);

    try {
      print('📱 AirtimeProvider: Getting airtime networks');
      final response = await _airtimeRepository.getAirtimeNetworks();

      if (response.success && response.data != null) {
        print(
          '📱 AirtimeProvider: Successfully got ${response.data!.data?.length} networks',
        );

        // Cache the networks
        try {
          final networksData =
              response.data!.data?.map((network) => network.toJson()).toList();
          if (networksData != null) {
            await CacheService().cacheAirtimeNetworks(networksData);
            print('📱 AirtimeProvider: Cached airtime networks');
          }
        } catch (e) {
          print('📱 AirtimeProvider: Error caching networks: $e');
        }

        state = state.copyWith(
          isLoading: false,
          networks: response.data!.data,
          error: null,
        );
      } else {
        print(
          '📱 AirtimeProvider: Failed to get networks - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to get airtime networks',
        );
      }
    } catch (e) {
      print('📱 AirtimeProvider: Error getting networks - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  /// Get airtime to cash networks
  Future<void> getAirtimeToCashNetworks() async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('📱 AirtimeProvider: Getting airtime to cash networks');
      final response = await _airtimeRepository.getAirtimeToCashNetworks();

      if (response.success && response.data != null) {
        print(
          '📱 AirtimeProvider: Successfully got ${response.data!.data?.length} airtime to cash networks',
        );
        state = state.copyWith(
          isLoading: false,
          airtimeToCashNetworks: response.data!.data,
          error: null,
        );
      } else {
        print(
          '📱 AirtimeProvider: Failed to get airtime to cash networks - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to get airtime to cash networks',
        );
      }
    } catch (e) {
      print('📱 AirtimeProvider: Error getting airtime to cash networks - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  /// Buy airtime
  Future<ApiResponse<Map<String, dynamic>>> buyAirtime({
    required String phoneNumber,
    required String networkId,
    required String typeId,
    required String amount,
    required String transactionPin,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('📱 AirtimeProvider: Buying airtime');
      final request = BuyAirtimeRequest(
        phoneNumber: phoneNumber,
        networkId: networkId,
        typeId: typeId,
        amount: amount,
        transactionPin: transactionPin,
      );

      final response = await _airtimeRepository.buyAirtime(request);

      if (response.success) {
        print('📱 AirtimeProvider: Airtime purchase successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
        _refreshBalances();
      } else {
        print(
          '📱 AirtimeProvider: Airtime purchase failed - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to buy airtime',
        );
      }

      return response;
    } catch (e) {
      print('📱 AirtimeProvider: Error buying airtime - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Convert airtime to cash
  Future<ApiResponse<Map<String, dynamic>>> convertAirtimeToCash({
    required String network,
    required String mode,
    required String amount,
    required String airtimeDetails,
  }) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      print('📱 AirtimeProvider: Converting airtime to cash');
      final request = ConvertAirtimeRequest(
        network: network,
        mode: mode,
        amount: amount,
        airtimeDetails: airtimeDetails,
      );

      final response = await _airtimeRepository.convertAirtimeToCash(request);

      if (response.success) {
        print('📱 AirtimeProvider: Airtime to cash conversion successful');
        state = state.copyWith(
          isLoading: false,
          lastTransaction: response.data,
          error: null,
        );
        _refreshBalances();
      } else {
        print(
          '📱 AirtimeProvider: Airtime to cash conversion failed - ${response.message}',
        );
        state = state.copyWith(
          isLoading: false,
          error: response.message ?? 'Failed to convert airtime to cash',
        );
      }

      return response;
    } catch (e) {
      print('📱 AirtimeProvider: Error converting airtime to cash - $e');
      state = state.copyWith(isLoading: false, error: e.toString());
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Clear error
  void clearError() {
    state = state.copyWith(error: null);
  }

  /// Clear last transaction
  void clearLastTransaction() {
    state = state.copyWith(lastTransaction: null);
  }
}

// Providers
final airtimeRepositoryProvider = Provider<AirtimeRepository>((ref) {
  return AirtimeRepository(ApiService.instance);
});

final airtimeNotifierProvider =
    StateNotifierProvider<AirtimeNotifier, AirtimeState>((ref) {
      final airtimeRepository = ref.watch(airtimeRepositoryProvider);
      return AirtimeNotifier(airtimeRepository, ref);
    });

// Convenience providers
final airtimeNetworksProvider = Provider<List<AirtimeNetworkModel>?>((ref) {
  return ref.watch(airtimeNotifierProvider).networks;
});

final airtimeToCashNetworksProvider =
    Provider<List<AirtimeToCashNetworkModel>?>((ref) {
      return ref.watch(airtimeNotifierProvider).airtimeToCashNetworks;
    });

final airtimeLoadingProvider = Provider<bool>((ref) {
  return ref.watch(airtimeNotifierProvider).isLoading;
});

final airtimeErrorProvider = Provider<String?>((ref) {
  return ref.watch(airtimeNotifierProvider).error;
});

final airtimeLastTransactionProvider = Provider<Map<String, dynamic>?>((ref) {
  return ref.watch(airtimeNotifierProvider).lastTransaction;
});