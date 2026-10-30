import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/bill/bills_response.dart';
import '../models/bill/pay_bill_request.dart';
import '../models/bill/verify_bill_request.dart';

class BillRepository {
  final ApiService _apiService;

  BillRepository(this._apiService);

  /// Get bills
  Future<ApiResponse<BillsResponse>> getBills() async {
    try {
      print('💡 BillRepository: Getting bills');
      final response = await _apiService.get<BillsResponse>(
        ApiConstants.getBills,
        fromJson: BillsResponse.fromJson,
      );

      return response;
    } catch (e) {
      print('💡 BillRepository: Error getting bills - $e');
      return ApiResponse<BillsResponse>(success: false, message: e.toString());
    }
  }

  /// Pay bill
  Future<ApiResponse<Map<String, dynamic>>> payBill(
    PayBillRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.payBill,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('💡 BillRepository: Error paying bill - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Verify bill
  Future<ApiResponse<Map<String, dynamic>>> verifyBill(
    VerifyBillRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.verifyBill,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('💡 BillRepository: Error verifying bill - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }
}











