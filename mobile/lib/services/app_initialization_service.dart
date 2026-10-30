// lib/core/services/app_initialization_service.dart
import 'package:dupay/core/network/api_service.dart';
import 'package:dupay/core/constants/api_contants.dart';

class AppInitializationService {
  /// Initialize the app with required configurations
  static Future<void> initialize() async {
    print('🚀 AppInitializationService: Starting DuPay initialization');

    try {
      // Initialize ismart header
      await _initializeIsmartHeader();

      // Add other initialization tasks here if needed
      // await _initializeOtherServices();

      print('✅ AppInitializationService: DuPay initialization completed');
    } catch (e) {
      print('❌ AppInitializationService: Initialization failed - $e');
      rethrow;
    }
  }

  /// Initialize the ismart header value
  static Future<void> _initializeIsmartHeader() async {
    try {
      print('🔧 AppInitializationService: Setting up ismart header');

      // Use the backend-required ismart value
      const String ismartValue = ApiConstants.ismart; // typically 'api'

      // Set the ismart header
      ApiService.instance.setIsmartHeader(ismartValue);

      print('✅ AppInitializationService: ismart header set to: $ismartValue');
    } catch (e) {
      print('❌ AppInitializationService: Failed to set ismart header - $e');
      rethrow;
    }
  }

  /// Update ismart header (useful for testing different values)
  static Future<void> updateIsmartHeader(String newIsmartValue) async {
    try {
      ApiService.instance.setIsmartHeader(newIsmartValue);
      print(
        '✅ AppInitializationService: ismart header updated to: $newIsmartValue',
      );
    } catch (e) {
      print('❌ AppInitializationService: Failed to update ismart header - $e');
      rethrow;
    }
  }

  /// Test different ismart values to find the correct one
  static Future<String?> testIsmartValues(List<String> testValues) async {
    print('🧪 AppInitializationService: Testing different ismart values');

    for (String testValue in testValues) {
      try {
        print('Testing ismart value: $testValue');
        ApiService.instance.setIsmartHeader(testValue);

        // You can add a simple API call here to test if the value works
        // For now, we'll just set it and return
        print('✅ Successfully set ismart to: $testValue');
        return testValue;
      } catch (e) {
        print('❌ Failed with ismart value $testValue: $e');
        continue;
      }
    }

    return null;
  }

  /// Common ismart values to test
  static const List<String> commonIsmartValues = [
    'mobile',
    'flutter',
    'dupay-mobile',
    'dupay-flutter',
    'app',
    'dupay',
    'android',
    'ios',
    'mobile-app',
    'client-mobile',
    'v1',
    '1.0',
    'smart',
    'ismart',
  ];
}
