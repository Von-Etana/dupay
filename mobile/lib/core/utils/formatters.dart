import 'package:intl/intl.dart';

/// Formats a numeric amount with thousand separators and a currency symbol.
/// Accepts String or num and gracefully handles invalid inputs.
String formatCurrency(
  dynamic amount, {
  String symbol = '₦',
  int decimalDigits = 2,
}) {
  if (amount == null) return '${symbol}0.00';

  double? value;
  if (amount is num) {
    value = amount.toDouble();
  } else if (amount is String) {
    final cleaned = amount.replaceAll(RegExp(r'[^0-9.\-]'), '');
    value = double.tryParse(cleaned);
  }

  if (value == null) return '${symbol}0.00';

  final formatter = NumberFormat.currency(
    locale: 'en_NG',
    symbol: symbol,
    decimalDigits: decimalDigits,
  );
  return formatter.format(value);
}

/// Formats an amount using the currency actually attached to the transaction.
/// This keeps receipts, history, and notification previews from defaulting to
/// the naira symbol when the transaction is in USD, EUR, or another currency.
String formatCurrencyCode(
  dynamic amount,
  String? currency, {
  int decimalDigits = 2,
}) {
  final code = currency?.trim().toUpperCase();
  final symbol =
      code == null || code.isEmpty || code == 'NGN'
          ? '₦'
          : code == 'USD'
          ? '\$'
          : code == 'EUR'
          ? '€'
          : code == 'GBP'
          ? '£'
          : code;
  return formatCurrency(amount, symbol: symbol, decimalDigits: decimalDigits);
}

/// Formats a numeric amount with thousand separators (no symbol).
String formatNumber(dynamic amount, {int decimalDigits = 2}) {
  if (amount == null) return '0.00';

  double? value;
  if (amount is num) {
    value = amount.toDouble();
  } else if (amount is String) {
    final cleaned = amount.replaceAll(RegExp(r'[^0-9.\-]'), '');
    value = double.tryParse(cleaned);
  }

  if (value == null) return '0.00';

  final formatter =
      NumberFormat.decimalPattern('en_NG')
        ..minimumFractionDigits = decimalDigits
        ..maximumFractionDigits = decimalDigits;
  return formatter.format(value);
}
