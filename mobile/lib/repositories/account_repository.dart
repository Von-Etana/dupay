// // lib/repositories/account_repository.dart
// import '../core/network/api_service.dart';
// import '../core/constants/api_constants.dart';
// import '../models/base/api_response.dart';
// import '../models/account/bank_account_model.dart';

// class AccountRepository {
//   final ApiService _apiService = ApiService.instance;

//   // Get Virtual Account
//   Future<ApiResponse<Map<String, dynamic>>> getVirtualAccount() async {
//     return await _apiService.get<Map<String, dynamic>>(
//       ApiConstants.virtualAccount,
//       fromJson: (json) => json,
//     );
//   }

//   // Get Bank Accounts
//   Future<ApiResponse<List<BankAccountModel>>> getBankAccounts() async {
//     return await _apiService.getList<BankAccountModel>(
//       ApiConstants.bankAccount,
//       fromJson: BankAccountModel.fromJson,
//     );
//   }

//   // Store Bank Account
//   Future<ApiResponse<BankAccountModel>> storeBankAccount({
//     required String accountNumber,
//     required String bankCode,
//     required String accountName,
//   }) async {
//     return await _apiService.post<BankAccountModel>(
//       ApiConstants.storeBankAccount,
//       data: {
//         'account_number': accountNumber,
//         'bank_code': bankCode,
//         'account_name': accountName,
//       },
//       fromJson: BankAccountModel.fromJson,
//     );
//   }

//   // Delete Bank Account
//   Future<ApiResponse<Map<String, dynamic>>> deleteBankAccount(int accountId) async {
//     return await _apiService.delete(
//       '${ApiConstants.deleteBankAccount}/$accountId',
//     );
//   }
// }