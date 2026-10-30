// lib/repositories/auth_repository.dart
import 'package:dupay/models/auth/check_user_request.dart';
import 'package:dupay/models/auth/check_user_response.dart';
import 'package:dupay/models/auth/get_user_info_response.dart';
import 'package:dupay/models/auth/login_request.dart';
import 'package:dupay/models/auth/login_response.dart';
import 'package:dupay/models/auth/register_request.dart';
import 'package:dupay/models/auth/register_response.dart';
import 'package:dupay/models/auth/reset_password_request.dart';
import 'package:dupay/models/auth/reset_password_response.dart';
import 'package:dupay/models/auth/reset_password_verified_request.dart';
import 'package:dupay/models/auth/reset_password_verified_response.dart';
import 'package:dupay/models/auth/user_model.dart';
import 'package:dupay/models/base/api_response.dart';

import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../services/storage/secure_storage_service.dart';

class AuthRepository {
  final ApiService _apiService = ApiService.instance;

  // Login
  Future<ApiResponse<LoginResponse>> login(LoginRequest request) async {

    final response = await _apiService.post<LoginResponse>(
      ApiConstants.login,
      data: request.toJson(),
      fromJson: LoginResponse.fromJson,
    );

    // Save tokens if login successful
    if (response.success && response.data != null) {
      await SecureStorageService.saveAccessToken(response.data!.accessToken);
      await SecureStorageService.saveUserId(response.data!.user.id.toString());
    }

    return response;
  }

  // Register
  //
  // The backend /register endpoint expects multipart/form-data because it
  // accepts file uploads (id_image, user_photo). We therefore build a FormData
  // body from the request rather than sending JSON.
  Future<ApiResponse<RegisterResponse>> register(
    RegisterRequest request,
  ) async {

    final formData = await request.toFormData();

    final response = await _apiService.post<RegisterResponse>(
      ApiConstants.register,
      data: formData,
      fromJson: RegisterResponse.fromJson,
    );

    // Save tokens if registration successful
    if (response.success && response.data != null) {
      await SecureStorageService.saveAccessToken(response.data!.accessToken);
      await SecureStorageService.saveUserId(response.data!.user.id.toString());
    }

    return response;
  }

  // Reset Password
  Future<ApiResponse<ResetPasswordResponse>> resetPassword(String email) async {
    final request = ResetPasswordRequest(email: email);
    return await _apiService.post<ResetPasswordResponse>(
      ApiConstants.resetPassword,
      data: request.toJson(),
      fromJson: ResetPasswordResponse.fromJson,
    );
  }

  // Reset Password Verified
  Future<ApiResponse<ResetPasswordVerifiedResponse>> resetPasswordVerified({
    required String email,
    required String code,
    required String password,
    required String passwordConfirmation,
  }) async {
    final request = ResetPasswordVerifiedRequest(
      email: email,
      code: code,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );

    final response = await _apiService.post<ResetPasswordVerifiedResponse>(
      ApiConstants.resetPasswordVerified,
      data: request.toJson(),
      fromJson: ResetPasswordVerifiedResponse.fromJson,
    );

    // This response contains no access token. Require a fresh login.
    if (response.success && response.data != null) {
      await SecureStorageService.clearAll();
    }

    return response;
  }

  // Send Email OTP
  Future<ApiResponse<Map<String, dynamic>>> sendEmailOtp(String email) async {
    return await _apiService.postSimple(
      ApiConstants.sendEmailOtp,
      data: {'email': email},
    );
  }

  // Verify Email OTP
  Future<ApiResponse<UserModel>> verifyEmailOtp({
    required String email,
    required String code,
  }) async {
    return await _apiService.post<UserModel>(
      ApiConstants.verifyEmailOtp,
      data: {'email': email, 'code': code},
      fromJson: (json) => UserModel.fromJson(json['user'] as Map<String, dynamic>),
    );
  }

  // Check User
  Future<ApiResponse<CheckUserResponse>> checkUser(
    CheckUserRequest request,
  ) async {
    return await _apiService.post<CheckUserResponse>(
      ApiConstants.checkUser,
      data: request.toJson(),
      fromJson: CheckUserResponse.fromJson,
    );
  }

  // Check User with email (convenience method)
  Future<ApiResponse<CheckUserResponse>> checkUserByEmail(String email) async {
    return await checkUser(CheckUserRequest(email: email));
  }

  // Check User with phone number (convenience method)
  Future<ApiResponse<CheckUserResponse>> checkUserByPhone(
    String phoneNumber,
  ) async {
    return await checkUser(CheckUserRequest(phoneNumber: phoneNumber));
  }

  // Check User with username (convenience method)
  Future<ApiResponse<CheckUserResponse>> checkUserByUsername(
    String username,
  ) async {
    return await checkUser(CheckUserRequest(username: username));
  }

  // Get Updated User Information
  Future<ApiResponse<GetUserInfoResponse>> getUserInfo() async {
    return await _apiService.get<GetUserInfoResponse>(
      ApiConstants.getUserInfo,
      fromJson: GetUserInfoResponse.fromJson,
    );
  }

  // Logout
  Future<void> logout() async {
    try {
      if (await SecureStorageService.getAccessToken() != null) {
        await _apiService.postSimple(ApiConstants.logout);
      }
    } catch (_) {
      // Local sign-out must still work when the API cannot be reached.
    } finally {
      await SecureStorageService.clearAll();
    }
  }

  // Check if user is authenticated
  Future<bool> isAuthenticated() async {
    return await SecureStorageService.isAuthenticated();
  }

  // Get stored user ID
  Future<String?> getStoredUserId() async {
    return await SecureStorageService.getUserId();
  }

  // Get stored access token
  Future<String?> getStoredAccessToken() async {
    return await SecureStorageService.getAccessToken();
  }
}
