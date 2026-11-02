import 'package:dupay/models/auth/login_response.dart';
import 'package:dupay/models/base/api_response.dart';
import 'package:dupay/providers/auth_provider.dart';
import 'package:dupay/repositories/auth_repository.dart';
import 'package:dupay/screen/auth/login_otp_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';

class _FakeAuthRepository extends AuthRepository {
  @override
  Future<ApiResponse<Map<String, dynamic>>> sendLoginOtp(
    String challengeId, {
    bool resend = false,
  }) async => ApiResponse(success: true, message: 'sent', data: {});

  @override
  Future<ApiResponse<LoginResponse>> verifyLoginOtp({
    required String challengeId,
    required String otp,
    String? fcmToken,
  }) async => ApiResponse(success: false, message: 'invalid');
}

void main() {
  testWidgets(
    'OTP screen validates six digits and prevents invalid submission',
    (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWithValue(_FakeAuthRepository()),
          ],
          child: MaterialApp(
            home: LoginOtpScreen(
              challengeId: 'challenge',
              repository: _FakeAuthRepository(),
            ),
          ),
        ),
      );
      await tester.pump();
      expect(find.text('Verify login'), findsOneWidget);
      await tester.tap(find.text('Verify'));
      await tester.pump();
      expect(find.text('Enter the six-digit code.'), findsOneWidget);
    },
  );
}
