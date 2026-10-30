// // lib/screen/more/transaction_pin_setup_screen.dart
// //
// // Modern redesign of the post-signup "set transaction PIN" screen.
// // Layout preserved: header pill, big title, dots, primary button at bottom,
// // two-step flow (enter → confirm). What's new is the visual treatment:
// //
// //   • Brand-coloured gradient hero with a security icon
// //   • Step indicator ("Step 1 of 2" / "Step 2 of 2") with progress bar
// //   • Larger, brand-coloured PIN dots inside framed boxes
// //   • Subtle helper copy under the title explaining what's happening
// //   • Reassurance footer ("Your PIN is encrypted on this device")
// //   • Modern brand-coloured primary button with proper disabled state

// import 'package:flutter/material.dart';
// import 'package:flutter/services.dart';
// import 'package:flutter_screenutil/flutter_screenutil.dart';
// import 'package:hooks_riverpod/hooks_riverpod.dart';
// import '../../providers/transaction_provider.dart';
// import '../../providers/auth_provider.dart';

// class TransactionPinSetupScreen extends ConsumerStatefulWidget {
//   const TransactionPinSetupScreen({super.key});

//   @override
//   ConsumerState<TransactionPinSetupScreen> createState() =>
//       _TransactionPinSetupScreenState();
// }

// class _TransactionPinSetupScreenState
//     extends ConsumerState<TransactionPinSetupScreen>
//     with SingleTickerProviderStateMixin {
//   // ── Brand palette ──────────────────────────────────────────────────────────
//   static const Color _brand     = Color(0xFF34544A);
//   static const Color _brandSoft = Color(0xFF7CA183);
//   static const Color _surface   = Color(0xFFF6F8F7);
//   static const Color _border    = Color(0xFFE5EAE7);

//   final TextEditingController _controller = TextEditingController();
//   final FocusNode _focusNode = FocusNode();

//   String _firstPin = '';
//   bool _isConfirmStep = false;
//   bool _isProcessing = false;
//   bool _mismatchShake = false;

//   late final AnimationController _shakeController;

//   @override
//   void initState() {
//     super.initState();
//     _shakeController = AnimationController(
//       vsync: this,
//       duration: const Duration(milliseconds: 350),
//     );
//     _controller.addListener(_onPinChanged);
//     // Bring up native keyboard automatically.
//     WidgetsBinding.instance.addPostFrameCallback((_) {
//       _focusNode.requestFocus();
//     });
//   }

//   @override
//   void dispose() {
//     _controller.removeListener(_onPinChanged);
//     _controller.dispose();
//     _focusNode.dispose();
//     _shakeController.dispose();
//     super.dispose();
//   }

//   void _onPinChanged() {
//     setState(() {});
//     if (_controller.text.length == 4 && !_isProcessing) {
//       // Tiny delay so the final dot animates in before transitioning.
//       Future.delayed(const Duration(milliseconds: 120), _handlePinFilled);
//     }
//   }

//   void _handlePinFilled() {
//     if (!mounted) return;
//     if (!_isConfirmStep) {
//       // Move on to confirm step.
//       setState(() {
//         _firstPin = _controller.text;
//         _isConfirmStep = true;
//         _controller.clear();
//       });
//       _focusNode.requestFocus();
//     } else {
//       // Confirm step — check match.
//       if (_controller.text == _firstPin) {
//         _handleSubmit();
//       } else {
//         // Shake + clear, then move back to step 1.
//         _shakeController.forward(from: 0);
//         setState(() => _mismatchShake = true);
//         Future.delayed(const Duration(milliseconds: 400), () {
//           if (!mounted) return;
//           setState(() => _mismatchShake = false);
//         });
//         _showError('PINs do not match. Please try again.');
//         setState(() {
//           _firstPin = '';
//           _isConfirmStep = false;
//           _controller.clear();
//         });
//         _focusNode.requestFocus();
//       }
//     }
//   }

//   void _showError(String message) {
//     HapticFeedback.mediumImpact();
//     ScaffoldMessenger.of(context).showSnackBar(
//       SnackBar(
//         content: Text(message),
//         backgroundColor: const Color(0xFFD64545),
//         behavior: SnackBarBehavior.floating,
//         margin: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
//         shape: RoundedRectangleBorder(
//           borderRadius: BorderRadius.circular(12.r),
//         ),
//         duration: const Duration(seconds: 2),
//       ),
//     );
//   }

//   void _resetToFirstStep() {
//     setState(() {
//       _firstPin = '';
//       _isConfirmStep = false;
//       _controller.clear();
//     });
//     _focusNode.requestFocus();
//   }

//   Future<void> _handleSubmit() async {
//     if (_isProcessing) return;
//     setState(() => _isProcessing = true);

//     try {
//       final transactionNotifier =
//           ref.read(transactionNotifierProvider.notifier);
//       final response =
//           await transactionNotifier.createTransactionPin(pin: _firstPin);

//       if (!mounted) return;

//       if (response.success) {
//         final authNotifier = ref.read(authNotifierProvider.notifier);
//         await authNotifier.forceRefreshUserInfo();

//         if (!mounted) return;
//         showDialog(
//           context: context,
//           barrierDismissible: false,
//           builder: (context) => Dialog(
//             shape: RoundedRectangleBorder(
//               borderRadius: BorderRadius.circular(20.r),
//             ),
//             child: Padding(
//               padding: EdgeInsets.fromLTRB(24.w, 28.h, 24.w, 20.h),
//               child: Column(
//                 mainAxisSize: MainAxisSize.min,
//                 children: [
//                   Container(
//                     width: 64.w,
//                     height: 64.h,
//                     decoration: BoxDecoration(
//                       color: _brand.withOpacity(0.10),
//                       shape: BoxShape.circle,
//                     ),
//                     child: Icon(Icons.check_rounded,
//                         size: 36.sp, color: _brand),
//                   ),
//                   SizedBox(height: 16.h),
//                   Text(
//                     'PIN Set Successfully',
//                     style: TextStyle(
//                       fontSize: 18.sp,
//                       fontWeight: FontWeight.w700,
//                       color: Colors.grey[900],
//                     ),
//                   ),
//                   SizedBox(height: 8.h),
//                   Text(
//                     'Your transaction PIN is now active. Keep it secret — '
//                     'you\'ll need it to authorize payments.',
//                     textAlign: TextAlign.center,
//                     style: TextStyle(
//                       fontSize: 13.sp,
//                       color: Colors.grey[600],
//                       height: 1.4,
//                     ),
//                   ),
//                   SizedBox(height: 20.h),
//                   SizedBox(
//                     width: double.infinity,
//                     height: 50.h,
//                     child: ElevatedButton(
//                       onPressed: () {
//                         Navigator.of(context).pop();
//                         Navigator.of(context).pushNamedAndRemoveUntil(
//                           '/homeNav',
//                           (route) => false,
//                         );
//                       },
//                       style: ElevatedButton.styleFrom(
//                         backgroundColor: _brand,
//                         foregroundColor: Colors.white,
//                         elevation: 0,
//                         shape: RoundedRectangleBorder(
//                           borderRadius: BorderRadius.circular(12.r),
//                         ),
//                       ),
//                       child: Text(
//                         'Continue',
//                         style: TextStyle(
//                           fontSize: 15.sp,
//                           fontWeight: FontWeight.w600,
//                         ),
//                       ),
//                     ),
//                   ),
//                 ],
//               ),
//             ),
//           ),
//         );
//       } else {
//         _showError(response.message ?? 'Failed to set transaction PIN');
//         _resetToFirstStep();
//       }
//     } catch (e) {
//       if (!mounted) return;
//       _showError('An error occurred: ${e.toString()}');
//       _resetToFirstStep();
//     } finally {
//       if (mounted) {
//         setState(() => _isProcessing = false);
//       }
//     }
//   }

//   // ─── UI ─────────────────────────────────────────────────────────────────────
//   @override
//   Widget build(BuildContext context) {
//     final pinLength = _controller.text.length;
//     final stepNum = _isConfirmStep ? 2 : 1;
//     final title    = _isConfirmStep ? 'Confirm your PIN' : 'Create your PIN';
//     final helperText = _isConfirmStep
//         ? 'Re-enter the same 4 digits to confirm.'
//         : 'Pick 4 digits you\'ll use to authorize every transaction.';

//     return Scaffold(
//       backgroundColor: Colors.white,
//       resizeToAvoidBottomInset: true,
//       body: SafeArea(
//         child: GestureDetector(
//           // Tap anywhere to refocus the hidden field (re-opens keyboard).
//           onTap: () => _focusNode.requestFocus(),
//           behavior: HitTestBehavior.opaque,
//           child: Column(
//             children: [
//               SizedBox(height: 12.h),

//               // ── Header row: back button (confirm step) + step pill ────────
//               Padding(
//                 padding: EdgeInsets.symmetric(horizontal: 16.w),
//                 child: Row(
//                   children: [
//                     SizedBox(
//                       width: 40.w,
//                       child: _isConfirmStep
//                           ? Material(
//                               color: _surface,
//                               shape: const CircleBorder(),
//                               child: InkWell(
//                                 customBorder: const CircleBorder(),
//                                 onTap: _isProcessing
//                                     ? null
//                                     : _resetToFirstStep,
//                                 child: Padding(
//                                   padding: EdgeInsets.all(8.r),
//                                   child: Icon(
//                                     Icons.arrow_back_rounded,
//                                     size: 20.sp,
//                                     color: _brand,
//                                   ),
//                                 ),
//                               ),
//                             )
//                           : const SizedBox.shrink(),
//                     ),
//                     Expanded(
//                       child: Center(
//                         child: Container(
//                           padding: EdgeInsets.symmetric(
//                             horizontal: 14.w,
//                             vertical: 6.h,
//                           ),
//                           decoration: BoxDecoration(
//                             color: _brand.withOpacity(0.10),
//                             borderRadius: BorderRadius.circular(20.r),
//                           ),
//                           child: Text(
//                             'Step $stepNum of 2',
//                             style: TextStyle(
//                               fontSize: 12.sp,
//                               fontWeight: FontWeight.w600,
//                               color: _brand,
//                               letterSpacing: 0.3,
//                             ),
//                           ),
//                         ),
//                       ),
//                     ),
//                     SizedBox(width: 40.w),
//                   ],
//                 ),
//               ),

//               SizedBox(height: 14.h),

//               // ── Progress bar (2 segments) ─────────────────────────────────
//               Padding(
//                 padding: EdgeInsets.symmetric(horizontal: 24.w),
//                 child: Row(
//                   children: [
//                     Expanded(
//                       child: AnimatedContainer(
//                         duration: const Duration(milliseconds: 250),
//                         height: 4.h,
//                         decoration: BoxDecoration(
//                           color: _brand,
//                           borderRadius: BorderRadius.circular(2.r),
//                         ),
//                       ),
//                     ),
//                     SizedBox(width: 6.w),
//                     Expanded(
//                       child: AnimatedContainer(
//                         duration: const Duration(milliseconds: 250),
//                         height: 4.h,
//                         decoration: BoxDecoration(
//                           color: _isConfirmStep
//                               ? _brand
//                               : const Color(0xFFE5EAE7),
//                           borderRadius: BorderRadius.circular(2.r),
//                         ),
//                       ),
//                     ),
//                   ],
//                 ),
//               ),

//               SizedBox(height: 32.h),

//               // ── Brand hero icon ───────────────────────────────────────────
//               Container(
//                 width: 76.w,
//                 height: 76.w,
//                 decoration: BoxDecoration(
//                   gradient: const LinearGradient(
//                     begin: Alignment.topLeft,
//                     end: Alignment.bottomRight,
//                     colors: [_brand, _brandSoft],
//                   ),
//                   shape: BoxShape.circle,
//                   boxShadow: [
//                     BoxShadow(
//                       color: _brand.withOpacity(0.22),
//                       blurRadius: 18,
//                       offset: const Offset(0, 8),
//                     ),
//                   ],
//                 ),
//                 child: Icon(
//                   _isConfirmStep
//                       ? Icons.lock_rounded
//                       : Icons.shield_outlined,
//                   color: Colors.white,
//                   size: 36.sp,
//                 ),
//               ),

//               SizedBox(height: 22.h),

//               // ── Title ─────────────────────────────────────────────────────
//               Padding(
//                 padding: EdgeInsets.symmetric(horizontal: 24.w),
//                 child: Text(
//                   title,
//                   style: TextStyle(
//                     fontSize: 24.sp,
//                     fontWeight: FontWeight.w700,
//                     color: Colors.grey[900],
//                     letterSpacing: -0.3,
//                   ),
//                 ),
//               ),
//               SizedBox(height: 8.h),

//               // ── Helper text ───────────────────────────────────────────────
//               Padding(
//                 padding: EdgeInsets.symmetric(horizontal: 32.w),
//                 child: Text(
//                   helperText,
//                   textAlign: TextAlign.center,
//                   style: TextStyle(
//                     fontSize: 13.sp,
//                     color: Colors.grey[600],
//                     height: 1.4,
//                   ),
//                 ),
//               ),

//               SizedBox(height: 36.h),

//               // ── PIN dot boxes (with shake on mismatch) ───────────────────
//               AnimatedBuilder(
//                 animation: _shakeController,
//                 builder: (_, child) {
//                   // simple horizontal shake
//                   final progress = _shakeController.value;
//                   final offset = _mismatchShake
//                       ? (8 *
//                           (1 - progress) *
//                           ((progress * 4).floor().isEven ? 1 : -1))
//                       : 0.0;
//                   return Transform.translate(
//                     offset: Offset(offset, 0),
//                     child: child,
//                   );
//                 },
//                 child: Row(
//                   mainAxisAlignment: MainAxisAlignment.center,
//                   children: List.generate(4, (index) {
//                     final filled = index < pinLength;
//                     return AnimatedContainer(
//                       duration: const Duration(milliseconds: 180),
//                       curve: Curves.easeOut,
//                       margin: EdgeInsets.symmetric(horizontal: 8.w),
//                       width: 56.w,
//                       height: 60.h,
//                       decoration: BoxDecoration(
//                         color: filled
//                             ? _brand.withOpacity(0.06)
//                             : _surface,
//                         border: Border.all(
//                           color: filled ? _brand : _border,
//                           width: filled ? 1.6 : 1,
//                         ),
//                         borderRadius: BorderRadius.circular(14.r),
//                       ),
//                       child: Center(
//                         child: AnimatedContainer(
//                           duration: const Duration(milliseconds: 160),
//                           curve: Curves.easeOut,
//                           width: filled ? 14.w : 12.w,
//                           height: filled ? 14.h : 12.h,
//                           decoration: BoxDecoration(
//                             shape: BoxShape.circle,
//                             color: filled
//                                 ? _brand
//                                 : Colors.transparent,
//                             border: filled
//                                 ? null
//                                 : Border.all(
//                                     color: Colors.grey.shade400,
//                                     width: 1.5,
//                                   ),
//                           ),
//                         ),
//                       ),
//                     );
//                   }),
//                 ),
//               ),

//               // Hidden text field that drives the native keyboard.
//               Opacity(
//                 opacity: 0,
//                 child: SizedBox(
//                   height: 1,
//                   child: TextField(
//                     controller: _controller,
//                     focusNode: _focusNode,
//                     autofocus: true,
//                     keyboardType: TextInputType.number,
//                     obscureText: true,
//                     enableInteractiveSelection: false,
//                     showCursor: false,
//                     inputFormatters: [
//                       FilteringTextInputFormatter.digitsOnly,
//                       LengthLimitingTextInputFormatter(4),
//                     ],
//                   ),
//                 ),
//               ),

//               SizedBox(height: 24.h),

//               // ── Reassurance footer ────────────────────────────────────────
//               Padding(
//                 padding: EdgeInsets.symmetric(horizontal: 32.w),
//                 child: Row(
//                   mainAxisAlignment: MainAxisAlignment.center,
//                   children: [
//                     Icon(Icons.lock_outline,
//                         size: 14.sp, color: Colors.grey[500]),
//                     SizedBox(width: 6.w),
//                     Flexible(
//                       child: Text(
//                         'Your PIN is encrypted — never share it with anyone.',
//                         style: TextStyle(
//                           fontSize: 11.5.sp,
//                           color: Colors.grey[600],
//                           fontWeight: FontWeight.w500,
//                         ),
//                       ),
//                     ),
//                   ],
//                 ),
//               ),

//               const Spacer(),

//               // ── Primary button ────────────────────────────────────────────
//               Padding(
//                 padding: EdgeInsets.symmetric(horizontal: 24.w),
//                 child: SizedBox(
//                   width: double.infinity,
//                   height: 56.h,
//                   child: ElevatedButton(
//                     onPressed: (pinLength == 4 && !_isProcessing)
//                         ? _handlePinFilled
//                         : null,
//                     style: ElevatedButton.styleFrom(
//                       backgroundColor: _brand,
//                       disabledBackgroundColor:
//                           _brand.withOpacity(0.35),
//                       foregroundColor: Colors.white,
//                       disabledForegroundColor:
//                           Colors.white.withOpacity(0.8),
//                       shape: RoundedRectangleBorder(
//                         borderRadius: BorderRadius.circular(14.r),
//                       ),
//                       elevation: 0,
//                     ),
//                     child: _isProcessing
//                         ? SizedBox(
//                             width: 22.w,
//                             height: 22.h,
//                             child: const CircularProgressIndicator(
//                               strokeWidth: 2.4,
//                               valueColor: AlwaysStoppedAnimation<Color>(
//                                   Colors.white),
//                             ),
//                           )
//                         : Row(
//                             mainAxisAlignment: MainAxisAlignment.center,
//                             children: [
//                               Text(
//                                 _isConfirmStep ? 'Confirm PIN' : 'Continue',
//                                 style: TextStyle(
//                                   fontSize: 16.sp,
//                                   fontWeight: FontWeight.w600,
//                                 ),
//                               ),
//                               SizedBox(width: 8.w),
//                               Icon(
//                                 _isConfirmStep
//                                     ? Icons.check_rounded
//                                     : Icons.arrow_forward_rounded,
//                                 size: 18.sp,
//                               ),
//                             ],
//                           ),
//                   ),
//                 ),
//               ),

//               SizedBox(height: 28.h),
//             ],
//           ),
//         ),
//       ),
//     );
//   }
// }


// lib/screen/more/transaction_pin_setup_screen.dart
//
// Modern redesign of the post-signup "set transaction PIN" screen.
// Layout preserved: header pill, big title, dots, primary button at bottom,
// two-step flow (enter → confirm). What's new is the visual treatment:
//
//   • Brand-coloured gradient hero with a security icon
//   • Step indicator ("Step 1 of 2" / "Step 2 of 2") with progress bar
//   • Larger, brand-coloured PIN dots inside framed boxes
//   • Subtle helper copy under the title explaining what's happening
//   • Reassurance footer ("Your PIN is encrypted on this device")
//   • Modern brand-coloured primary button with proper disabled state

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import '../../providers/transaction_provider.dart';
import '../../providers/auth_provider.dart';

class TransactionPinSetupScreen extends ConsumerStatefulWidget {
  const TransactionPinSetupScreen({super.key});

  @override
  ConsumerState<TransactionPinSetupScreen> createState() =>
      _TransactionPinSetupScreenState();
}

class _TransactionPinSetupScreenState
    extends ConsumerState<TransactionPinSetupScreen>
    with SingleTickerProviderStateMixin {
  // ── Brand palette ──────────────────────────────────────────────────────────
  static const Color _brand     = Color(0xFF34544A);
  static const Color _brandSoft = Color(0xFF7CA183);
  static const Color _surface   = Color(0xFFF6F8F7);
  static const Color _border    = Color(0xFFE5EAE7);

  final TextEditingController _controller = TextEditingController();
  final FocusNode _focusNode = FocusNode();

  String _firstPin = '';
  bool _isConfirmStep = false;
  bool _isProcessing = false;
  bool _mismatchShake = false;

  late final AnimationController _shakeController;

  @override
  void initState() {
    super.initState();
    _shakeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 350),
    );
    _controller.addListener(_onPinChanged);
    // Bring up native keyboard automatically.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _openKeyboard();
    });
  }

  /// Re-open the soft keyboard reliably on Android.
  /// On Android, swiping down to dismiss the keyboard does NOT remove focus
  /// from the TextField — so requestFocus() is a no-op when the node already
  /// has focus. We must poke the platform channel directly in that case.
  void _openKeyboard() {
    if (!mounted) return;
    if (_focusNode.hasFocus) {
      SystemChannels.textInput.invokeMethod('TextInput.show');
    } else {
      FocusScope.of(context).requestFocus(_focusNode);
    }
  }

  @override
  void dispose() {
    _controller.removeListener(_onPinChanged);
    _controller.dispose();
    _focusNode.dispose();
    _shakeController.dispose();
    super.dispose();
  }

  void _onPinChanged() {
    setState(() {});
    if (_controller.text.length == 4 && !_isProcessing) {
      // Tiny delay so the final dot animates in before transitioning.
      Future.delayed(const Duration(milliseconds: 120), _handlePinFilled);
    }
  }

  void _handlePinFilled() {
    if (!mounted) return;
    if (!_isConfirmStep) {
      // Move on to confirm step.
      setState(() {
        _firstPin = _controller.text;
        _isConfirmStep = true;
        _controller.clear();
      });
      _openKeyboard();
    } else {
      // Confirm step — check match.
      if (_controller.text == _firstPin) {
        _handleSubmit();
      } else {
        // Shake + clear, then move back to step 1.
        _shakeController.forward(from: 0);
        setState(() => _mismatchShake = true);
        Future.delayed(const Duration(milliseconds: 400), () {
          if (!mounted) return;
          setState(() => _mismatchShake = false);
        });
        _showError('PINs do not match. Please try again.');
        setState(() {
          _firstPin = '';
          _isConfirmStep = false;
          _controller.clear();
        });
        _openKeyboard();
      }
    }
  }

  void _showError(String message) {
    HapticFeedback.mediumImpact();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: const Color(0xFFD64545),
        behavior: SnackBarBehavior.floating,
        margin: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12.r),
        ),
        duration: const Duration(seconds: 2),
      ),
    );
  }

  void _resetToFirstStep() {
    setState(() {
      _firstPin = '';
      _isConfirmStep = false;
      _controller.clear();
    });
    _openKeyboard();
  }

  Future<void> _handleSubmit() async {
    if (_isProcessing) return;
    setState(() => _isProcessing = true);

    try {
      final transactionNotifier =
          ref.read(transactionNotifierProvider.notifier);
      final response =
          await transactionNotifier.createTransactionPin(pin: _firstPin);

      if (!mounted) return;

      if (response.success) {
        final authNotifier = ref.read(authNotifierProvider.notifier);
        await authNotifier.forceRefreshUserInfo();

        if (!mounted) return;
        showDialog(
          context: context,
          barrierDismissible: false,
          builder: (context) => Dialog(
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(20.r),
            ),
            child: Padding(
              padding: EdgeInsets.fromLTRB(24.w, 28.h, 24.w, 20.h),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 64.w,
                    height: 64.h,
                    decoration: BoxDecoration(
                      color: _brand.withOpacity(0.10),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(Icons.check_rounded,
                        size: 36.sp, color: _brand),
                  ),
                  SizedBox(height: 16.h),
                  Text(
                    'PIN Set Successfully',
                    style: TextStyle(
                      fontSize: 18.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.grey[900],
                    ),
                  ),
                  SizedBox(height: 8.h),
                  Text(
                    'Your transaction PIN is now active. Keep it secret — '
                    'you\'ll need it to authorize payments.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 13.sp,
                      color: Colors.grey[600],
                      height: 1.4,
                    ),
                  ),
                  SizedBox(height: 20.h),
                  SizedBox(
                    width: double.infinity,
                    height: 50.h,
                    child: ElevatedButton(
                      onPressed: () {
                        Navigator.of(context).pop();
                        Navigator.of(context).pushNamedAndRemoveUntil(
                          '/homeNav',
                          (route) => false,
                        );
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: _brand,
                        foregroundColor: Colors.white,
                        elevation: 0,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12.r),
                        ),
                      ),
                      child: Text(
                        'Continue',
                        style: TextStyle(
                          fontSize: 15.sp,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      } else {
        _showError(response.message ?? 'Failed to set transaction PIN');
        _resetToFirstStep();
      }
    } catch (e) {
      if (!mounted) return;
      _showError('An error occurred: ${e.toString()}');
      _resetToFirstStep();
    } finally {
      if (mounted) {
        setState(() => _isProcessing = false);
      }
    }
  }

  // ─── UI ─────────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    final pinLength = _controller.text.length;
    final stepNum = _isConfirmStep ? 2 : 1;
    final title    = _isConfirmStep ? 'Confirm your PIN' : 'Create your PIN';
    final helperText = _isConfirmStep
        ? 'Re-enter the same 4 digits to confirm.'
        : 'Pick 4 digits you\'ll use to authorize every transaction.';

    return Scaffold(
      backgroundColor: Colors.white,
      resizeToAvoidBottomInset: true,
      body: SafeArea(
        child: GestureDetector(
          // Tap anywhere to refocus the hidden field (re-opens keyboard).
          onTap: _openKeyboard,
          behavior: HitTestBehavior.opaque,
          child: Column(
            children: [
              SizedBox(height: 12.h),

              // ── Header row: back button (confirm step) + step pill ────────
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 16.w),
                child: Row(
                  children: [
                    SizedBox(
                      width: 40.w,
                      child: _isConfirmStep
                          ? Material(
                              color: _surface,
                              shape: const CircleBorder(),
                              child: InkWell(
                                customBorder: const CircleBorder(),
                                onTap: _isProcessing
                                    ? null
                                    : _resetToFirstStep,
                                child: Padding(
                                  padding: EdgeInsets.all(8.r),
                                  child: Icon(
                                    Icons.arrow_back_rounded,
                                    size: 20.sp,
                                    color: _brand,
                                  ),
                                ),
                              ),
                            )
                          : const SizedBox.shrink(),
                    ),
                    Expanded(
                      child: Center(
                        child: Container(
                          padding: EdgeInsets.symmetric(
                            horizontal: 14.w,
                            vertical: 6.h,
                          ),
                          decoration: BoxDecoration(
                            color: _brand.withOpacity(0.10),
                            borderRadius: BorderRadius.circular(20.r),
                          ),
                          child: Text(
                            'Step $stepNum of 2',
                            style: TextStyle(
                              fontSize: 12.sp,
                              fontWeight: FontWeight.w600,
                              color: _brand,
                              letterSpacing: 0.3,
                            ),
                          ),
                        ),
                      ),
                    ),
                    SizedBox(width: 40.w),
                  ],
                ),
              ),

              SizedBox(height: 14.h),

              // ── Progress bar (2 segments) ─────────────────────────────────
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 24.w),
                child: Row(
                  children: [
                    Expanded(
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 250),
                        height: 4.h,
                        decoration: BoxDecoration(
                          color: _brand,
                          borderRadius: BorderRadius.circular(2.r),
                        ),
                      ),
                    ),
                    SizedBox(width: 6.w),
                    Expanded(
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 250),
                        height: 4.h,
                        decoration: BoxDecoration(
                          color: _isConfirmStep
                              ? _brand
                              : const Color(0xFFE5EAE7),
                          borderRadius: BorderRadius.circular(2.r),
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              SizedBox(height: 32.h),

              // ── Brand hero icon ───────────────────────────────────────────
              Container(
                width: 76.w,
                height: 76.w,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [_brand, _brandSoft],
                  ),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: _brand.withOpacity(0.22),
                      blurRadius: 18,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: Icon(
                  _isConfirmStep
                      ? Icons.lock_rounded
                      : Icons.shield_outlined,
                  color: Colors.white,
                  size: 36.sp,
                ),
              ),

              SizedBox(height: 22.h),

              // ── Title ─────────────────────────────────────────────────────
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 24.w),
                child: Text(
                  title,
                  style: TextStyle(
                    fontSize: 24.sp,
                    fontWeight: FontWeight.w700,
                    color: Colors.grey[900],
                    letterSpacing: -0.3,
                  ),
                ),
              ),
              SizedBox(height: 8.h),

              // ── Helper text ───────────────────────────────────────────────
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 32.w),
                child: Text(
                  helperText,
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 13.sp,
                    color: Colors.grey[600],
                    height: 1.4,
                  ),
                ),
              ),

              SizedBox(height: 36.h),

              // ── PIN dot boxes (with shake on mismatch) ───────────────────
              AnimatedBuilder(
                animation: _shakeController,
                builder: (_, child) {
                  // simple horizontal shake
                  final progress = _shakeController.value;
                  final offset = _mismatchShake
                      ? (8 *
                          (1 - progress) *
                          ((progress * 4).floor().isEven ? 1 : -1))
                      : 0.0;
                  return Transform.translate(
                    offset: Offset(offset, 0),
                    child: child,
                  );
                },
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: List.generate(4, (index) {
                    final filled = index < pinLength;
                    return AnimatedContainer(
                      duration: const Duration(milliseconds: 180),
                      curve: Curves.easeOut,
                      margin: EdgeInsets.symmetric(horizontal: 8.w),
                      width: 56.w,
                      height: 60.h,
                      decoration: BoxDecoration(
                        color: filled
                            ? _brand.withOpacity(0.06)
                            : _surface,
                        border: Border.all(
                          color: filled ? _brand : _border,
                          width: filled ? 1.6 : 1,
                        ),
                        borderRadius: BorderRadius.circular(14.r),
                      ),
                      child: Center(
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 160),
                          curve: Curves.easeOut,
                          width: filled ? 14.w : 12.w,
                          height: filled ? 14.h : 12.h,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: filled
                                ? _brand
                                : Colors.transparent,
                            border: filled
                                ? null
                                : Border.all(
                                    color: Colors.grey.shade400,
                                    width: 1.5,
                                  ),
                          ),
                        ),
                      ),
                    );
                  }),
                ),
              ),

              // Hidden text field that drives the native keyboard.
              Opacity(
                opacity: 0,
                child: SizedBox(
                  height: 1,
                  child: TextField(
                    controller: _controller,
                    focusNode: _focusNode,
                    autofocus: true,
                    keyboardType: TextInputType.number,
                    obscureText: true,
                    enableInteractiveSelection: false,
                    showCursor: false,
                    inputFormatters: [
                      FilteringTextInputFormatter.digitsOnly,
                      LengthLimitingTextInputFormatter(4),
                    ],
                  ),
                ),
              ),

              SizedBox(height: 24.h),

              // ── Reassurance footer ────────────────────────────────────────
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 32.w),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.lock_outline,
                        size: 14.sp, color: Colors.grey[500]),
                    SizedBox(width: 6.w),
                    Flexible(
                      child: Text(
                        'Your PIN is encrypted — never share it with anyone.',
                        style: TextStyle(
                          fontSize: 11.5.sp,
                          color: Colors.grey[600],
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              const Spacer(),

              // ── Primary button ────────────────────────────────────────────
              Padding(
                padding: EdgeInsets.symmetric(horizontal: 24.w),
                child: SizedBox(
                  width: double.infinity,
                  height: 56.h,
                  child: ElevatedButton(
                    onPressed: (pinLength == 4 && !_isProcessing)
                        ? _handlePinFilled
                        : null,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: _brand,
                      disabledBackgroundColor:
                          _brand.withOpacity(0.35),
                      foregroundColor: Colors.white,
                      disabledForegroundColor:
                          Colors.white.withOpacity(0.8),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14.r),
                      ),
                      elevation: 0,
                    ),
                    child: _isProcessing
                        ? SizedBox(
                            width: 22.w,
                            height: 22.h,
                            child: const CircularProgressIndicator(
                              strokeWidth: 2.4,
                              valueColor: AlwaysStoppedAnimation<Color>(
                                  Colors.white),
                            ),
                          )
                        : Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                _isConfirmStep ? 'Confirm PIN' : 'Continue',
                                style: TextStyle(
                                  fontSize: 16.sp,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              SizedBox(width: 8.w),
                              Icon(
                                _isConfirmStep
                                    ? Icons.check_rounded
                                    : Icons.arrow_forward_rounded,
                                size: 18.sp,
                              ),
                            ],
                          ),
                  ),
                ),
              ),

              SizedBox(height: 28.h),
            ],
          ),
        ),
      ),
    );
  }
}