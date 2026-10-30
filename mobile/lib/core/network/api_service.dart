// lib/core/network/api_service.dart
import 'package:dio/dio.dart';
import 'package:dupay/models/base/api_response.dart';
import '../constants/api_contants.dart';
import '../interceptors/auth_interceptor.dart';
import '../interceptors/logging_interceptor.dart';
import '../interceptors/retry_interceptor.dart';
import '../exceptions/api_exception.dart';

class ApiService {
  static ApiService? _instance;
  late final Dio _dio;
  String? _ismartValue;

  ApiService._internal() {
    _dio = Dio();
    _setupDio();
  }

  static ApiService get instance {
    _instance ??= ApiService._internal();
    return _instance!;
  }

  void _setupDio() {
    _dio.options = BaseOptions(
      baseUrl: EnvironmentConfig.baseUrl,
      connectTimeout: Duration(milliseconds: ApiConstants.connectTimeout),
      receiveTimeout: Duration(milliseconds: ApiConstants.receiveTimeout),
      sendTimeout: Duration(milliseconds: ApiConstants.sendTimeout),
      headers: {
        'Content-Type': ApiConstants.contentType,
        'Accept': ApiConstants.contentType,
        // Add default ismart header if available
        if (_ismartValue != null) 'ismart': _ismartValue!,
      },
    );

    // Add interceptors
    _dio.interceptors.addAll([
      // Add ismart header interceptor before other interceptors
      InterceptorsWrapper(
        onRequest: (options, handler) {
          // Ensure ismart header is always present if we have a value
          if (_ismartValue != null && !options.headers.containsKey('ismart')) {
            options.headers['ismart'] = _ismartValue;
          }
          handler.next(options);
        },
      ),
      AuthInterceptor(),
      LoggingInterceptor(),
      RetryInterceptor(),
    ]);
  }

  /// Set the ismart header value
  /// This should be called during app initialization or after getting the value
  void setIsmartHeader(String ismartValue) {
    _ismartValue = ismartValue;
    _dio.options.headers['ismart'] = ismartValue;
  }

  /// Get current ismart header value
  String? get ismartValue => _ismartValue;

  /// Check if ismart header is set
  bool get hasIsmartHeader => _ismartValue != null;

  /// Remove ismart header (if needed)
  void clearIsmartHeader() {
    _ismartValue = null;
    _dio.options.headers.remove('ismart');
    print('🌐 ApiService: ismart header cleared');
  }

  /// Update multiple headers at once
  void updateHeaders(Map<String, String> headers) {
    _dio.options.headers.addAll(headers);
  }

  // Generic GET request
  Future<ApiResponse<List<T>>> getList<T>(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    required T Function(Map<String, dynamic>) fromJson,
    String? customIsmartValue, // Optional per-request ismart value
  }) async {
    try {
      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      final response = await _dio.get(
        endpoint,
        queryParameters: queryParameters,
        options:
            customIsmartValue != null
                ? Options(headers: {'ismart': customIsmartValue})
                : null,
      );

      return _handleListResponse<T>(response, fromJson);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // Generic GET request for single item
  Future<ApiResponse<T>> get<T>(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    required T Function(Map<String, dynamic>) fromJson,
    String? customIsmartValue, // Optional per-request ismart value
  }) async {
    try {
      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      final response = await _dio.get(
        endpoint,
        queryParameters: queryParameters,
        options:
            customIsmartValue != null
                ? Options(headers: {'ismart': customIsmartValue})
                : null,
      );

      return _handleSingleResponse<T>(response, fromJson);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // Generic POST request
  Future<ApiResponse<T>> post<T>(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    required T Function(Map<String, dynamic>) fromJson,
    String? customIsmartValue, // Optional per-request ismart value
  }) async {
    try {
      print('🌐 ApiService: POST request to $endpoint');

      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      final response = await _dio.post(
        endpoint,
        data: data,
        queryParameters: queryParameters,
        options:
            customIsmartValue != null
                ? Options(headers: {'ismart': customIsmartValue})
                : null,
      );

      print('🌐 ApiService: Response status: ${response.statusCode}');

      final result = _handleSingleResponse<T>(response, fromJson);
      return result;
    } catch (e) {
      throw _handleError(e);
    }
  }

  // POST request for simple responses (no data parsing needed)
  Future<ApiResponse<Map<String, dynamic>>> postSimple(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    String? customIsmartValue, // Optional per-request ismart value
    Duration? receiveTimeout, // Override for slow endpoints (e.g. card creation)
  }) async {
    try {
      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      // Some provider-backed endpoints (card creation/activation) legitimately
      // take longer than the global 30s receive timeout because the backend is
      // waiting on a third party (StroWallet). Allow callers to raise it just
      // for those requests instead of loosening the default for everything.
      Options? options;
      if (customIsmartValue != null || receiveTimeout != null) {
        options = Options(
          headers:
              customIsmartValue != null ? {'ismart': customIsmartValue} : null,
          receiveTimeout: receiveTimeout,
        );
      }

      final response = await _dio.post(
        endpoint,
        data: data,
        queryParameters: queryParameters,
        options: options,
      );

      return _handleSimpleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // Generic PUT request
  Future<ApiResponse<T>> put<T>(
    String endpoint, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    required T Function(Map<String, dynamic>) fromJson,
    String? customIsmartValue, // Optional per-request ismart value
  }) async {
    try {
      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      final response = await _dio.put(
        endpoint,
        data: data,
        queryParameters: queryParameters,
        options:
            customIsmartValue != null
                ? Options(headers: {'ismart': customIsmartValue})
                : null,
      );

      return _handleSingleResponse<T>(response, fromJson);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // Generic DELETE request
  Future<ApiResponse<Map<String, dynamic>>> delete(
    String endpoint, {
    Map<String, dynamic>? queryParameters,
    String? customIsmartValue, // Optional per-request ismart value
  }) async {
    try {
      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      final response = await _dio.delete(
        endpoint,
        queryParameters: queryParameters,
        options:
            customIsmartValue != null
                ? Options(headers: {'ismart': customIsmartValue})
                : null,
      );

      return _handleSimpleResponse(response);
    } catch (e) {
      throw _handleError(e);
    }
  }

  // File upload
  Future<ApiResponse<T>> uploadFile<T>(
    String endpoint, {
    required String filePath,
    required String fieldName,
    Map<String, dynamic>? additionalData,
    required T Function(Map<String, dynamic>) fromJson,
    ProgressCallback? onSendProgress,
    String? customIsmartValue, // Optional per-request ismart value
  }) async {
    try {
      await _checkConnectivity();
      _validateIsmartHeader(customIsmartValue);

      final formData = FormData.fromMap({
        fieldName: await MultipartFile.fromFile(filePath),
        ...?additionalData,
      });

      final response = await _dio.post(
        endpoint,
        data: formData,
        onSendProgress: onSendProgress,
        options:
            customIsmartValue != null
                ? Options(headers: {'ismart': customIsmartValue})
                : null,
      );

      return _handleSingleResponse<T>(response, fromJson);
    } catch (e) {
      throw _handleError(e);
    }
  }

  /// Validate that ismart header is available when needed
  void _validateIsmartHeader(String? customValue) {
    if (customValue == null && _ismartValue == null) {
      print(
        '⚠️ ApiService: Warning - No ismart header value available. This may cause API requests to fail.',
      );
    }
  }

  // Response handlers (keeping existing implementations)
  ApiResponse<List<T>> _handleListResponse<T>(
    Response response,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    final responseData = response.data as Map<String, dynamic>;

    if (responseData['success'] == true) {
      final dataList = responseData['data'] as List<dynamic>?;
      final items =
          dataList
              ?.map((item) => fromJson(item as Map<String, dynamic>))
              .toList() ??
          [];

      return ApiResponse<List<T>>(
        success: true,
        message: responseData['message'] ?? 'Success',
        data: items,
        statusCode: response.statusCode,
      );
    } else {
      throw ServerException(
        message: responseData['message'] ?? 'Unknown error',
        statusCode: response.statusCode,
        errors: responseData['errors'],
      );
    }
  }

  ApiResponse<T> _handleSingleResponse<T>(
    Response response,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    final responseData = response.data as Map<String, dynamic>;

    if (responseData['success'] == true) {
      // For single response, always pass the entire responseData to fromJson
      // This allows the model to handle the structure as needed
      final data = fromJson(responseData);

      return ApiResponse<T>(
        success: true,
        message: responseData['message'] ?? 'Success',
        data: data,
        statusCode: response.statusCode,
      );
    } else {
      throw ServerException(
        message: responseData['message'] ?? 'Unknown error',
        statusCode: response.statusCode,
        errors: responseData['errors'],
      );
    }
  }

  ApiResponse<Map<String, dynamic>> _handleSimpleResponse(Response response) {
    final responseData = response.data as Map<String, dynamic>;

    if (responseData['success'] == true) {
      return ApiResponse<Map<String, dynamic>>(
        success: true,
        message: responseData['message'] ?? 'Success',
        // Backend sometimes returns `data: []` (an empty PHP array serializes
        // to a JSON array, not an object). Guard the cast so a List never
        // crashes the parse — treat any non-object as null.
        data: responseData['data'] is Map<String, dynamic>
            ? responseData['data'] as Map<String, dynamic>
            : null,
        statusCode: response.statusCode,
      );
    } else {
      throw ServerException(
        message: responseData['message'] ?? 'Unknown error',
        statusCode: response.statusCode,
        errors: responseData['errors'],
      );
    }
  }

  // Error handling (keeping existing implementation)
  ApiException _handleError(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return const NetworkException(
            message:
                'Connection timeout. Please check your internet connection.',
          );
        case DioExceptionType.badResponse:
          return _handleBadResponse(error);
        case DioExceptionType.cancel:
          return const ServerException(message: 'Request was cancelled');
        case DioExceptionType.connectionError:
          return const NetworkException(
            message:
                'No internet connection. Please check your network settings.',
          );
        default:
          return ServerException(message: 'Unexpected error: ${error.message}');
      }
    }

    if (error is ApiException) {
      return error;
    }

    return ServerException(message: 'Unexpected error: $error');
  }

  ApiException _handleBadResponse(DioException error) {
    final statusCode = error.response?.statusCode;
    final responseData = error.response?.data;

    switch (statusCode) {
      case 400:
        return ValidationException(
          message: _extractErrorMessage(responseData) ?? 'Invalid request',
          errors: _extractErrors(responseData),
        );
      case 401:
        return const AuthenticationException(
          message: 'Authentication failed. Please login again.',
        );
      case 403:
        return const AuthorizationException(
          message:
              'Access denied. You don\'t have permission to perform this action.',
        );
      case 404:
        return const ServerException(message: 'Resource not found');
      case 422:
        return ValidationException(
          message: _extractErrorMessage(responseData) ?? 'Validation failed',
          errors: _extractErrors(responseData),
        );
      case 500:
        return const ServerException(
          message: 'Internal server error. Please try again later.',
        );
      default:
        return ServerException(
          message:
              _extractErrorMessage(responseData) ??
              'Server error (${statusCode ?? 'Unknown'})',
          statusCode: statusCode,
        );
    }
  }

  String? _extractErrorMessage(dynamic responseData) {
    if (responseData is Map<String, dynamic>) {
      return responseData['message'] as String?;
    }
    return null;
  }

  Map<String, dynamic>? _extractErrors(dynamic responseData) {
    if (responseData is Map<String, dynamic>) {
      final errors = responseData['errors'];
      return errors is Map<String, dynamic> ? errors : null;
    }
    return null;
  }

  // Simplified connectivity check - removed problematic connectivity_plus dependency
  Future<void> _checkConnectivity() async {
    // Skip connectivity check for now - let the actual HTTP request determine connectivity
    // This avoids false negatives from the connectivity_plus package
    print(
      '🌐 ApiService: Skipping connectivity check, proceeding with request',
    );
  }
}
