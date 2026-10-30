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

  final formatter = NumberFormat.decimalPattern('en_NG');
  return '${formatter.format(value)}${decimalDigits == 0 ? '' : ''}';
}
