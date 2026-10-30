import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/cable/cables_response.dart';
import '../models/cable/buy_cable_request.dart';
import '../models/cable/verify_cable_request.dart';

class CableRepository {
  final ApiService _apiService;

  CableRepository(this._apiService);

  /// Get cables
  Future<ApiResponse<CablesResponse>> getCables() async {
    try {
      print('📺 CableRepository: Getting cables');
      final response = await _apiService.get<CablesResponse>(
        ApiConstants.getCables,
        fromJson: CablesResponse.fromJson,
      );

      return response;
    } catch (e) {
      print('📺 CableRepository: Error getting cables - $e');
      return ApiResponse<CablesResponse>(success: false, message: e.toString());
    }
  }

  /// Buy cable
  Future<ApiResponse<Map<String, dynamic>>> buyCable(
    BuyCableRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.buyCable,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('📺 CableRepository: Error buying cable - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Verify cable number
  Future<ApiResponse<Map<String, dynamic>>> verifyCableNumber(
    VerifyCableRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.verifyCableNumber,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('📺 CableRepository: Error verifying cable number - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }
}










