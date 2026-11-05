import 'package:flutter_test/flutter_test.dart';
import 'package:dupay/services/transactions/transaction_result_resolver.dart';

void main() {
  group('TransactionResultResolver', () {
    test('immediately trusts confirmed provider success', () async {
      final result = await TransactionResultResolver.resolve(
        responseSuccess: true,
        responseMessage: 'Order complete',
        responseData: {
          'status': 'success',
          'reference': 'REF12345',
          'initial_balance': '1000',
          'final_balance': '900',
        },
        statusCode: 200,
        amount: '100',
        submittedAt: DateTime.now(),
        descriptionHints: ['08012345678', 'MTN', 'airtime'],
      );

      expect(result.success, isTrue);
      expect(result.status, equals('success'));
      expect(result.reference, equals('REF12345'));
      expect(result.initialBalance, equals('1000'));
      expect(result.finalBalance, equals('900'));
    });

    test('immediately returns failure for 400 client validation errors without polling', () async {
      final result = await TransactionResultResolver.resolve(
        responseSuccess: false,
        responseMessage: 'Incorrect PIN entered',
        responseData: {
          'status': 'failed',
          'reference': '',
        },
        statusCode: 400,
        amount: '100',
        submittedAt: DateTime.now(),
        descriptionHints: ['airtime'],
      );

      expect(result.success, isFalse);
      expect(result.status, equals('failed'));
      expect(result.message, equals('Incorrect PIN entered'));
    });
  });
}
