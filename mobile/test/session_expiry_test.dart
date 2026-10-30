import 'package:dupay/models/auth/get_user_info_response.dart';
import 'package:dupay/models/base/api_response.dart';
import 'package:dupay/providers/auth_provider.dart';
import 'package:dupay/repositories/auth_repository.dart';
import 'package:dupay/services/auth/session_events.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';

class _AuthenticatedRepository extends AuthRepository {
  @override
  Future<bool> isAuthenticated() async => true;

  @override
  Future<String?> getStoredAccessToken() async => 'test-token';

  @override
  Future<ApiResponse<GetUserInfoResponse>> getUserInfo() async =>
      ApiResponse<GetUserInfoResponse>(success: false, message: 'Unavailable');
}

void main() {
  test('session expiry removes the authenticated user state', () async {
    final container = ProviderContainer(overrides: [
      authRepositoryProvider.overrideWithValue(_AuthenticatedRepository()),
    ]);
    addTearDown(container.dispose);
    container.read(authNotifierProvider);

    await Future<void>.delayed(Duration.zero);
    expect(container.read(authNotifierProvider).isAuthenticated, isTrue);

    SessionEvents.notifyExpired();
    expect(container.read(authNotifierProvider).isAuthenticated, isFalse);
    expect(container.read(authNotifierProvider).accessToken, isNull);
    expect(container.read(authNotifierProvider).user, isNull);
  });
}
