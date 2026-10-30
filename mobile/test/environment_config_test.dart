import 'package:dupay/core/constants/api_contants.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('development builds require a separate HTTPS API', () {
    expect(EnvironmentConfig.isDevelopment, isTrue);
    expect(() => EnvironmentConfig.baseUrl, throwsStateError);
  });
}
