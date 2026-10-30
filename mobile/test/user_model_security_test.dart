import 'package:dupay/models/auth/user_model.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('PIN presence is parsed without receiving the PIN hash', () {
    final user = UserModel.fromJson({
      'id': 1,
      'email': 'test@example.invalid',
      'has_transaction_pin': true,
      'email_verify': '1',
    });

    expect(user.hasTransactionPin, isTrue);
    expect(user.isEmailVerified, isTrue);
    expect(user.transactionPin, 'set');
    expect(user.emailCode, isNull);
  });
}
