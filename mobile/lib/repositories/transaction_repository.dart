// lib/repositories/transaction_repository.dart

import '../core/network/api_service.dart';
import '../core/constants/api_contants.dart';
import '../models/base/api_response.dart';
import '../models/transaction/transaction_model.dart';

class TransactionRepository {
  final ApiService _apiService = ApiService.instance;

  // GET /transactions  — backend returns paginated Map, NOT a flat list
  Future<ApiResponse<List<TransactionModel>>> searchTransactions({
    String? search,
    String? type,
    String? status,
    String? startDate,
    String? endDate,
    int? page,
    int? limit,
  }) async {
    final queryParams = <String, dynamic>{};
    if (search != null && search.isNotEmpty) queryParams['search'] = search;
    if (type != null)      queryParams['type']       = type;
    if (status != null)    queryParams['status']     = status;
    if (startDate != null) queryParams['start_date'] = startDate;
    if (endDate != null)   queryParams['end_date']   = endDate;
    if (page != null)      queryParams['page']       = page;
    if (limit != null)     queryParams['limit']      = limit;

    try {
      // Use get<Map> so we receive the raw response and can unpack it ourselves
      final response = await _apiService.get<Map<String, dynamic>>(
        ApiConstants.transactionSearch,
        queryParameters: queryParams,
        fromJson: (json) => json,
      );

      if (!response.success) {
        return ApiResponse<List<TransactionModel>>(
          success: false,
          message: response.message,
        );
      }

      // The API response shape can be any of:
      //   { success:true, data: [ ...items ] }            ← flat list
      //   { success:true, data: { data: [...], ... } }    ← paginated object
      //   { success:true, data: { transactions: [...] } } ← named key
      final raw = response.data;
      List<dynamic> items = [];

      if (raw != null) {
        final dataField = raw['data'];
        if (dataField is List) {
          // flat list
          items = dataField;
        } else if (dataField is Map) {
          // paginated — Laravel wraps in { data: [...], current_page: ... }
          final inner = dataField['data'];
          if (inner is List) {
            items = inner;
          } else {
            // try common alternative keys
            for (final key in ['transactions', 'items', 'records']) {
              if (dataField[key] is List) {
                items = dataField[key] as List;
                break;
              }
            }
          }
        }
      }

      final transactions = items
          .whereType<Map<String, dynamic>>()
          .map((e) => TransactionModel.fromJson(e))
          .toList();

      // Extract last_page for hasMore detection
      // Store in errors map (nullable, unused) as pagination metadata
      int lastPage = 1;
      if (raw != null && raw['data'] is Map) {
        final pg = raw['data'] as Map;
        lastPage = int.tryParse(pg['last_page']?.toString() ?? '1') ?? 1;
      }

      return ApiResponse<List<TransactionModel>>(
        success: true,
        message: response.message,
        data: transactions,
        // Encode last_page in statusCode for provider to read
        statusCode: lastPage,
      );
    } catch (e) {
      print('📊 TransactionRepository: Error - $e');
      return ApiResponse<List<TransactionModel>>(
        success: false,
        message: e.toString(),
      );
    }
  }

  // POST /transactions/create-pin
  Future<ApiResponse<Map<String, dynamic>>> createTransactionPin({
    required String pin,
    required String confirmPin,
  }) async {
    return await _apiService.postSimple(
      ApiConstants.createTransactionPin,
      data: {'pin': pin, 'pin_confirmation': confirmPin},
    );
  }

  // POST /transactions/change-pin
  Future<ApiResponse<Map<String, dynamic>>> changeTransactionPin({
    required String oldPin,
    required String newPin,
    required String confirmNewPin,
  }) async {
    return await _apiService.postSimple(
      ApiConstants.changeTransactionPin,
      data: {
        'old_pin': oldPin,
        'pin': newPin,
        'pin_confirmation': confirmNewPin,
      },
    );
  }
}