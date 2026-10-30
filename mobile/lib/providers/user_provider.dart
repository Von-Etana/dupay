// lib/providers/user_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';

class UserListState {
  final bool isLoading;
  final String? error;
  final List<Map<String, dynamic>> users;

  const UserListState({
    this.isLoading = false,
    this.error,
    this.users = const [],
  });

  UserListState copyWith({
    bool? isLoading,
    String? error,
    List<Map<String, dynamic>>? users,
  }) {
    return UserListState(
      isLoading: isLoading ?? this.isLoading,
      error:     error     ?? this.error,
      users:     users     ?? this.users,
    );
  }
}

class UserNotifier extends StateNotifier<UserListState> {
  final ApiService _api;
  UserNotifier(this._api) : super(const UserListState());

  // ── Do NOT auto-load all users — users must search explicitly ─────────────
  Future<void> loadAllUsers() async {
    state = state.copyWith(isLoading: false, users: []);
  }

  // ── Exact match search via GET /find-user/{query} ───────────────────────
  // Matches exact username, phone number, or email only
  Future<void> searchUsers(String query) async {
    if (query.trim().isEmpty) {
      state = state.copyWith(isLoading: false, users: []);
      return;
    }

    state = state.copyWith(isLoading: true, error: null);

    try {
      final response = await _api.get<Map<String, dynamic>>(
        '/find-user/${Uri.encodeComponent(query.trim())}',
        queryParameters: {'search': query.trim()},
        fromJson: (json) => json,
      );

      if (response.success && response.data != null) {
        final data = response.data!['data'];
        if (data != null && data is Map<String, dynamic>) {
          // Single exact-match user returned
          state = state.copyWith(
            isLoading: false,
            users: _normalizeUsers([data]),
          );
        } else {
          state = state.copyWith(isLoading: false, users: []);
        }
      } else {
        // No match found
        state = state.copyWith(isLoading: false, users: []);
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: e.toString());
    }
  }

  // ── Unwrap Laravel paginator ──────────────────────────────────────────────
  Map<String, dynamic> _extractPaginated(Map<String, dynamic> responseData) {
    var raw      = responseData['data'];
    int lastPage = 1;

    if (raw is Map) {
      lastPage = int.tryParse(raw['last_page']?.toString() ?? '1') ?? 1;
      raw      = raw['data'];
    }

    final items = (raw is List)
        ? raw.whereType<Map<String, dynamic>>().toList()
        : <Map<String, dynamic>>[];

    return {'items': items, 'lastPage': lastPage};
  }

  // ── Normalize user map for display ────────────────────────────────────────
  List<Map<String, dynamic>> _normalizeUsers(List<Map<String, dynamic>> raw) {
    return raw.map((user) {
      final profileRaw = user['profile'];
      final profile   = profileRaw is Map<String, dynamic> ? profileRaw : <String, dynamic>{};
      final firstName = (profile['first_name'] ?? user['first_name'] ?? '').toString().trim();
      final lastName  = (profile['last_name']  ?? user['last_name']  ?? '').toString().trim();
      final username  = (profile['username']   ?? user['username']   ?? '').toString().trim();
      final fullName  = [firstName, lastName].where((s) => s.isNotEmpty).join(' ');
      return {
        ...user,
        'name':      fullName.isEmpty ? username : fullName,
        'username':  username,
        'wallet_id': user['id']?.toString() ?? '',
      };
    }).toList();
  }
}

final userNotifierProvider =
    StateNotifierProvider<UserNotifier, UserListState>((ref) {
  return UserNotifier(ApiService.instance);
});