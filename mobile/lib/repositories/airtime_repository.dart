import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/airtime/airtime_networks_response.dart';
import '../models/airtime/airtime_to_cash_networks_response.dart';
import '../models/airtime/buy_airtime_request.dart';
import '../models/airtime/convert_airtime_request.dart';

class AirtimeRepository {
  final ApiService _apiService;

  AirtimeRepository(this._apiService);

  /// Get airtime networks
  Future<ApiResponse<AirtimeNetworksResponse>> getAirtimeNetworks() async {
    try {
      print('📱 AirtimeRepository: Getting airtime networks');
      final response = await _apiService.get<AirtimeNetworksResponse>(
        ApiConstants.airtimeNetwork,
        fromJson: AirtimeNetworksResponse.fromJson,
      );

      return response;
    } catch (e) {
      print('📱 AirtimeRepository: Error getting airtime networks - $e');
      return ApiResponse<AirtimeNetworksResponse>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Get airtime to cash networks
  Future<ApiResponse<AirtimeToCashNetworksResponse>>
  getAirtimeToCashNetworks() async {
    try {
      print('📱 AirtimeRepository: Getting airtime to cash networks');
      final response = await _apiService.get<AirtimeToCashNetworksResponse>(
        ApiConstants.airtimeToCashNetworks,
        fromJson: AirtimeToCashNetworksResponse.fromJson,
      );

      return response;
    } catch (e) {
      print(
        '📱 AirtimeRepository: Error getting airtime to cash networks - $e',
      );
      return ApiResponse<AirtimeToCashNetworksResponse>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Buy airtime
  Future<ApiResponse<Map<String, dynamic>>> buyAirtime(
    BuyAirtimeRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.buyAirtime,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('📱 AirtimeRepository: Error buying airtime - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  /// Convert airtime to cash
  Future<ApiResponse<Map<String, dynamic>>> convertAirtimeToCash(
    ConvertAirtimeRequest request,
  ) async {
    try {
      final response = await _apiService.postSimple(
        ApiConstants.convertAirtimeToCash,
        data: request.toJson(),
      );

      return response;
    } catch (e) {
      print('📱 AirtimeRepository: Error converting airtime to cash - $e');
      return ApiResponse<Map<String, dynamic>>(
        success: false,
        message: e.toString(),
      );
    }
  }
}
