import 'package:dupay/models/auth/user_model.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('user parsing does not retain server supplied secrets', () {
    final user = UserModel.fromJson({
      'id': 42,
      'email': 'example@example.invalid',
      'transaction_pin': '1234',
      'profile': {
        'id': 7,
        'user_id': 42,
        'username': 'example',
        'ref_id': 'ref',
        'api_key': 'private-api-key',
        'user_key': 'private-user-key',
      },
    });

    expect(user.hasTransactionPin, isTrue);
    expect(user.transactionPin, isNot('1234'));
    expect(user.toJson().toString(), isNot(contains('1234')));
    expect(user.profile!.toJson(), isNot(containsPair('api_key', 'private-api-key')));
    expect(user.profile!.toJson(), isNot(containsPair('user_key', 'private-user-key')));
  });
}
