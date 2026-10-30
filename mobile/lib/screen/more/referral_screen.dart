// import 'package:flutter/material.dart';
// import 'package:flutter/services.dart';
// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import 'package:flutter_screenutil/flutter_screenutil.dart';
// import '../../core/network/api_service.dart';
// import '../../core/utils/formatters.dart';
// import '../../providers/auth_provider.dart';

// class ReferralScreen extends ConsumerStatefulWidget {
//   const ReferralScreen({super.key});

//   @override
//   ConsumerState<ReferralScreen> createState() => _ReferralScreenState();
// }

// class _ReferralScreenState extends ConsumerState<ReferralScreen> {
//   bool _isWithdrawing = false;

//   void _copyCode(String code) {
//     Clipboard.setData(ClipboardData(text: code));
//     ScaffoldMessenger.of(context).showSnackBar(
//         const SnackBar(content: Text('Referral code copied!')));
//   }

//   Future<void> _showWithdrawDialog(double commission) async {
//     final amountCtrl = TextEditingController(
//         text: commission.toStringAsFixed(2));
//     showDialog(
//       context: context,
//       builder: (ctx) => Dialog(
//         shape: RoundedRectangleBorder(
//             borderRadius: BorderRadius.circular(20)),
//         insetPadding: const EdgeInsets.symmetric(
//             horizontal: 24, vertical: 40),
//         child: Padding(
//           padding: const EdgeInsets.all(24),
//           child: Column(
//             mainAxisSize: MainAxisSize.min,
//             crossAxisAlignment: CrossAxisAlignment.start,
//             children: [
//               Row(children: [
//                 const Expanded(
//                   child: Text('Withdraw Earnings',
//                       style: TextStyle(
//                           fontSize: 18,
//                           fontWeight: FontWeight.bold)),
//                 ),
//                 GestureDetector(
//                   onTap: () => Navigator.of(ctx).pop(),
//                   child: Container(
//                     width: 28, height: 28,
//                     decoration: const BoxDecoration(
//                         color: Colors.red, shape: BoxShape.circle),
//                     child: const Icon(Icons.close,
//                         color: Colors.white, size: 16),
//                   ),
//                 ),
//               ]),
//               const SizedBox(height: 16),
//               Text('Available: ₦${commission.toStringAsFixed(2)}',
//                   style: TextStyle(color: Colors.grey[600])),
//               const SizedBox(height: 12),
//               TextField(
//                 controller: amountCtrl,
//                 keyboardType: TextInputType.number,
//                 decoration: InputDecoration(
//                   labelText: 'Amount',
//                   prefixText: '₦',
//                   filled: true,
//                   fillColor: const Color(0xFFF8FAF9),
//                   border: OutlineInputBorder(
//                       borderRadius: BorderRadius.circular(12),
//                       borderSide: BorderSide.none),
//                   focusedBorder: OutlineInputBorder(
//                       borderRadius: BorderRadius.circular(12),
//                       borderSide: const BorderSide(
//                           color: Color(0xFF34544A), width: 1.5)),
//                 ),
//               ),
//               const SizedBox(height: 24),
//               Row(children: [
//                 Expanded(
//                   child: OutlinedButton(
//                     onPressed: () => Navigator.of(ctx).pop(),
//                     style: OutlinedButton.styleFrom(
//                       side: BorderSide(color: Colors.grey[300]!),
//                       shape: RoundedRectangleBorder(
//                           borderRadius: BorderRadius.circular(32)),
//                       padding: const EdgeInsets.symmetric(vertical: 14),
//                     ),
//                     child: const Text('Cancel',
//                         style: TextStyle(color: Colors.black54)),
//                   ),
//                 ),
//                 const SizedBox(width: 12),
//                 Expanded(
//                   child: ElevatedButton(
//                     style: ElevatedButton.styleFrom(
//                       backgroundColor: const Color(0xFF34544A),
//                       foregroundColor: Colors.white,
//                       shape: RoundedRectangleBorder(
//                           borderRadius: BorderRadius.circular(32)),
//                       padding: const EdgeInsets.symmetric(vertical: 14),
//                     ),
//                     onPressed: () async {
//                       final amount = double.tryParse(
//                           amountCtrl.text) ?? 0;
//                       if (amount <= 0) return;
//                       Navigator.of(ctx).pop();
//                       await _redeem(amount);
//                     },
//                     child: const Text('Withdraw',
//                         style: TextStyle(fontWeight: FontWeight.bold)),
//                   ),
//                 ),
//               ]),
//             ],
//           ),
//         ),
//       ),
//     );
//   }

//   Future<void> _redeem(double amount) async {
//     setState(() => _isWithdrawing = true);
//     try {
//       final response = await ApiService.instance.postSimple(
//         '/redeem-commission',
//         data: {'amount': amount},
//       );
//       if (!mounted) return;
//       if (response.success) {
//         await ref.read(authNotifierProvider.notifier).refreshUser();
//         ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
//           content: Text('Earnings withdrawn to your wallet!'),
//           backgroundColor: Color(0xFF34544A),
//         ));
//       } else {
//         ScaffoldMessenger.of(context).showSnackBar(SnackBar(
//           content: Text(response.message.isNotEmpty
//               ? response.message : 'Withdrawal failed'),
//           backgroundColor: Colors.red,
//         ));
//       }
//     } catch (e) {
//       ScaffoldMessenger.of(context).showSnackBar(SnackBar(
//         content: Text('Error: $e'),
//         backgroundColor: Colors.red,
//       ));
//     }
//     setState(() => _isWithdrawing = false);
//   }

//   void _withdraw() {
//     // kept for legacy — unused
//     ScaffoldMessenger.of(context).showSnackBar(
//       const SnackBar(
//         content: Text(
//           'Referral earning has been transferred to your main account.',
//         ),
//       ),
//     );
//   }

//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//       backgroundColor: const Color(0xFF34544A),
//       body: SafeArea(
//         bottom: false,
//         child: Column(
//           children: [
//             SizedBox(height: 30),
//             Padding(
//               padding: EdgeInsets.symmetric(horizontal: 20),
//               child: Row(
//                 children: [
//                   IconButton(
//                     onPressed: () => Navigator.pop(context),
//                     icon: Icon(
//                       Icons.arrow_back_ios,
//                       color: Colors.white,
//                       size: 24,
//                     ),
//                   ),
//                   Expanded(
//                     child: Text(
//                       'Referral',
//                       textAlign: TextAlign.center,
//                       style: TextStyle(
//                         fontSize: 25.sp,
//                         fontWeight: FontWeight.w700,
//                         color: Colors.white,
//                       ),
//                     ),
//                   ),
//                   SizedBox(width: 48),
//                 ],
//               ),
//             ),
//             SizedBox(height: 30),
//             Container(
//               decoration: BoxDecoration(
//                 gradient: LinearGradient(
//                   colors: [Color(0xFF34544A), Color(0xFF7CA183)],
//                   begin: Alignment.topCenter,
//                   end: Alignment.bottomCenter,
//                 ),
//               ),
//             ),
//             Expanded(
//               child: Container(
//                 width: double.infinity,
//                 decoration: const BoxDecoration(
//                   color: Color(0xFF7CA183),
//                   borderRadius: BorderRadius.only(
//                     topLeft: Radius.circular(20),
//                     topRight: Radius.circular(20),
//                   ),
//                 ),
//                 child: Padding(
//                   padding: const EdgeInsets.only(top: 20),
//                   child: Container(
//                     decoration: const BoxDecoration(
//                       color: Colors.white,
//                       borderRadius: BorderRadius.only(
//                         topLeft: Radius.circular(20),
//                         topRight: Radius.circular(20),
//                       ),
//                     ),
//                     child: SingleChildScrollView(
//                       child: Padding(
//                         padding: EdgeInsets.symmetric(horizontal: 24),
//                         child: Column(
//                           crossAxisAlignment: CrossAxisAlignment.center,
//                           children: [
//                             SizedBox(height: 30),
//                             Text(
//                               'Referral Balance',
//                               style: TextStyle(
//                                 fontWeight: FontWeight.bold,
//                                 fontSize: 16.sp,
//                                 color: Colors.grey[800],
//                               ),
//                             ),
//                             SizedBox(height: 10),
//                             Builder(builder: (context) {
//                               final user = ref.watch(currentUserProvider);
//                               final commission = double.tryParse(user?.profile?.commission?.toString() ?? '0') ?? 0.0;
//                               return Text(
//                                 '₦${commission.toStringAsFixed(2)}',
//                                 style: TextStyle(
//                                   fontWeight: FontWeight.bold,
//                                   fontSize: 32.sp,
//                                   color: const Color(0xFF34544A),
//                                 ),
//                               );
//                             }),
//                             SizedBox(height: 24),
//                             Container(
//                               padding: EdgeInsets.all(16),
//                               decoration: BoxDecoration(
//                                 color: Color(0xFFF8FAF9),
//                                 borderRadius: BorderRadius.circular(12),
//                               ),
//                               child: Text(
//                                 'Any customer who registers with your referral code, you will earn 2% of whatever transaction they do.',
//                                 style: TextStyle(
//                                   fontSize: 15,
//                                   color: Colors.black87,
//                                 ),
//                                 textAlign: TextAlign.center,
//                               ),
//                             ),
//                             SizedBox(height: 30),
//                             Text(
//                               'Your Referral Code',
//                               style: TextStyle(
//                                 fontWeight: FontWeight.bold,
//                                 fontSize: 15.sp,
//                                 color: Colors.grey[800],
//                               ),
//                             ),
//                             SizedBox(height: 12),
//                             Container(
//                               padding: EdgeInsets.symmetric(
//                                 horizontal: 18,
//                                 vertical: 12,
//                               ),
//                               decoration: BoxDecoration(
//                                 color: Color(0xFFF8FAF9),
//                                 borderRadius: BorderRadius.circular(10),
//                                 border: Border.all(
//                                   color: Color(0xFF34544A),
//                                   width: 1.2,
//                                 ),
//                               ),
//                               child: Row(
//                                 mainAxisAlignment: MainAxisAlignment.center,
//                                 children: [
//                                   SelectableText(
//                                     ref.watch(currentUserProvider)
//                                         ?.profile?.refId ?? '—',
//                                     style: TextStyle(
//                                       fontWeight: FontWeight.bold,
//                                       fontSize: 18,
//                                       color: Color(0xFF34544A),
//                                     ),
//                                     enableInteractiveSelection: false,
//                                   ),
//                                   SizedBox(width: 10),
//                                   InkWell(
//                                     onTap: () {
//                                       final code = ref.read(currentUserProvider)
//                                           ?.profile?.refId ?? '';
//                                       _copyCode(code);
//                                     },
//                                     borderRadius: BorderRadius.circular(8),
//                                     child: Icon(
//                                       Icons.copy,
//                                       color: Color(0xFF34544A),
//                                     ),
//                                   ),
//                                 ],
//                               ),
//                             ),
//                             SizedBox(height: 36),
//                             SizedBox(
//                               width: double.infinity,
//                               height: 48,
//                               child: ElevatedButton(
//                                 style: ElevatedButton.styleFrom(
//                                   backgroundColor: const Color(0xFF34544A),
//                                   shape: RoundedRectangleBorder(
//                                     borderRadius: BorderRadius.circular(12),
//                                   ),
//                                 ),
//                                 onPressed: _isWithdrawing ? null : () {
//                                   final commission = double.tryParse(ref.read(currentUserProvider)
//                                       ?.profile?.commission?.toString() ?? '0') ?? 0.0;
//                                   _showWithdrawDialog(commission);
//                                 },
//                                 child: Text(
//                                   _isWithdrawing
//                                       ? 'Processing...'
//                                       : 'Withdraw Referral Earnings',
//                                   style: TextStyle(
//                                     fontWeight: FontWeight.bold,
//                                     fontSize: 18,
//                                     color: Colors.white,
//                                   ),
//                                 ),
//                               ),
//                             ),
//                             SizedBox(height: 30),
//                           ],
//                         ),
//                       ),
//                     ),
//                   ),
//                 ),
//               ),
//             ),
//           ],
//         ),
//       ),
//     );
//   }
// }


// String formatNumber(String val) {
//   final n = double.tryParse(val);
//   if (n == null) return val;
//   return n.toStringAsFixed(2).replaceAllMapped(
//         RegExp(r'(\d)(?=(\d{3})+\.)', caseSensitive: false),
//         (m) => '${m[1]},',
//       );
// }

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../core/network/api_service.dart';
import '../../core/utils/formatters.dart';
import '../../providers/auth_provider.dart';

class ReferralScreen extends ConsumerStatefulWidget {
  const ReferralScreen({super.key});

  @override
  ConsumerState<ReferralScreen> createState() => _ReferralScreenState();
}

class _ReferralScreenState extends ConsumerState<ReferralScreen> {
  bool _isWithdrawing = false;

  void _copyCode(String code) {
    Clipboard.setData(ClipboardData(text: code));
    ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Referral code copied!'),
          backgroundColor: Colors.green,
        ));
  }

  Future<void> _showWithdrawDialog(double commission) async {
    final amountCtrl = TextEditingController(
        text: commission.toStringAsFixed(2));
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20)),
        insetPadding: const EdgeInsets.symmetric(
            horizontal: 24, vertical: 40),
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(children: [
                const Expanded(
                  child: Text('Withdraw Earnings',
                      style: TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold)),
                ),
                GestureDetector(
                  onTap: () => Navigator.of(ctx).pop(),
                  child: Container(
                    width: 28, height: 28,
                    decoration: const BoxDecoration(
                        color: Colors.red, shape: BoxShape.circle),
                    child: const Icon(Icons.close,
                        color: Colors.white, size: 16),
                  ),
                ),
              ]),
              const SizedBox(height: 16),
              Text('Available: ${formatCurrency(commission)}',
                  style: TextStyle(color: Colors.grey[600])),
              const SizedBox(height: 12),
              TextField(
                controller: amountCtrl,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  labelText: 'Amount',
                  prefixText: '₦',
                  filled: true,
                  fillColor: const Color(0xFFF8FAF9),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none),
                  focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(
                          color: Color(0xFF34544A), width: 1.5)),
                ),
              ),
              const SizedBox(height: 24),
              Row(children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(ctx).pop(),
                    style: OutlinedButton.styleFrom(
                      side: BorderSide(color: Colors.grey[300]!),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(32)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    child: const Text('Cancel',
                        style: TextStyle(color: Colors.black54)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF34544A),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(32)),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                    ),
                    onPressed: () async {
                      final amount = double.tryParse(
                          amountCtrl.text) ?? 0;
                      if (amount <= 0) return;
                      Navigator.of(ctx).pop();
                      await _redeem(amount);
                    },
                    child: const Text('Withdraw',
                        style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ]),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _redeem(double amount) async {
    setState(() => _isWithdrawing = true);
    try {
      final response = await ApiService.instance.postSimple(
        '/redeem-commission',
        data: {'amount': amount},
      );
      if (!mounted) return;
      if (response.success) {
        await ref.read(authNotifierProvider.notifier).refreshUser();
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(
          content: Text('Earnings withdrawn to your wallet!'),
          backgroundColor: Color(0xFF34544A),
        ));
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(response.message.isNotEmpty
              ? response.message : 'Withdrawal failed'),
          backgroundColor: Colors.red,
        ));
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text('Error: $e'),
        backgroundColor: Colors.red,
      ));
    }
    setState(() => _isWithdrawing = false);
  }

  void _withdraw() {
    // kept for legacy — unused
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text(
          'Referral earning has been transferred to your main account.',
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            SizedBox(height: 30),
            Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: Row(
                children: [
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: Icon(
                      Icons.arrow_back_ios,
                      color: Colors.white,
                      size: 24,
                    ),
                  ),
                  Expanded(
                    child: Text(
                      'Referral',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 25.sp,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  SizedBox(width: 48),
                ],
              ),
            ),
            SizedBox(height: 30),
            Container(
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF34544A), Color(0xFF7CA183)],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
            ),
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(top: 20),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20),
                      ),
                    ),
                    child: SingleChildScrollView(
                      child: Padding(
                        padding: EdgeInsets.symmetric(horizontal: 24),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            SizedBox(height: 30),
                            Text(
                              'Referral Balance',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 16.sp,
                                color: Colors.grey[800],
                              ),
                            ),
                            SizedBox(height: 10),
                            Builder(builder: (context) {
                              final user = ref.watch(currentUserProvider);
                              final commission = double.tryParse(user?.profile?.commission?.toString() ?? '0') ?? 0.0;
                              return Text(
                                formatCurrency(commission),
                                style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 32.sp,
                                  color: const Color(0xFF34544A),
                                ),
                              );
                            }),
                            SizedBox(height: 24),
                            Container(
                              padding: EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Color(0xFFF8FAF9),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Text(
                                'Any customer who registers with your referral code, you will earn 2% of whatever transaction they do.',
                                style: TextStyle(
                                  fontSize: 15,
                                  color: Colors.black87,
                                ),
                                textAlign: TextAlign.center,
                              ),
                            ),
                            SizedBox(height: 30),
                            Text(
                              'Your Referral Code',
                              style: TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 15.sp,
                                color: Colors.grey[800],
                              ),
                            ),
                            SizedBox(height: 12),
                            Container(
                              padding: EdgeInsets.symmetric(
                                horizontal: 18,
                                vertical: 12,
                              ),
                              decoration: BoxDecoration(
                                color: Color(0xFFF8FAF9),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: Color(0xFF34544A),
                                  width: 1.2,
                                ),
                              ),
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  SelectableText(
                                    ref.watch(currentUserProvider)
                                        ?.profile?.refId ?? '—',
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 18,
                                      color: Color(0xFF34544A),
                                    ),
                                    enableInteractiveSelection: false,
                                  ),
                                  SizedBox(width: 10),
                                  InkWell(
                                    onTap: () {
                                      final code = ref.read(currentUserProvider)
                                          ?.profile?.refId ?? '';
                                      _copyCode(code);
                                    },
                                    borderRadius: BorderRadius.circular(8),
                                    child: Icon(
                                      Icons.copy,
                                      color: Color(0xFF34544A),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(height: 36),
                            SizedBox(
                              width: double.infinity,
                              height: 48,
                              child: ElevatedButton(
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF34544A),
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12),
                                  ),
                                ),
                                onPressed: _isWithdrawing ? null : () {
                                  final commission = double.tryParse(ref.read(currentUserProvider)
                                      ?.profile?.commission?.toString() ?? '0') ?? 0.0;
                                  _showWithdrawDialog(commission);
                                },
                                child: Text(
                                  _isWithdrawing
                                      ? 'Processing...'
                                      : 'Withdraw Referral Earnings',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 18,
                                    color: Colors.white,
                                  ),
                                ),
                              ),
                            ),
                            SizedBox(height: 30),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}