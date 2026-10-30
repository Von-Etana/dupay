// lib/repositories/recharge_card_repository.dart

import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/recharge_card/epin_model.dart';

class RechargeCardRepository {
  final ApiService _api;

  RechargeCardRepository(this._api);

  /// POST /buy-epin
  /// body: { network_id, value, quantity, transaction_pin }
  Future<ApiResponse<Map<String, dynamic>>> buyEpin({
    required int    networkId,
    required int    value,
    required int    quantity,
    required String transactionPin,
  }) async {
    try {
      return await _api.postSimple(
        ApiConstants.buyEpin,
        data: {
          'network_id':      networkId,
          'value':           value,
          'quantity':        quantity,
          'transaction_pin': transactionPin,
        },
      );
    } catch (e) {
      return ApiResponse<Map<String, dynamic>>(
          success: false, message: e.toString());
    }
  }

  /// GET /user/fetch-epins
  /// Returns the list of previously purchased ePIN orders.
  Future<ApiResponse<List<EpinModel>>> fetchEpins() async {
    try {
      return await _api.getList<EpinModel>(
        ApiConstants.fetchEpins,
        fromJson: EpinModel.fromJson,
      );
    } catch (e) {
      return ApiResponse<List<EpinModel>>(
          success: false, message: e.toString());
    }
  }
}