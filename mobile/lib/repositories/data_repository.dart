import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/data/data_response.dart';
import '../models/data/buy_data_request.dart';
import '../models/data/buy_data_card_request.dart';

class DataRepository {
  final ApiService _apiService;

  DataRepository(this._apiService);

  /// Get data networks with plans
  Future<ApiResponse<DataResponse>> getDataNetworks() async {
    try {
      print('📶 DataRepository: Getting data networks');
      final response = await _apiService.get<DataResponse>(
        ApiConstants.getDataNetworks,
        fromJson: DataResponse.fromJson,
      );

      return response;
    } catch (e) {
      print('📶 DataRepository: Error getting data networks - $e');
      return ApiResponse<DataResponse>(success: false, message: e.toString());
    }
  }

  /// Buy data
  Future<ApiResponse<Map<String, dynamic>>> buyData(
    BuyDataRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.buyData,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('📶 DataRepository: Error buying data - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Get data card networks
  Future<ApiResponse<DataCardNetworksResponse>> getDataCardNetworks() async {
    try {
      print('📶 DataRepository: Getting data card networks');
      final response = await _apiService.get<DataCardNetworksResponse>(
        ApiConstants.getDataCardNetworks,
        fromJson: DataCardNetworksResponse.fromJson,
      );

      return response;
    } catch (e) {
      print('📶 DataRepository: Error getting data card networks - $e');
      return ApiResponse<DataCardNetworksResponse>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Buy data card
  Future<ApiResponse<Map<String, dynamic>>> buyDataCard(
    BuyDataCardRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.buyDataCard,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('📶 DataRepository: Error buying data card - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }
}










