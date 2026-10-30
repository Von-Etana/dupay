// lib/repositories/pos_repository.dart

import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/pos/pos_request_model.dart';

class PosRepository {
  final ApiService _apiService;
  PosRepository(this._apiService);

  // GET /pos-request  — fetch existing POS request for this user
  Future<ApiResponse<Map<String, dynamic>>> getPosRequest() async {
    try {
      print('💳 PosRepository: Getting POS request');
      return await _apiService.get<Map<String, dynamic>>(
        ApiConstants.posRequest,
        fromJson: (json) => json,
      );
    } catch (e) {
      print('💳 PosRepository: Error getting POS request - $e');
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  // POST /post-request  — submit new POS request
  Future<ApiResponse<Map<String, dynamic>>> submitPosRequest(
    PosRequestModel request,
  ) async {
    try {
      return await _apiService.postSimple(
        ApiConstants.posPostRequest,
        data: request.toJson(),
      );
    } catch (e) {
      print('💳 PosRepository: Error submitting POS request - $e');
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }
}
