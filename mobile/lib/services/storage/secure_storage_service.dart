// lib/services/storage/secure_storage_service.dart
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:logger/logger.dart';

class SecureStorageService {
  static const _storage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  static final _logger = Logger();

  // Keys
  static const String _accessTokenKey = 'access_token';
  static const String _refreshTokenKey = 'refresh_token';
  static const String _userIdKey = 'user_id';
  static const String _deviceIdKey = 'device_id';
  static const String _biometricEnabledKey = 'biometric_enabled';
  static const String _rememberedEmailKey = 'remembered_email';

  // Token Management
  static Future<void> saveAccessToken(String token) async {
    try {
      await _storage.write(key: _accessTokenKey, value: token);
    } catch (e) {
      _logger.e('Error saving access token: $e');
      rethrow;
    }
  }

  static Future<String?> getAccessToken() async {
    try {
      return await _storage.read(key: _accessTokenKey);
    } catch (e) {
      _logger.e('Error reading access token: $e');
      return null;
    }
  }

  static Future<void> saveRefreshToken(String token) async {
    try {
      await _storage.write(key: _refreshTokenKey, value: token);
    } catch (e) {
      _logger.e('Error saving refresh token: $e');
      rethrow;
    }
  }

  static Future<String?> getRefreshToken() async {
    try {
      return await _storage.read(key: _refreshTokenKey);
    } catch (e) {
      _logger.e('Error reading refresh token: $e');
      return null;
    }
  }

  // User Data
  static Future<void> saveUserId(String userId) async {
    try {
      await _storage.write(key: _userIdKey, value: userId);
    } catch (e) {
      _logger.e('Error saving user ID: $e');
      rethrow;
    }
  }

  static Future<String?> getUserId() async {
    try {
      return await _storage.read(key: _userIdKey);
    } catch (e) {
      _logger.e('Error reading user ID: $e');
      return null;
    }
  }

  // Device Management
  static Future<void> saveDeviceId(String deviceId) async {
    try {
      await _storage.write(key: _deviceIdKey, value: deviceId);
    } catch (e) {
      _logger.e('Error saving device ID: $e');
      rethrow;
    }
  }

  static Future<String?> getDeviceId() async {
    try {
      return await _storage.read(key: _deviceIdKey);
    } catch (e) {
      _logger.e('Error reading device ID: $e');
      return null;
    }
  }

  // Security Settings
  static Future<void> setBiometricEnabled(bool enabled) async {
    try {
      await _storage.write(
        key: _biometricEnabledKey,
        value: enabled.toString(),
      );
    } catch (e) {
      _logger.e('Error saving biometric setting: $e');
      rethrow;
    }
  }

  static Future<bool> isBiometricEnabled() async {
    try {
      final value = await _storage.read(key: _biometricEnabledKey);
      return value == 'true';
    } catch (e) {
      _logger.e('Error reading biometric setting: $e');
      return false;
    }
  }

  static Future<bool> hasBiometricPreference() async {
    return await _storage.read(key: _biometricEnabledKey) != null;
  }

  // Remember-me email ONLY (never the password). Stored encrypted so the
  // login screen can pre-fill the email without ever persisting credentials.
  static Future<void> saveRememberedEmail(String email) async {
    try {
      await _storage.write(key: _rememberedEmailKey, value: email);
    } catch (e) {
      _logger.e('Error saving remembered email: $e');
    }
  }

  static Future<String?> getRememberedEmail() async {
    try {
      return await _storage.read(key: _rememberedEmailKey);
    } catch (e) {
      _logger.e('Error reading remembered email: $e');
      return null;
    }
  }

  static Future<void> clearRememberedEmail() async {
    try {
      await _storage.delete(key: _rememberedEmailKey);
    } catch (e) {
      _logger.e('Error clearing remembered email: $e');
    }
  }

  // Clear all data (logout)
  static Future<void> clearAll() async {
    try {
      await _storage.deleteAll();
    } catch (e) {
      _logger.e('Error clearing secure storage: $e');
      rethrow;
    }
  }

  // Check if user is authenticated
  static Future<bool> isAuthenticated() async {
    final token = await getAccessToken();
    return token != null && token.isNotEmpty;
  }
}
