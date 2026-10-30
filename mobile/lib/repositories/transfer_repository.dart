import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/transfer/transfer_model.dart';

class TransferRepository {
  final ApiService _apiService;

  TransferRepository(this._apiService);

  /// Fund Transfer
  Future<ApiResponse<Map<String, dynamic>>> fundTransfer(
    TransferModel transfer,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.fundTransfer,
        data: transfer.toJson(),
      );

      return response;
    } catch (e) {
      print('💸 TransferRepository: Error fund transfer - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Request Fund
  Future<ApiResponse<Map<String, dynamic>>> requestFund(
    TransferModel request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.fundRequest,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('💸 TransferRepository: Error request fund - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Manual Funding
  Future<ApiResponse<Map<String, dynamic>>> manualFunding(
    ManualFundingModel funding,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.manualFunding,
        data: funding.toJson(),
      );

      return response;
    } catch (e) {
      print('💸 TransferRepository: Error manual funding - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }
}
