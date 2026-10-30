// // lib/repositories/user_repository.dart
// import '../core/network/api_service.dart';
// import '../core/constants/api_constants.dart';
// import '../models/base/api_response.dart';
// import '../models/auth/user_model.dart';

// class UserRepository {
//   final ApiService _apiService = ApiService.instance;

//   // Update Profile Field
//   Future<ApiResponse<UserModel>> updateProfileField({
//     required String field,
//     required dynamic value,
//   }) async {
//     return await _apiService.post<UserModel>(
//       ApiConstants.updateProfileField,
//       data: {
//         'field': field,
//         'value': value,
//       },
//       fromJson: UserModel.fromJson,
//     );
//   }

//   // Get All Users (for transfers/search)
//   Future<ApiResponse<List<UserModel>>> getAllUsers({
//     int? page,
//     int? limit,
//   }) async {
//     final queryParams = <String, dynamic>{};
//     if (page != null) queryParams['page'] = page;
//     if (limit != null) queryParams['limit'] = limit;

//     return await _apiService.getList<UserModel>(
//       ApiConstants.allUsers,
//       queryParameters: queryParams,
//       fromJson: UserModel.fromJson,
//     );
//   }

//   // Search Users
//   Future<ApiResponse<List<UserModel>>> searchUsers(String query) async {
//     return await _apiService.getList<UserModel>(
//       ApiConstants.allUsersSearch,
//       queryParameters: {'search': query},
//       fromJson: UserModel.fromJson,
//     );
//   }

//   // Find Users
//   Future<ApiResponse<List<UserModel>>> findUsers(String identifier) async {
//     return await _apiService.getList<UserModel>(
//       ApiConstants.findUsers,
//       queryParameters: {'identifier': identifier},
//       fromJson: UserModel.fromJson,
//     );
//   }

//   // Get Single User
//   Future<ApiResponse<UserModel>> getUser(int userId) async {
//     return await _apiService.get<UserModel>(
//       '${ApiConstants.getUser}/$userId',
//       fromJson: UserModel.fromJson,
//     );
//   }

//   // Change Password
//   Future<ApiResponse<Map<String, dynamic>>> changePassword({
//     required String currentPassword,
//     required String newPassword,
//     required String confirmPassword,
//   }) async {
//     return await _apiService.postSimple(
//       ApiConstants.changePassword,
//       data: {
//         'current_password': currentPassword,
//         'new_password': newPassword,
//         'new_password_confirmation': confirmPassword,
//       },
//     );
//   }

//   // Update Profile
//   Future<ApiResponse<UserModel>> updateProfile(Map<String, dynamic> profileData) async {
//     return await _apiService.post<UserModel>(
//       ApiConstants.updateProfile,
//       data: profileData,
//       fromJson: UserModel.fromJson,
//     );
//   }

//   // Upgrade Account
//   Future<ApiResponse<Map<String, dynamic>>> upgradeAccount({
//     required String accountType,
//     Map<String, dynamic>? additionalData,
//   }) async {
//     return await _apiService.postSimple(
//       ApiConstants.upgradeAccount,
//       data: {
//         'account_type': accountType,
//         ...?additionalData,
//       },
//     );
//   }
// }