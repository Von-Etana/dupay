// lib/core/utils/phone_validator.dart
//
// Nigerian MSISDN validation shared by the airtime and data purchase flows.
// Both used to forward whatever was typed straight to the provider, so a
// number like "777777777" only failed server-side (or, worse, silently debited
// against a bogus number). Validate locally and tell the user up front.
//
// Local numbers only — the app is Nigeria-only, so there's no country code to
// handle. Everything is the plain 11-digit 0XXXXXXXXXX form.

/// Strips spaces, dashes and brackets so "0806 123 4567" is accepted.
String _digitsOnly(String input) => input.replaceAll(RegExp(r'[^0-9]'), '');

/// Cleans user input down to bare digits. Nothing is added or guessed — a
/// number that isn't already the full 11-digit local form is invalid, not
/// something to repair. Typos must fail here rather than be turned into a
/// well-formed number that fires off a real request to the provider.
///
///   08031234567    -> 08031234567
///   0803 123 4567  -> 08031234567
///   8031234567     -> 8031234567   (stays 10 digits, so it fails validation)
String normalizeNigerianPhone(String input) => _digitsOnly(input);

/// Every Nigerian mobile number is 11 digits and starts with
/// 070/071/080/081/090/091 — i.e. 0, then 7/8/9, then 0/1. Landlines and short
/// codes can't receive airtime or data, so they're rejected too.
final RegExp _nigerianMobile = RegExp(r'^0[789][01]\d{8}$');

bool isValidNigerianPhone(String input) =>
    _nigerianMobile.hasMatch(normalizeNigerianPhone(input));

/// Returns null when [input] is a usable mobile number, otherwise a message
/// that can be shown directly to the user.
String? validateNigerianPhone(String? input) {
  final raw = (input ?? '').trim();
  if (raw.isEmpty) return 'Please enter a phone number';

  final normalized = normalizeNigerianPhone(raw);

  if (RegExp(r'[^0-9\s()\-]').hasMatch(raw)) {
    return 'Incorrect phone number — digits only';
  }
  if (normalized.length != 11) {
    return 'Incorrect phone number — it must be 11 digits (e.g. 08031234567)';
  }
  if (!_nigerianMobile.hasMatch(normalized)) {
    return 'Incorrect phone number — enter a valid Nigerian mobile number '
        '(e.g. 08031234567)';
  }
  return null;
}
