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
    final rawData = responseData ?? const <String, dynamic>{};
    // Some iOS responses arrive with the transaction nested under `data` (or
    // `transaction`) while Android receives the flattened payload. Normalize
    // both shapes before deciding whether a transaction succeeded.
    final nested = rawData['transaction'] ?? rawData['data'];
    final data =
        nested is Map
            ? <String, dynamic>{
              ...rawData,
              ...Map<String, dynamic>.from(nested),
            }
            : rawData;
    final rawStatus =
        (data['status'] ?? data['transaction_status'] ?? data['result'])
            ?.toString()
            .trim()
            .toLowerCase() ??
        '';
    final messageLower = responseMessage.toLowerCase();
    final reference = data['reference']?.toString() ?? '';
    final sessionId =
        data['session_id']?.toString() ?? data['sessionId']?.toString();
    final initialBalance = data['initial_balance']?.toString() ?? '';
    final finalBalance = data['final_balance']?.toString() ?? '';

    final requiresHistoryCheck =
        _unconfirmed.contains(rawStatus) ||
        statusCode == 202 ||
        data['request_id'] != null ||
        messageLower.contains('could not confirm') ||
        messageLower.contains('confirmation failed') ||
        messageLower.contains('timeout') ||
        messageLower.contains('timed out') ||
        (!responseSuccess && messageLower.contains('internal server error')) ||
        (statusCode != null && statusCode >= 500) ||
        (!responseSuccess && statusCode == null);

    // 1. Trust confirmed provider success immediately
    // A successful provider response is authoritative unless it explicitly
    // says the transaction is still pending. This avoids iOS treating a
    // harmless error message attached to a successful payload as a failure.
    if (responseSuccess &&
        (!_unconfirmed.contains(rawStatus)) &&
        (!_failed.contains(rawStatus))) {
      return ResolvedTransactionResult(
        success: true,
        status: 'success',
        message:
            responseMessage.isEmpty ? fallbackSuccessMessage : responseMessage,
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
        message:
            responseMessage.isEmpty
                ? 'Transaction failed. Please try again.'
                : responseMessage,
        reference: reference,
        sessionId: sessionId,
      );
    }

    // 3. For delayed, unconfirmed, or server-error responses, poll recent transaction history
    final expectedAmount =
        double.tryParse(amount.replaceAll(RegExp(r'[^0-9.]'), '')) ?? -1;
    final normalizedHints =
        descriptionHints
            .map((hint) => hint.trim().toLowerCase())
            .where((hint) => hint.isNotEmpty)
            .toList();

    for (var attempt = 0; attempt < 6; attempt++) {
      if (attempt > 0) await Future.delayed(const Duration(seconds: 1));
      try {
        final history = await TransactionRepository().searchTransactions(
          page: 1,
          limit: 30,
        );
        for (final transaction in history.data ?? const []) {
          final sameReference =
              reference.isNotEmpty &&
              transaction.reference?.toLowerCase() == reference.toLowerCase();
          final description = transaction.description?.toLowerCase() ?? '';
          final serviceType =
              transaction.metadata?['service_type']?.toString().toLowerCase() ??
              '';
          final matchesHint =
              normalizedHints.isEmpty ||
              normalizedHints.any(
                (hint) =>
                    description.contains(hint) || serviceType.contains(hint),
              );
          final sameTransaction =
              (transaction.amountValue - expectedAmount).abs() < 0.01 &&
              transaction.createdAt.isAfter(
                submittedAt.subtract(const Duration(seconds: 90)),
              ) &&
              matchesHint;
          if (!sameReference && !sameTransaction) continue;

          final historyStatus = transaction.status.trim().toLowerCase();
          final historySessionId =
              transaction.metadata?['session_id']?.toString() ??
              transaction.metadata?['sessionId']?.toString();
          final resolvedSessionId =
              (historySessionId != null && historySessionId.isNotEmpty)
                  ? historySessionId
                  : ((sessionId != null && sessionId.isNotEmpty)
                      ? sessionId
                      : null);

          if (_successful.contains(historyStatus)) {
            return ResolvedTransactionResult(
              success: true,
              status: 'success',
              message: fallbackSuccessMessage,
              reference: transaction.reference ?? reference,
              sessionId: resolvedSessionId,
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
              message:
                  responseMessage.isEmpty
                      ? 'Transaction failed. Please try again.'
                      : responseMessage,
              reference: transaction.reference ?? reference,
              sessionId: resolvedSessionId,
            );
          }
        }
      } catch (_) {
        // Keep checking history; never repeat the original money request.
      }
    }

    // A timeout/server error does not prove that money failed. Keep the result
    // pending so the receipt can be reconciled from transaction history later,
    // instead of showing a false failure on iOS when the transfer completed.
    return ResolvedTransactionResult(
      success: false,
      status: 'pending',
      message:
          'Transaction submitted. We could not confirm the result yet. Check transaction history shortly.',
      reference: reference,
      sessionId: (sessionId != null && sessionId.isNotEmpty) ? sessionId : null,
    );
  }
}
