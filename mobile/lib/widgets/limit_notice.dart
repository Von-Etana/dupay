// lib/widgets/limit_notice.dart

import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

/// Shows a dedicated dialog when a transaction was refused because the user hit
/// their daily limit, and reports whether it handled the message.
///
/// The backend returns a full explanation — the limit, how much has been spent
/// today, and what's left — but every screen was rendering it in a SnackBar
/// that vanishes after a few seconds. For a message this long, and one the user
/// needs to act on, that reads as "the transaction just failed" with no reason.
///
/// Call it before the screen's normal error handling:
///
///     if (!showLimitNoticeIfNeeded(context, response.message)) {
///       ...existing snackbar...
///     }
///
/// Returns false for any other error so callers keep their current behaviour.
bool showLimitNoticeIfNeeded(BuildContext context, String? message) {
  final text = (message ?? '').trim();
  if (text.isEmpty) return false;

  final lower = text.toLowerCase();
  final isLimit = lower.contains('daily transaction limit') ||
      lower.contains('daily transfer limit') ||
      lower.contains('daily limit');
  if (!isLimit) return false;

  // Unverified users are told to verify; surface that as the emphasis.
  final suggestsVerification = lower.contains('verify');

  showDialog<void>(
    context: context,
    builder: (ctx) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      titlePadding: const EdgeInsets.fromLTRB(24, 24, 24, 8),
      title: Row(children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: const BoxDecoration(
            color: Color(0xFFFFF4E0),
            shape: BoxShape.circle,
          ),
          child: const Icon(Icons.speed_rounded, color: Color(0xFFC98A00)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Text(
            'Daily limit reached',
            style: TextStyle(fontSize: 18.sp, fontWeight: FontWeight.bold),
          ),
        ),
      ]),
      content: Text(
        text,
        style: TextStyle(fontSize: 14.sp, height: 1.45, color: Colors.black87),
      ),
      actionsPadding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(ctx).pop(),
          child: Text(
            suggestsVerification ? 'Got it' : 'OK',
            style: const TextStyle(
                color: Color(0xFF34544A), fontWeight: FontWeight.bold),
          ),
        ),
      ],
    ),
  );

  return true;
}
