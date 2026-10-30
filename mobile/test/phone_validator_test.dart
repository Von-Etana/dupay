import 'package:dupay/core/utils/phone_validator.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('isValidNigerianPhone', () {
    test('accepts local 11-digit numbers', () {
      expect(isValidNigerianPhone('08031234567'), isTrue);
      expect(isValidNigerianPhone('07011234567'), isTrue);
      expect(isValidNigerianPhone('09131234567'), isTrue);
      expect(isValidNigerianPhone('0806 123 4567'), isTrue);
    });

    test('rejects country-code forms — local numbers only', () {
      expect(isValidNigerianPhone('+2348031234567'), isFalse);
      expect(isValidNigerianPhone('2348031234567'), isFalse);
    });

    test('rejects a missing leading zero instead of repairing it', () {
      expect(isValidNigerianPhone('8031234567'), isFalse);
    });

    test('rejects junk and wrong-length numbers', () {
      expect(isValidNigerianPhone('777777777'), isFalse);  // the reported case
      expect(isValidNigerianPhone('07777777777'), isFalse); // bad prefix
      expect(isValidNigerianPhone('0803123456'), isFalse);  // too short
      expect(isValidNigerianPhone('080312345678'), isFalse); // too long
      expect(isValidNigerianPhone('01234567890'), isFalse); // landline prefix
      expect(isValidNigerianPhone(''), isFalse);
    });
  });

  group('normalizeNigerianPhone', () {
    test('strips separators without changing the digits', () {
      for (final input in [
        '08031234567',
        '0803 123 4567',
        '0803-123-4567',
        '(0803) 123 4567',
      ]) {
        expect(normalizeNigerianPhone(input), '08031234567', reason: input);
      }
    });

    test('never invents digits', () {
      expect(normalizeNigerianPhone('8031234567'), '8031234567');
      expect(normalizeNigerianPhone('777777777'), '777777777');
    });
  });

  group('validateNigerianPhone', () {
    test('returns null for a valid number', () {
      expect(validateNigerianPhone('08031234567'), isNull);
    });

    test('asks for a number when empty', () {
      expect(validateNigerianPhone(''), 'Please enter a phone number');
      expect(validateNigerianPhone(null), 'Please enter a phone number');
    });

    test('reports an incorrect number otherwise', () {
      expect(validateNigerianPhone('777777777'), contains('Incorrect phone number'));
      expect(validateNigerianPhone('07777777777'), contains('Incorrect phone number'));
      expect(validateNigerianPhone('8031234567'), contains('Incorrect phone number'));
      expect(
          validateNigerianPhone('+2348031234567'), contains('Incorrect phone number'));
    });
  });
}
