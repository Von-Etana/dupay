// lib/repositories/fund_wallet_repository.dart

import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/fund_wallet/virtual_account_model.dart';

class FundWalletRepository {
  final ApiService _apiService;
  FundWalletRepository(this._apiService);

  // GET /virtual-account/fetch
  Future<ApiResponse<Map<String, dynamic>>> getVirtualAccount() async {
    try {
      return await _apiService.get<Map<String, dynamic>>(
        ApiConstants.virtualAccountFetch,
        fromJson: (json) => json,
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }

  // GET /virtual-account/create
  Future<ApiResponse<Map<String, dynamic>>> createVirtualAccount() async {
    try {
      return await _apiService.get<Map<String, dynamic>>(
        ApiConstants.virtualAccountCreate,
        fromJson: (json) => json,
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(success: false, message: e.toString());
    }
  }
}
