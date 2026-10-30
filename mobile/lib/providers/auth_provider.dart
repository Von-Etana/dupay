// lib/providers/auth_provider.dart
import 'dart:async';

import 'package:dupay/models/auth/check_user_request.dart';
import 'package:dupay/models/auth/get_user_info_response.dart';
import 'package:dupay/models/auth/login_request.dart';
import 'package:dupay/models/auth/register_request.dart';
import 'package:dupay/models/auth/reset_password_request.dart';
import 'package:dupay/models/auth/user_model.dart';
import 'package:dupay/models/auth/user_stats_model.dart';
import 'package:dupay/services/cache/cache_service.dart';
import 'package:dupay/services/notifications/notification_service.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../repositories/auth_repository.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:dupay/services/auth/session_events.dart';
import 'package:dupay/services/storage/secure_storage_service.dart';
import 'package:uuid/uuid.dart';

class AuthState {
  final bool isLoading;
  final bool isAuthenticated;
  final UserModel? user;
  final String? error;
  final String? accessToken;

  AuthState({
    this.isLoading = false,
    this.isAuthenticated = false,
    this.user,
    this.error,
    this.accessToken,
  });

  AuthState copyWith({
    bool? isLoading,
    bool? isAuthenticated,
    UserModel? user,
    String? error,
    String? accessToken,
  }) {
    return AuthState(
      isLoading: isLoading ?? this.isLoading,
      isAuthenticated: isAuthenticated ?? this.isAuthenticated,
      user: user ?? this.user,
      error: error ?? this.error,
      accessToken: accessToken ?? this.accessToken,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  final AuthRepository _authRepository;
  late final StreamSubscription<void> _sessionSubscription;
  int _sessionGeneration = 0;

  AuthNotifier(this._authRepository) : super(AuthState()) {
    _sessionSubscription = SessionEvents.expired.listen((_) {
      _sessionGeneration++;
      if (mounted) state = AuthState();
    });
    _initializeAuth();
  }

  @override
  void dispose() {
    _sessionSubscription.cancel();
    super.dispose();
  }

  Future<void> _initializeAuth() async {
    final generation = _sessionGeneration;
    print('🔐 AuthProvider: Initializing auth state...');
    final isAuthenticated = await _authRepository.isAuthenticated();
    final accessToken = await _authRepository.getStoredAccessToken();
    if (!mounted || generation != _sessionGeneration) return;

    print(
      '🔐 AuthProvider: Stored auth check - isAuthenticated: $isAuthenticated, token: ${accessToken != null ? "exists" : "null"}',
    );

    if (isAuthenticated && accessToken != null) {
      print('🔐 AuthProvider: User is authenticated, updating state');
      state = state.copyWith(isAuthenticated: true, accessToken: accessToken);

      // Try to get user info
      await getUserInfo();
    } else {
      print('🔐 AuthProvider: User is not authenticated');
    }
  }

  Future<bool> login({
    String? email,
    String? phoneNumber,
    required String password,
    String? fcmToken,
  }) async {
    print('🔐 AuthProvider: Starting login process');

    // NOTE: cache is intentionally NOT cleared here. We wait until we know who
    // logged in, then only wipe it if it's a DIFFERENT account (see below).
    // This keeps the cache warm when the SAME user re-authenticates after a
    // token expiry / 401-bounce, instead of nuking valid cached data.

    state = state.copyWith(isLoading: true, error: null);

    try {
      var deviceId = await SecureStorageService.getDeviceId();
      if (deviceId == null || deviceId.isEmpty) {
        deviceId = const Uuid().v4();
        await SecureStorageService.saveDeviceId(deviceId);
      }
      LoginRequest request;

      if (email != null && email.isNotEmpty) {
        print('🔐 AuthProvider: Creating login request with email');
        request = LoginRequest.withEmail(
          email: email,
          password: password,
          fcmToken: fcmToken,
          deviceId: deviceId,
        );
      } else if (phoneNumber != null && phoneNumber.isNotEmpty) {
        print('🔐 AuthProvider: Creating login request with phone');
        request = LoginRequest.withPhone(
          phoneNumber: phoneNumber,
          password: password,
          fcmToken: fcmToken,
          deviceId: deviceId,
        );
      } else {
        throw Exception('Either email or phone number is required');
      }

      print('🔐 AuthProvider: Calling repository login');
      final response = await _authRepository.login(request);
      print(
        '🔐 AuthProvider: Repository response - success: ${response.success}',
      );
      if (response.success && response.data != null) {
        print('🔐 AuthProvider: Login successful, updating state');

        // Identity-aware cache handling: only wipe the cache when the account
        // actually changed. Same user (e.g. re-login after a 401) keeps its
        // cache. A null marker (fresh install / first login after this update)
        // is treated as "different" so we start clean exactly once.
        final newUserId = response.data!.user.id.toString();
        final previousUserId = await CacheService().getLastUserId();

        if (previousUserId != newUserId) {
          await CacheService().clearAllCache();
          print('🔐 AuthProvider: Account changed; cleared cache');
        } else {
          print('🔐 AuthProvider: Reusing current account cache');
        }

        await CacheService().setLastUserId(newUserId);

        state = state.copyWith(
          isLoading: false,
          isAuthenticated: true,
          user: response.data!.user,
          accessToken: response.data!.accessToken,
          error: null,
        );
        return true;
      } else {
        print('🔐 AuthProvider: Login failed');
        state = state.copyWith(isLoading: false, error: response.message);
        return false;
      }
    } catch (e) {
      if (kDebugMode) print('🔐 AuthProvider: Login exception');
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> register(RegisterRequest request) async {
    // Clear all cache when starting a new registration
    await CacheService().clearAllCache();
    print('🔐 AuthProvider: Cleared all cache for new registration');

    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _authRepository.register(request);

      if (response.success && response.data != null) {
        // Record the new account so a later re-login is recognised as the
        // same user and keeps its cache (registration itself starts clean).
        await CacheService().setLastUserId(response.data!.user.id.toString());

        state = state.copyWith(
          isLoading: false,
          isAuthenticated: true,
          user: response.data!.user,
          accessToken: response.data!.accessToken,
          error: null,
        );
        return true;
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
        return false;
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  /// [force] skips the local cache entirely and pulls straight from the API.
  ///
  /// This matters after anything that MOVES MONEY (currency conversion, a
  /// transfer, a bill payment). `getCachedUserProfile()` / `getCachedUserStats()`
  /// return a payload that is still inside its TTL, so without [force] this
  /// method would hand back the pre-transaction balance and the UI would look
  /// like nothing happened. That was the "balance doesn't refresh after
  /// converting" bug: the refresh call was being made, it just never left the
  /// device.
  Future<GetUserInfoResponse?> getUserInfoWithStats({bool force = false}) async {
    final generation = _sessionGeneration;
    try {
      // Check cache first (unless the caller explicitly asked for fresh data).
      final cachedStats =
          force ? null : await CacheService().getCachedUserStats();
      final cachedProfile =
          force ? null : await CacheService().getCachedUserProfile();

      if (cachedStats != null && cachedProfile != null) {
        if (!mounted || generation != _sessionGeneration) return null;
        // Use cached data
        final cachedResponse = GetUserInfoResponse(
          success: true,
          message: 'Cached data',
          stats: UserStatsModel.fromJson(cachedStats),
          user: UserModel.fromJson(cachedProfile),
        );
        state = state.copyWith(user: cachedResponse.user);
        return cachedResponse;
      }

      // Fetch fresh data from API
      final response = await _authRepository.getUserInfo();
      if (!mounted || generation != _sessionGeneration) return null;

      if (response.success && response.data != null) {
        // Cache the new data
        await CacheService().cacheUserStats(response.data!.stats.toJson());
        await CacheService().cacheUserProfile(response.data!.user.toJson());
        if (!mounted || generation != _sessionGeneration) return null;

        state = state.copyWith(user: response.data!.user);
        return response.data;
      }
      return null;
    } catch (e) {
      // Handle error silently for now
      // print('Error getting user info: $e');
      return null;
    }
  }

  Future<void> getUserInfo({bool force = false}) async {
    await getUserInfoWithStats(force: force);
  }

  /// Refresh user data from the server (call after profile updates).
  ///
  /// Pass [force] = true after any balance-changing action so the cached
  /// profile/stats are bypassed and re-written with the post-transaction
  /// figures. See [refreshBalances] for the intent-revealing wrapper.
  Future<void> refreshUser({bool force = false}) async {
    await getUserInfo(force: force);
  }

  /// Hard refresh of every balance the app displays (NGN, USD, EUR).
  ///
  /// Clears the user cache first so nothing stale can be served even if the
  /// network call fails partway, then pulls fresh figures from the API.
  /// Call this after currency conversion, transfers, funding and bill payments.
  Future<void> refreshBalances() async {
    await CacheService().clearUserCache();
    await getUserInfoWithStats(force: true);
  }

  /// Optimistically update the SMS alert flag in local user state.
  /// Call this before the API so the UI updates instantly.

  void updateSmsAlert(bool enabled) {
    if (state.user == null) return;
    final updated = state.user!.copyWith(receiveSMS: enabled ? 1 : 0);
    state = state.copyWith(user: updated);
  }

  Future<void> logout() async {
    state = state.copyWith(isLoading: true);

    try {
      try {
        await NotificationService().deleteToken();
      } catch (e) {
        debugPrint('Error deleting FCM token on logout: $e');
      }
      await _authRepository.logout();
      // Clear all cache on logout
      await CacheService().clearAllCache();
      state = AuthState();
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  Future<bool> resetPassword(String email) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _authRepository.resetPassword(email);

      if (response.success) {
        state = state.copyWith(isLoading: false, error: null);
        return true;
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
        return false;
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> resetPasswordWithRequest(ResetPasswordRequest request) async {
    return await resetPassword(request.email);
  }

  Future<bool> resetPasswordVerified({
    required String email,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    // Clear all cache when resetting password
    await CacheService().clearAllCache();
    print('🔐 AuthProvider: Cleared all cache for password reset');

    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _authRepository.resetPasswordVerified(
        email: email,
        code: code,
        password: password,
        passwordConfirmation: passwordConfirmation,
      );

      if (response.success && response.data != null) {
        state = AuthState();
        return true;
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
        return false;
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> sendEmailOtp(String email) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _authRepository.sendEmailOtp(email);

      state = state.copyWith(isLoading: false);
      return response.success;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> verifyEmailOtp({
    required String email,
    required String code,
  }) async {
    // Clear all cache when verifying email OTP
    await CacheService().clearAllCache();
    print('🔐 AuthProvider: Cleared all cache for email verification');

    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _authRepository.verifyEmailOtp(
        email: email,
        code: code,
      );

      if (response.success && response.data != null) {
        state = state.copyWith(
          isLoading: false,
          user: response.data,
          error: null,
        );
        return true;
      } else {
        state = state.copyWith(isLoading: false, error: response.message);
        return false;
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  Future<bool> checkUser(CheckUserRequest request) async {
    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _authRepository.checkUser(request);

      state = state.copyWith(isLoading: false);
      return response.success;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
      return false;
    }
  }

  // Check User with email (convenience method)
  Future<bool> checkUserByEmail(String email) async {
    return await checkUser(CheckUserRequest(email: email));
  }

  // Check User with phone number (convenience method)
  Future<bool> checkUserByPhone(String phoneNumber) async {
    return await checkUser(CheckUserRequest(phoneNumber: phoneNumber));
  }

  // Check User with username (convenience method)
  Future<bool> checkUserByUsername(String username) async {
    return await checkUser(CheckUserRequest(username: username));
  }

  void clearError() {
    state = state.copyWith(error: null);
  }

  // Force refresh user data without using cache
  Future<GetUserInfoResponse?> forceRefreshUserInfo() async {
    try {
      print('🔐 AuthProvider: Force refreshing user info');

      // Clear user cache first
      await CacheService().clearUserCache();

      // Fetch fresh data from API
      final response = await _authRepository.getUserInfo();

      if (response.success && response.data != null) {
        // Update state with new user data
        state = state.copyWith(user: response.data!.user);

        // Cache the fresh response
        await CacheService().cacheUserStats(response.data!.toJson());
        print('🔐 AuthProvider: Force refreshed and cached user info');
        return response.data;
      }

      return null;
    } catch (e) {
      print('🔐 AuthProvider: Error force refreshing user info: $e');
      return null;
    }
  }
}

// Providers
final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepository();
});

final authNotifierProvider = StateNotifierProvider<AuthNotifier, AuthState>((
  ref,
) {
  final authRepository = ref.watch(authRepositoryProvider);
  return AuthNotifier(authRepository);
});

// Convenience providers
final isAuthenticatedProvider = Provider<bool>((ref) {
  return ref.watch(authNotifierProvider).isAuthenticated;
});

final currentUserProvider = Provider<UserModel?>((ref) {
  return ref.watch(authNotifierProvider).user;
});

final authLoadingProvider = Provider<bool>((ref) {
  return ref.watch(authNotifierProvider).isLoading;
});

final authErrorProvider = Provider<String?>((ref) {
  return ref.watch(authNotifierProvider).error;
});
