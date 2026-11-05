import '../../repositories/transaction_repository.dart';

class ResolvedTransactionResult {
  final bool success;
  final String status;
  final String message;
  final String reference;
  final String? sessionId;
  final String initialBalance;
  final String finalBalance;

  const ResolvedTransactionResult({
    required this.success,
    required this.status,
    required this.message,
    required this.reference,
    this.sessionId,
    this.initialBalance = '',
    this.finalBalance = '',
  });
}

class TransactionResultResolver {
  static const _successful = {
    'success',
    'successful',
    'completed',
    'complete',
    'approved',
    '1',
  };
  static const _failed = {
    'failed',
    'failure',
    'unsuccessful',
    'declined',
    'rejected',
    'cancelled',
    'canceled',
    'refund',
    'refunded',
    '0',
  };
  static const _unconfirmed = {
    'pending',
    'processing',
    'queued',
    'in_progress',
    'in progress',
  };

  static Future<ResolvedTransactionResult> resolve({
    required bool responseSuccess,
    required String responseMessage,
    required Map<String, dynamic>? responseData,
    required int? statusCode,
    required String amount,
    required DateTime submittedAt,
    required List<String> descriptionHints,
    String fallbackSuccessMessage = 'Transaction completed successfully.',
  }) async {
    final data = responseData ?? const <String, dynamic>{};
    final rawStatus = data['status']?.toString().trim().toLowerCase() ?? '';
    final messageLower = responseMessage.toLowerCase();
    final reference = data['reference']?.toString() ?? '';
    final sessionId = data['session_id']?.toString() ?? data['sessionId']?.toString();
    final initialBalance = data['initial_balance']?.toString() ?? '';
    final finalBalance = data['final_balance']?.toString() ?? '';

    final requiresHistoryCheck = _unconfirmed.contains(rawStatus) ||
        statusCode == 202 ||
        data['request_id'] != null ||
        messageLower.contains('could not confirm') ||
        messageLower.contains('confirmation failed') ||
        messageLower.contains('timeout') ||
        messageLower.contains('timed out') ||
        messageLower.contains('internal server error') ||
        (statusCode != null && statusCode >= 500) ||
        (!responseSuccess && statusCode == null);

    // 1. Trust confirmed provider success immediately
    if (responseSuccess && !requiresHistoryCheck) {
      return ResolvedTransactionResult(
        success: true,
        status: 'success',
        message: responseMessage.isEmpty
            ? fallbackSuccessMessage
            : responseMessage,
        reference: reference,
        sessionId: sessionId,
        initialBalance: initialBalance,
        finalBalance: finalBalance,
      );
    }

    // 2. Reject definitive client validation errors without polling (e.g. 400 Bad Request, Insufficient Funds, Incorrect PIN)
    if (!requiresHistoryCheck &&
        (_failed.contains(rawStatus) ||
            (statusCode != null && statusCode >= 400 && statusCode < 500))) {
      return ResolvedTransactionResult(
        success: false,
        status: 'failed',
        message: responseMessage.isEmpty
            ? 'Transaction failed. Please try again.'
            : responseMessage,
        reference: reference,
        sessionId: sessionId,
      );
    }

    // 3. For delayed, unconfirmed, or server-error responses, poll recent transaction history
    final expectedAmount = double.tryParse(
          amount.replaceAll(RegExp(r'[^0-9.]'), ''),
        ) ??
        -1;
    final normalizedHints = descriptionHints
        .map((hint) => hint.trim().toLowerCase())
        .where((hint) => hint.isNotEmpty)
        .toList();

    for (var attempt = 0; attempt < 8; attempt++) {
      if (attempt > 0) await Future.delayed(const Duration(seconds: 2));
      try {
        final history = await TransactionRepository()
            .searchTransactions(page: 1, limit: 30);
        for (final transaction in history.data ?? const []) {
          final sameReference = reference.isNotEmpty &&
              transaction.reference?.toLowerCase() == reference.toLowerCase();
          final description = transaction.description?.toLowerCase() ?? '';
          final serviceType =
              transaction.metadata?['service_type']?.toString().toLowerCase() ??
                  '';
          final matchesHint = normalizedHints.isEmpty ||
              normalizedHints.any(
                (hint) => description.contains(hint) || serviceType.contains(hint),
              );
          final sameTransaction = (transaction.amountValue - expectedAmount).abs() < 0.01 &&
              transaction.createdAt.isAfter(
                submittedAt.subtract(const Duration(seconds: 20)),
              ) &&
              matchesHint;
          if (!sameReference && !sameTransaction) continue;

          final historyStatus = transaction.status.trim().toLowerCase();
          if (_successful.contains(historyStatus)) {
            return ResolvedTransactionResult(
              success: true,
              status: 'success',
              message: fallbackSuccessMessage,
              reference: transaction.reference ?? reference,
              sessionId: sessionId ?? transaction.reference,
              initialBalance:
                  transaction.metadata?['initial_balance']?.toString() ?? '',
              finalBalance:
                  transaction.metadata?['final_balance']?.toString() ?? '',
            );
          }
          if (_failed.contains(historyStatus)) {
            return ResolvedTransactionResult(
              success: false,
              status: 'failed',
              message: responseMessage.isEmpty
                  ? 'Transaction failed. Please try again.'
                  : responseMessage,
              reference: transaction.reference ?? reference,
              sessionId: sessionId,
            );
          }
        }
      } catch (_) {
        // Keep checking history; never repeat the original money request.
      }
    }

    // 4. Only show failure after no successful record can be found in history
    return ResolvedTransactionResult(
      success: false,
      status: 'failed',
      message:
          'We could not confirm the transaction result. Check transaction history before trying again.',
      reference: reference,
      sessionId: sessionId,
    );
  }
}
