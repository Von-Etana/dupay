// lib/core/interceptors/retry_interceptor.dart
import 'package:dio/dio.dart';
import 'package:logger/logger.dart';

/// Retries failed requests — but ONLY when it is SAFE to do so.
///
/// CRITICAL money-safety rules:
///  1. NEVER retry on a 5xx response. A 500 means the server may have
///     ALREADY completed the work (e.g. money was sent) before erroring.
///     Retrying re-runs a non-idempotent operation and double/triple-sends.
///  2. ONLY retry idempotent requests (GET/HEAD), OR requests the caller
///     has explicitly marked safe via `extra['retryable'] == true`.
///  3. Retry only on genuine transport failures (timeouts / connection
///     errors) where the request most likely never reached the server.
class RetryInterceptor extends Interceptor {
  final int maxRetries;
  final Duration delay;
  final Logger _logger = Logger();

  RetryInterceptor({
    this.maxRetries = 3,
    this.delay = const Duration(seconds: 1),
  });

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (_shouldRetry(err) && err.requestOptions.extra['retryCount'] == null) {
      err.requestOptions.extra['retryCount'] = 0;
    }

    final retryCount = err.requestOptions.extra['retryCount'] as int? ?? 0;

    if (_shouldRetry(err) && retryCount < maxRetries) {
      _logger.w(
        'Retrying ${err.requestOptions.method} request (${retryCount + 1}/$maxRetries)',
      );

      err.requestOptions.extra['retryCount'] = retryCount + 1;

      await Future.delayed(delay * (retryCount + 1));

      try {
        final response = await Dio().fetch(err.requestOptions);
        handler.resolve(response);
      } catch (e) {
        if (e is DioException) {
          onError(e, handler);
        } else {
          handler.next(err);
        }
      }
    } else {
      handler.next(err);
    }
  }

  bool _shouldRetry(DioException err) {
    // 1. Only retry on transport-level failures where the request most
    //    likely never reached the server. NEVER retry on a 5xx response —
    //    the server may have already processed it.
    final bool isTransportFailure =
        err.type == DioExceptionType.connectionTimeout ||
        err.type == DioExceptionType.connectionError;
    // NOTE: sendTimeout / receiveTimeout are intentionally EXCLUDED for
    // unsafe methods because the request may already have reached the server.

    if (!isTransportFailure) return false;

    // 2. Only retry SAFE/idempotent requests.
    final method = err.requestOptions.method.toUpperCase();
    final bool isIdempotent = method == 'GET' || method == 'HEAD';

    // 3. Allow a caller to explicitly opt a request in as retry-safe
    //    (e.g. a backend endpoint protected by an idempotency key).
    final bool explicitlyRetryable =
        err.requestOptions.extra['retryable'] == true;

    return isIdempotent || explicitlyRetryable;
  }
}
