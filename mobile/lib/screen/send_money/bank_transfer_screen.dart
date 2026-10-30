// // lib/screen/send_money/bank_transfer_screen.dart

// import 'dart:async';
// import 'package:flutter/material.dart';
// import 'package:flutter/services.dart';
// import 'package:intl/intl.dart';
// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import 'package:flutter_screenutil/flutter_screenutil.dart';
// import '../../core/network/api_service.dart';
// import '../../providers/auth_provider.dart';
// import '../../services/cache/cache_service.dart';
// import '../transaction/transaction_summary_screen.dart';

// class BankTransferScreen extends ConsumerStatefulWidget {
//   const BankTransferScreen({super.key});

//   @override
//   ConsumerState<BankTransferScreen> createState() => _BankTransferScreenState();
// }

// class _BankTransferScreenState extends ConsumerState<BankTransferScreen> {

//   // ── Controllers ────────────────────────────────────────────────────────────
//   final _accountNumberCtrl = TextEditingController();
//   final _accountNameCtrl   = TextEditingController();
//   final _amountCtrl        = TextEditingController();
//   final _commentCtrl       = TextEditingController();
//   final _searchCtrl        = TextEditingController();

//   // ── State ──────────────────────────────────────────────────────────────────
//   List<Map<String, dynamic>> _allBanks  = [];
//   List<Map<String, dynamic>> _filteredBanks = [];
//   Map<String, dynamic>? _selectedBank;
//   bool _loadingBanks   = false;
//   bool _hasChanges     = false;
//   bool _isResolving    = false;
//   String? _resolvedName;
//   String? _sessionId;
//   String? _resolveError;

//   @override
//   void initState() {
//     super.initState();
//     _fetchBanks();
//     _accountNumberCtrl.addListener(_onAccountNumberChanged);
//     for (final c in [_accountNameCtrl, _amountCtrl]) {
//       c.addListener(() => setState(() => _hasChanges = true));
//     }
//   }

//   void _onAccountNumberChanged() {
//     setState(() => _hasChanges = true);
//     final num = _accountNumberCtrl.text;
//     if (num.length == 10 && _selectedBank != null) {
//       _resolveAccountName();
//     } else {
//       setState(() {
//         _resolvedName = null;
//         _sessionId    = null;
//         _resolveError = null;
//       });
//     }
//   }

//   Future<void> _resolveAccountName() async {
//     final bankCode = _selectedBank!['bank_code']?.toString() ?? '';
//     final accNum   = _accountNumberCtrl.text;
//     if (bankCode.isEmpty || accNum.length != 10) return;

//     setState(() {
//       _isResolving  = true;
//       _resolvedName = null;
//       _sessionId    = null;
//       _resolveError = null;
//     });

//     try {
//       final response = await ApiService.instance.postSimple(
//         '/bank-transfer/name-enquiry',
//         data: {'bank_code': bankCode, 'account_number': accNum},
//       );

//       if (!mounted) return;
//       // Backend returns account_name & session_id at ROOT level (not inside 'data')
//       // response.data maps to responseData['data'] which is null for this endpoint
//       // So we check response.success and read from the message/raw fields
//       // ApiService puts the full responseData into response.data when data key is absent
//       final rd = response.data as Map<String, dynamic>?;
//       final name      = rd?['account_name']?.toString()
//                      ?? response.message; // fallback
//       final sessionId = rd?['session_id']?.toString() ?? '';

//       if (response.success && name != null && name.isNotEmpty && sessionId.isNotEmpty) {
//         setState(() {
//           _resolvedName = name;
//           _sessionId    = sessionId;
//           _accountNameCtrl.text = name;
//         });
//       } else {
//         setState(() {
//           _resolveError = response.message.isNotEmpty
//               ? response.message
//               : 'Account not found';
//         });
//       }
//     } catch (_) {
//       setState(() => _resolveError = 'Could not verify account');
//     }
//     setState(() => _isResolving = false);
//   }

//   @override
//   void dispose() {
//     _accountNumberCtrl.removeListener(_onAccountNumberChanged);
//     _accountNumberCtrl.dispose();
//     _accountNameCtrl.dispose();
//     _amountCtrl.dispose();
//     _commentCtrl.dispose();
//     _searchCtrl.dispose();
//     super.dispose();
//   }

//   // ── Fetch bank list from GET /bank-transfer/banklist ──────────────────────
//   // Strategy: cache-first. Banks rarely change, so we show the cached list
//   // immediately (no spinner) if it exists and is still within the 3-week
//   // TTL defined in CacheService. If the cache is missing or expired we hit
//   // the network. Either way, we always update the local cache when a fresh
//   // response comes back successfully.
//   Future<void> _fetchBanks() async {
//     final cache = CacheService();

//     // 1) Try cache first — instant first paint when available.
//     final cached = await cache.getCachedBankList();
//     if (cached != null && cached.isNotEmpty) {
//       if (!mounted) return;
//       setState(() {
//         _allBanks      = cached;
//         _filteredBanks = cached;
//         _loadingBanks  = false; // already have something to show
//       });
//       return; // Cache is still valid (TTL handled in CacheService) — done.
//     }

//     // 2) No usable cache → hit the network.
//     setState(() => _loadingBanks = true);
//     try {
//       final response = await ApiService.instance.get<Map<String, dynamic>>(
//         '/bank-transfer/banklist',
//         fromJson: (j) => j,
//       );
//       if (response.success && response.data != null) {
//         final raw = response.data!['data'];
//         if (raw is List) {
//           final banks = raw.whereType<Map<String, dynamic>>().toList();
//           if (mounted) {
//             setState(() {
//               _allBanks      = banks;
//               _filteredBanks = banks;
//             });
//           }
//           // Persist for next time (3-week TTL).
//           if (banks.isNotEmpty) {
//             await cache.cacheBankList(banks);
//           }
//         }
//       }
//     } catch (_) {}
//     if (mounted) setState(() => _loadingBanks = false);
//   }

//   void _filterBanks(String query) {
//     setState(() {
//       _filteredBanks = query.isEmpty
//           ? _allBanks
//           : _allBanks.where((b) {
//               final name = (b['name'] ?? b['bankName'] ?? '').toString().toLowerCase();
//               return name.contains(query.toLowerCase());
//             }).toList();
//     });
//   }

//   // ── Bank picker ────────────────────────────────────────────────────────────
//   void _showBankPicker() {
//     _searchCtrl.clear();
//     _filteredBanks = _allBanks;
//     showModalBottomSheet(
//       context: context,
//       isScrollControlled: true,
//       backgroundColor: Colors.white,
//       shape: const RoundedRectangleBorder(
//           borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
//       builder: (ctx) => StatefulBuilder(
//         builder: (ctx, setSheet) => DraggableScrollableSheet(
//           expand: false,
//           initialChildSize: 0.85,
//           builder: (_, scrollCtrl) => Column(children: [
//             Container(
//               margin: const EdgeInsets.only(top: 12),
//               width: 40, height: 4,
//               decoration: BoxDecoration(
//                   color: Colors.grey[300],
//                   borderRadius: BorderRadius.circular(2)),
//             ),
//             Padding(
//               padding: const EdgeInsets.all(16),
//               child: TextField(
//                 controller: _searchCtrl,
//                 onChanged: (v) {
//                   _filterBanks(v);
//                   setSheet(() {});
//                 },
//                 decoration: InputDecoration(
//                   hintText: 'Search bank name',
//                   prefixIcon: const Icon(Icons.search),
//                   filled: true,
//                   fillColor: const Color(0xFFF8FAF9),
//                   border: OutlineInputBorder(
//                       borderRadius: BorderRadius.circular(12),
//                       borderSide: BorderSide.none),
//                 ),
//               ),
//             ),
//             _loadingBanks
//                 ? const Center(child: CircularProgressIndicator(
//                     color: Color(0xFF34544A)))
//                 : Expanded(
//                     child: ListView.builder(
//                       controller: scrollCtrl,
//                       itemCount: _filteredBanks.length,
//                       itemBuilder: (_, i) {
//                         final bank = _filteredBanks[i];
//                         final name = bank['name']?.toString() ?? '';
//                         return ListTile(
//                           leading: const Icon(Icons.account_balance,
//                               color: Color(0xFF34544A)),
//                           title: Text(name,
//                               style: const TextStyle(
//                                   fontWeight: FontWeight.w500)),
//                           onTap: () {
//                             setState(() {
//                               _selectedBank = bank;
//                               _hasChanges   = true;
//                             });
//                             Navigator.pop(ctx);
//                           },
//                         );
//                       },
//                     ),
//                   ),
//           ]),
//         ),
//       ),
//     );
//   }

//   // ── Validation ─────────────────────────────────────────────────────────────
//   // The amount field shows comma-grouped text (e.g. "1,500.00"); strip the
//   // commas before parsing or sending anywhere.
//   String get _rawAmount => _amountCtrl.text.replaceAll(',', '').trim();

//   bool get _isValid =>
//       _selectedBank != null &&
//       _accountNumberCtrl.text.length == 10 &&
//       _accountNameCtrl.text.trim().isNotEmpty &&
//       _sessionId != null &&
//       (double.tryParse(_rawAmount) ?? 0) > 0 &&
//       _hasEnoughBalance;

//   bool get _hasEnoughBalance {
//     final amount  = double.tryParse(_rawAmount) ?? 0;
//     if (amount <= 0) return true; // let _isValid handle the zero case
//     // Access balance via ConsumerState's ref — injected via build method
//     return amount <= _userBalance;
//   }

//   double _userBalance = 0;

//   // ── Navigate to summary ────────────────────────────────────────────────────
//   void _proceed() {
//     final bank     = _selectedBank!;
//     // SafeHaven bank list returns 'bank_code' for the transfer field
//     final bankCode = bank['bank_code']?.toString() ?? bank['bankCode']?.toString() ?? '';
//     final bankName = bank['name']?.toString() ?? '';
//     final accNum   = _accountNumberCtrl.text.trim();
//     final accName  = _accountNameCtrl.text.trim();
//     final amount   = _rawAmount;                 // digits only — for the backend
//     final amountDisplay = _amountCtrl.text.trim(); // comma-grouped — for the UI
//     final comment  = _commentCtrl.text.trim();

//     final sessionId = _sessionId!; // Real session ID from SafeHaven name enquiry

//     Navigator.push(
//       context,
//       MaterialPageRoute(
//         builder: (_) => TransactionSummaryScreen(
//           data: TransactionSummaryData(
//             service:       'Bank Transfer',
//             from:          'Wallet',
//             accountName:   accName,
//             accountNumber: accNum,
//             amount:        '₦$amountDisplay',
//             description:   '$bankName • $comment',
//             onConfirm: (pin) async {
//               // POST /fund/withdraw
//               final response = await ApiService.instance.postSimple(
//                 '/fund/withdraw',
//                 data: {
//                   'bank':            bankCode,
//                   'bank_name':       bankName,
//                   'account_number':  accNum,
//                   'account_name':    accName,
//                   'amount':          amount,
//                   'session_id':      sessionId,
//                   'transaction_pin': pin,
//                   if (comment.isNotEmpty) 'comment': comment,
//                 },
//               );
//               return {
//                 'success':         response.success,
//                 'message':         response.message,
//                 'reference':       response.data?['reference']?.toString() ?? '',
//                 // Prefer session_id echoed back by the backend; fall back to
//                 // the one we obtained from the SafeHaven name enquiry so the
//                 // receipt shown immediately after sending matches the value
//                 // later returned by transaction history.
//                 'session_id':      response.data?['session_id']?.toString() ?? sessionId,
//                 'description':     'Transfer to $accName',
//                 'initial_balance': response.data?['initial_balance']?.toString() ?? '',
//                 'final_balance':   response.data?['final_balance']?.toString() ?? '',
//               };
//             },
//           ),
//         ),
//       ),
//     );
//   }

//   // ── Build ──────────────────────────────────────────────────────────────────
//   @override
//   Widget build(BuildContext context) {
//     // Read current balance for validation
//     final user = ref.watch(authNotifierProvider).user;
//     _userBalance = double.tryParse(user?.balance ?? '0') ?? 0;

//     return Scaffold(
//       resizeToAvoidBottomInset: true,
//       backgroundColor: const Color(0xFF34544A),
//       body: SafeArea(
//         bottom: false,
//         child: Column(children: [
//           const SizedBox(height: 30),
//           Row(children: [
//             IconButton(
//               icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
//               onPressed: () => Navigator.of(context).pop(),
//             ),
//             Expanded(
//               child: Text('Bank Transfer',
//                   textAlign: TextAlign.center,
//                   style: TextStyle(
//                       fontSize: 22.sp,
//                       fontWeight: FontWeight.w600,
//                       color: Colors.white)),
//             ),
//             const SizedBox(width: 48),
//           ]),
//           const SizedBox(height: 20),
//           Expanded(
//             child: Container(
//               decoration: const BoxDecoration(
//                   color: Color(0xFF7CA183),
//                   borderRadius: BorderRadius.only(
//                       topLeft: Radius.circular(20),
//                       topRight: Radius.circular(20))),
//               child: Padding(
//                 padding: const EdgeInsets.only(top: 20),
//                 child: Container(
//                   decoration: const BoxDecoration(
//                       color: Colors.white,
//                       borderRadius: BorderRadius.only(
//                           topLeft: Radius.circular(20),
//                           topRight: Radius.circular(20))),
//                   child: SingleChildScrollView(
//                     padding: const EdgeInsets.all(20),
//                     child: Column(
//                       crossAxisAlignment: CrossAxisAlignment.start,
//                       children: [
//                         SizedBox(height: 8.h),

//                         // ── Balance display ──────────────────────────────────
//                         Container(
//                           padding: const EdgeInsets.symmetric(
//                               horizontal: 14, vertical: 10),
//                           decoration: BoxDecoration(
//                             color: _userBalance <= 0
//                                 ? Colors.red.withOpacity(0.08)
//                                 : const Color(0xFF34544A).withOpacity(0.06),
//                             borderRadius: BorderRadius.circular(10),
//                             border: Border.all(
//                               color: _userBalance <= 0
//                                   ? Colors.red.withOpacity(0.3)
//                                   : const Color(0xFF34544A).withOpacity(0.2),
//                             ),
//                           ),
//                           child: Row(children: [
//                             Icon(
//                               _userBalance <= 0
//                                   ? Icons.warning_amber_rounded
//                                   : Icons.account_balance_wallet_outlined,
//                               size: 16,
//                               color: _userBalance <= 0
//                                   ? Colors.red[700]
//                                   : const Color(0xFF34544A),
//                             ),
//                             const SizedBox(width: 8),
//                             Text(
//                               _userBalance <= 0
//                                   ? 'Insufficient balance — please fund your wallet first'
//                                   // : 'Available Balance: ₦${_userBalance.toStringAsFixed(2)}',
//                                   : 'Available Balance: ₦${formatNumber(_userBalance.toStringAsFixed(2))}',
                                  
                                  
//                               style: TextStyle(
//                                 fontSize: 12.sp,
//                                 fontWeight: FontWeight.w600,
//                                 color: _userBalance <= 0
//                                     ? Colors.red[700]
//                                     : const Color(0xFF34544A),
//                               ),
//                             ),
//                           ]),
//                         ),
//                         SizedBox(height: 16.h),

//                         // ── Destination Bank ─────────────────────────────────
//                         _label('Destination Bank'),
//                         GestureDetector(
//                           onTap: _showBankPicker,
//                           child: Container(
//                             padding: const EdgeInsets.symmetric(
//                                 horizontal: 16, vertical: 18),
//                             decoration: BoxDecoration(
//                               color: const Color(0xFFF8FAF9),
//                               borderRadius: BorderRadius.circular(12.r),
//                               border: _selectedBank != null
//                                   ? Border.all(
//                                       color: const Color(0xFF34544A),
//                                       width: 1.5)
//                                   : null,
//                             ),
//                             child: Row(children: [
//                               Icon(Icons.account_balance,
//                                   color: _selectedBank != null
//                                       ? const Color(0xFF34544A)
//                                       : Colors.grey[400]),
//                               const SizedBox(width: 10),
//                               Expanded(
//                                 child: Text(
//                                   _selectedBank != null
//                                       ? (_selectedBank!['name']?.toString() ?? '')
//                                       : _loadingBanks
//                                           ? 'Loading banks...'
//                                           : 'Select Bank',
//                                   style: TextStyle(
//                                       color: _selectedBank != null
//                                           ? Colors.black87
//                                           : Colors.grey[400],
//                                       fontSize: 16.sp),
//                                 ),
//                               ),
//                               Icon(Icons.chevron_right,
//                                   color: Colors.grey[400]),
//                             ]),
//                           ),
//                         ),
//                         SizedBox(height: 18.h),

//                         // ── Account Number ────────────────────────────────────
//                         _label('Account Number'),
//                         _textField(
//                           controller: _accountNumberCtrl,
//                           hint: '0123456789',
//                           type: TextInputType.number,
//                           maxLength: 10,
//                         ),
//                         SizedBox(height: 18.h),

//                         // ── Account Name (auto-resolved via name enquiry) ──────
//                         if (_isResolving)
//                           Padding(
//                             padding: const EdgeInsets.only(bottom: 8),
//                             child: Row(children: [
//                               const SizedBox(width: 16, height: 16,
//                                   child: CircularProgressIndicator(
//                                       strokeWidth: 2,
//                                       color: Color(0xFF34544A))),
//                               const SizedBox(width: 8),
//                               Text('Verifying account...',
//                                   style: TextStyle(
//                                       color: Colors.grey[600], fontSize: 13.sp)),
//                             ]),
//                           ),
//                         if (_resolvedName != null)
//                           Padding(
//                             padding: const EdgeInsets.only(bottom: 8),
//                             child: Row(children: [
//                               const Icon(Icons.check_circle,
//                                   color: Colors.green, size: 18),
//                               const SizedBox(width: 6),
//                               Text(_resolvedName!,
//                                   style: const TextStyle(
//                                       color: Colors.green,
//                                       fontWeight: FontWeight.bold)),
//                             ]),
//                           ),
//                         if (_resolveError != null)
//                           Padding(
//                             padding: const EdgeInsets.only(bottom: 8),
//                             child: Row(children: [
//                               const Icon(Icons.error_outline,
//                                   color: Colors.red, size: 18),
//                               const SizedBox(width: 6),
//                               Text(_resolveError!,
//                                   style: const TextStyle(
//                                       color: Colors.red, fontSize: 13)),
//                             ]),
//                           ),
//                         SizedBox(height: 18.h),
//                         _label('Account Name'),
//                         _textField(
//                           controller: _accountNameCtrl,
//                           hint: 'Auto-filled after verification',
//                           readOnly: _resolvedName != null,
//                         ),
//                         SizedBox(height: 18.h),

//                         // ── Amount ────────────────────────────────────────────
                      
//                         const SizedBox(height: 6),
//                         _textField(
//                           controller: _amountCtrl,
//                           hint: '₦ Enter Amount',
//                           type: const TextInputType.numberWithOptions(
//                               decimal: true),
//                           inputFormatters: [
//                             ThousandsSeparatorInputFormatter(),
//                           ],
//                         ),
//                         if ((double.tryParse(_rawAmount) ?? 0) > _userBalance &&
//                             _rawAmount.isNotEmpty)
//                           Padding(
//                             padding: const EdgeInsets.only(top: 6),
//                             child: Row(children: [
//                               const Icon(Icons.warning_amber_rounded,
//                                   color: Colors.red, size: 14),
//                               const SizedBox(width: 4),
//                               Text('Insufficient balance',
//                                   style: TextStyle(
//                                       color: Colors.red, fontSize: 12.sp)),
//                             ]),
//                           ),
//                         SizedBox(height: 18.h),

//                         // ── Narration (optional) ──────────────────────────────
//                         _label('Narration (Optional)'),
//                         _textField(
//                           controller: _commentCtrl,
//                           hint: 'What is this transfer for?',
//                         ),
//                         SizedBox(height: 36.h),

//                         // ── Next button ───────────────────────────────────────
//                         SizedBox(
//                           width: double.infinity,
//                           child: ElevatedButton(
//                             style: ElevatedButton.styleFrom(
//                               backgroundColor: _isValid
//                                   ? const Color(0xFF34544A)
//                                   : Colors.grey[300],
//                               foregroundColor: Colors.white,
//                               shape: RoundedRectangleBorder(
//                                   borderRadius:
//                                       BorderRadius.circular(32.r)),
//                               padding:
//                                   EdgeInsets.symmetric(vertical: 16.h),
//                             ),
//                             onPressed: _isValid && _userBalance > 0 ? _proceed : null,
//                             child: Text('Next',
//                                 style: TextStyle(
//                                     fontSize: 18.sp,
//                                     fontWeight: FontWeight.bold,
//                                     color: _isValid
//                                         ? Colors.white
//                                         : Colors.grey[600])),
//                           ),
//                         ),
//                         SizedBox(height: 24.h),
//                       ],
//                     ),
//                   ),
//                 ),
//               ),
//             ),
//           ),
//         ]),
//       ),
//     );
//   }

//   Widget _label(String text) => Padding(
//     padding: const EdgeInsets.only(bottom: 6),
//     child: Text(text,
//         style: TextStyle(
//             fontSize: 13.sp,
//             color: Colors.grey[600],
//             fontWeight: FontWeight.w500)),
//   );

//   Widget _textField({
//     required TextEditingController controller,
//     required String hint,
//     TextInputType type = TextInputType.text,
//     int? maxLength,
//     bool readOnly = false,
//     List<TextInputFormatter>? inputFormatters,
//   }) =>
//       TextField(
//         controller: controller,
//         keyboardType: type,
//         maxLength: maxLength,
//         readOnly: readOnly,
//         inputFormatters: inputFormatters,
//         decoration: InputDecoration(
//           hintText: hint,
//           hintStyle:
//               TextStyle(color: Colors.grey[400], fontSize: 15.sp),
//           filled: true,
//           fillColor: const Color(0xFFF8FAF9),
//           border: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(12.r),
//               borderSide: BorderSide.none),
//           focusedBorder: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(12.r),
//               borderSide: const BorderSide(
//                   color: Color(0xFF34544A), width: 1.5)),
//           counterText: '',
//           contentPadding: EdgeInsets.symmetric(
//               vertical: 18.h, horizontal: 16.w),
//         ),
//       );
// }

// String formatNumber(String val) {
//   final n = double.tryParse(val);
//   if (n == null) return val;
//   return n.toStringAsFixed(2).replaceAllMapped(
//         RegExp(r'(\d)(?=(\d{3})+\.)', caseSensitive: false),
//         (m) => '${m[1]},',
//       );
// }

// /// Adds thousand separators to a numeric text field as the user types, while
// /// keeping the cursor in a sensible place. Allows a single decimal point and
// /// up to 2 decimal places. The underlying digits (with commas) live in the
// /// controller — strip commas with `text.replaceAll(',', '')` before parsing
// /// or sending to the backend.
// class ThousandsSeparatorInputFormatter extends TextInputFormatter {
//   final NumberFormat _fmt = NumberFormat('#,##0.##', 'en_US');

//   @override
//   TextEditingValue formatEditUpdate(
//     TextEditingValue oldValue,
//     TextEditingValue newValue,
//   ) {
//     String text = newValue.text;
//     if (text.isEmpty) return newValue;

//     // Keep only digits and at most one dot.
//     text = text.replaceAll(',', '');
//     if ('.'.allMatches(text).length > 1) {
//       // Reject the extra dot — keep the old value.
//       return oldValue;
//     }

//     // Split off the decimal part so we don't reformat it away while typing.
//     final hasDot = text.contains('.');
//     final parts = text.split('.');
//     String intPart = parts[0].replaceAll(RegExp(r'[^0-9]'), '');
//     String decPart = parts.length > 1
//         ? parts[1].replaceAll(RegExp(r'[^0-9]'), '')
//         : '';
//     if (decPart.length > 2) decPart = decPart.substring(0, 2);

//     String formatted;
//     if (intPart.isEmpty) {
//       formatted = hasDot ? '0.$decPart' : '';
//     } else {
//       final intVal = int.tryParse(intPart) ?? 0;
//       formatted = _fmt.format(intVal);
//       if (hasDot) formatted = '$formatted.$decPart';
//     }

//     return TextEditingValue(
//       text: formatted,
//       selection: TextSelection.collapsed(offset: formatted.length),
//     );
//   }
// }


// lib/screen/send_money/bank_transfer_screen.dart

import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../core/network/api_service.dart';
import '../../providers/auth_provider.dart';
import '../../services/cache/cache_service.dart';
import '../transaction/transaction_summary_screen.dart';

class BankTransferScreen extends ConsumerStatefulWidget {
  const BankTransferScreen({super.key});

  @override
  ConsumerState<BankTransferScreen> createState() => _BankTransferScreenState();
}

class _BankTransferScreenState extends ConsumerState<BankTransferScreen> {

  // ── Controllers ────────────────────────────────────────────────────────────
  final _accountNumberCtrl = TextEditingController();
  final _accountNameCtrl   = TextEditingController();
  final _amountCtrl        = TextEditingController();
  final _commentCtrl       = TextEditingController();
  final _searchCtrl        = TextEditingController();

  // ── State ──────────────────────────────────────────────────────────────────
  List<Map<String, dynamic>> _allBanks  = [];
  List<Map<String, dynamic>> _filteredBanks = [];
  Map<String, dynamic>? _selectedBank;
  bool _loadingBanks   = false;
  bool _hasChanges     = false;
  bool _isResolving    = false;
  String? _resolvedName;
  String? _sessionId;
  String? _resolveError;

  // Lets _fetchBanks() rebuild the OPEN bank-picker bottom sheet when the list
  // arrives. The sheet has its own StatefulBuilder, so the screen's setState
  // does not refresh it on its own — without this the first tap shows an empty
  // sheet until you close and reopen it.
  void Function(void Function())? _sheetSetState;
  bool _bankFetchInFlight = false;

  @override
  void initState() {
    super.initState();
    _fetchBanks();
    _accountNumberCtrl.addListener(_onAccountNumberChanged);
    for (final c in [_accountNameCtrl, _amountCtrl]) {
      c.addListener(() => setState(() => _hasChanges = true));
    }
  }

  void _onAccountNumberChanged() {
    setState(() => _hasChanges = true);
    final num = _accountNumberCtrl.text;
    if (num.length == 10 && _selectedBank != null) {
      _resolveAccountName();
    } else {
      setState(() {
        _resolvedName = null;
        _sessionId    = null;
        _resolveError = null;
      });
    }
  }

  Future<void> _resolveAccountName() async {
    final bankCode = _selectedBank!['bank_code']?.toString() ?? '';
    final accNum   = _accountNumberCtrl.text;
    if (bankCode.isEmpty || accNum.length != 10) return;

    setState(() {
      _isResolving  = true;
      _resolvedName = null;
      _sessionId    = null;
      _resolveError = null;
    });

    try {
      final response = await ApiService.instance.postSimple(
        '/bank-transfer/name-enquiry',
        data: {'bank_code': bankCode, 'account_number': accNum},
      );

      if (!mounted) return;
      // Backend returns account_name & session_id at ROOT level (not inside 'data')
      // response.data maps to responseData['data'] which is null for this endpoint
      // So we check response.success and read from the message/raw fields
      // ApiService puts the full responseData into response.data when data key is absent
      final rd = response.data as Map<String, dynamic>?;
      final name      = rd?['account_name']?.toString()
                     ?? response.message; // fallback
      final sessionId = rd?['session_id']?.toString() ?? '';

      if (response.success && name != null && name.isNotEmpty && sessionId.isNotEmpty) {
        setState(() {
          _resolvedName = name;
          _sessionId    = sessionId;
          _accountNameCtrl.text = name;
        });
      } else {
        setState(() {
          _resolveError = response.message.isNotEmpty
              ? response.message
              : 'Account not found';
        });
      }
    } catch (_) {
      setState(() => _resolveError = 'Could not verify account');
    }
    setState(() => _isResolving = false);
  }

  @override
  void dispose() {
    _accountNumberCtrl.removeListener(_onAccountNumberChanged);
    _accountNumberCtrl.dispose();
    _accountNameCtrl.dispose();
    _amountCtrl.dispose();
    _commentCtrl.dispose();
    _searchCtrl.dispose();
    super.dispose();
  }

  // ── Fetch bank list from GET /bank-transfer/banklist ──────────────────────
  // Strategy: cache-first. Banks rarely change, so we show the cached list
  // immediately (no spinner) if it exists and is still within the 3-week
  // TTL defined in CacheService. If the cache is missing or expired we hit
  // the network. Either way, we always update the local cache when a fresh
  // response comes back successfully.
  Future<void> _fetchBanks() async {
    if (_bankFetchInFlight) return; // avoid overlapping fetches
    _bankFetchInFlight = true;

    final cache = CacheService();
    try {
      // 1) Try cache first — instant first paint when available.
      final cached = await cache.getCachedBankList();
      if (cached != null && cached.isNotEmpty) {
        if (!mounted) return;
        setState(() {
          _allBanks      = cached;
          _filteredBanks = cached;
          _loadingBanks  = false; // already have something to show
        });
        _rebuildSheet();
        return; // Cache is still valid (TTL handled in CacheService) — done.
      }

      // 2) No usable cache → hit the network.
      if (mounted) setState(() => _loadingBanks = true);
      _rebuildSheet();

      final response = await ApiService.instance.get<Map<String, dynamic>>(
        '/bank-transfer/banklist',
        fromJson: (j) => j,
      );
      if (response.success && response.data != null) {
        final raw = response.data!['data'];
        if (raw is List) {
          final banks = raw.whereType<Map<String, dynamic>>().toList();
          if (mounted) {
            setState(() {
              _allBanks      = banks;
              _filteredBanks = banks;
            });
          }
          // Persist for next time (3-week TTL).
          if (banks.isNotEmpty) {
            await cache.cacheBankList(banks);
          }
        }
      }
    } catch (_) {
    } finally {
      if (mounted) setState(() => _loadingBanks = false);
      _rebuildSheet(); // refresh the picker if it's currently open
      _bankFetchInFlight = false;
    }
  }

  /// Rebuilds the bank-picker bottom sheet if it is currently open.
  void _rebuildSheet() {
    final rebuild = _sheetSetState;
    if (rebuild != null) {
      try {
        rebuild(() {});
      } catch (_) {
        // Sheet was disposed between dismissal and this call — ignore.
      }
    }
  }

  void _filterBanks(String query) {
    setState(() {
      _filteredBanks = query.isEmpty
          ? _allBanks
          : _allBanks.where((b) {
              final name = (b['name'] ?? b['bankName'] ?? '').toString().toLowerCase();
              return name.contains(query.toLowerCase());
            }).toList();
    });
  }

  // ── Bank picker ────────────────────────────────────────────────────────────
  void _showBankPicker() {
    _searchCtrl.clear();
    _filteredBanks = _allBanks;

    // If we don't have the list yet (e.g. user tapped before the initial
    // fetch finished, or it failed), make sure a fetch is running. The sheet
    // will refresh itself via _rebuildSheet() the moment data arrives.
    if (_allBanks.isEmpty && !_bankFetchInFlight) {
      _fetchBanks();
    }

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setSheet) {
          // Expose this sheet's rebuilder so _fetchBanks() can refresh it.
          _sheetSetState = setSheet;
          return DraggableScrollableSheet(
            expand: false,
            initialChildSize: 0.85,
            builder: (_, scrollCtrl) => Column(children: [
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40, height: 4,
                decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2)),
              ),
              Padding(
                padding: const EdgeInsets.all(16),
                child: TextField(
                  controller: _searchCtrl,
                  onChanged: (v) {
                    _filterBanks(v);
                    setSheet(() {});
                  },
                  decoration: InputDecoration(
                    hintText: 'Search bank name',
                    prefixIcon: const Icon(Icons.search),
                    filled: true,
                    fillColor: const Color(0xFFF8FAF9),
                    border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(12),
                        borderSide: BorderSide.none),
                  ),
                ),
              ),
              if (_filteredBanks.isEmpty)
                Expanded(
                  child: Center(
                    child: _loadingBanks
                        ? const CircularProgressIndicator(
                            color: Color(0xFF34544A))
                        : Text(
                            _searchCtrl.text.isNotEmpty
                                ? 'No banks match your search'
                                : 'No banks available. Pull to retry.',
                            style: TextStyle(color: Colors.grey[600]),
                          ),
                  ),
                )
              else
                Expanded(
                  child: ListView.builder(
                    controller: scrollCtrl,
                    itemCount: _filteredBanks.length,
                    itemBuilder: (_, i) {
                      final bank = _filteredBanks[i];
                      final name = bank['name']?.toString() ?? '';
                      return ListTile(
                        leading: const Icon(Icons.account_balance,
                            color: Color(0xFF34544A)),
                        title: Text(name,
                            style: const TextStyle(
                                fontWeight: FontWeight.w500)),
                        onTap: () {
                          setState(() {
                            _selectedBank = bank;
                            _hasChanges   = true;
                          });
                          Navigator.pop(ctx);
                        },
                      );
                    },
                  ),
                ),
            ]),
          );
        },
      ),
    ).whenComplete(() => _sheetSetState = null);
  }

  // ── Validation ─────────────────────────────────────────────────────────────
  // The amount field shows comma-grouped text (e.g. "1,500.00"); strip the
  // commas before parsing or sending anywhere.
  String get _rawAmount => _amountCtrl.text.replaceAll(',', '').trim();

  bool get _isValid =>
      _selectedBank != null &&
      _accountNumberCtrl.text.length == 10 &&
      _accountNameCtrl.text.trim().isNotEmpty &&
      _sessionId != null &&
      (double.tryParse(_rawAmount) ?? 0) > 0 &&
      _hasEnoughBalance;

  bool get _hasEnoughBalance {
    final amount  = double.tryParse(_rawAmount) ?? 0;
    if (amount <= 0) return true; // let _isValid handle the zero case
    // Access balance via ConsumerState's ref — injected via build method
    return amount <= _userBalance;
  }

  double _userBalance = 0;

  // ── Navigate to summary ────────────────────────────────────────────────────
  void _proceed() {
    final bank     = _selectedBank!;
    // SafeHaven bank list returns 'bank_code' for the transfer field
    final bankCode = bank['bank_code']?.toString() ?? bank['bankCode']?.toString() ?? '';
    final bankName = bank['name']?.toString() ?? '';
    final accNum   = _accountNumberCtrl.text.trim();
    final accName  = _accountNameCtrl.text.trim();
    final amount   = _rawAmount;                 // digits only — for the backend
    final amountDisplay = _amountCtrl.text.trim(); // comma-grouped — for the UI
    final comment  = _commentCtrl.text.trim();

    final sessionId = _sessionId!; // Real session ID from SafeHaven name enquiry

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => TransactionSummaryScreen(
          data: TransactionSummaryData(
            service:       'Bank Transfer',
            from:          'Wallet',
            accountName:   accName,
            accountNumber: accNum,
            amount:        '₦$amountDisplay',
            description:   '$bankName • $comment',
            onConfirm: (pin) async {
              // POST /fund/withdraw
              final response = await ApiService.instance.postSimple(
                '/fund/withdraw',
                data: {
                  'bank':            bankCode,
                  'bank_name':       bankName,
                  'account_number':  accNum,
                  'account_name':    accName,
                  'amount':          amount,
                  'session_id':      sessionId,
                  'transaction_pin': pin,
                  if (comment.isNotEmpty) 'comment': comment,
                },
              );
              return {
                'success':         response.success,
                'message':         response.message,
                'reference':       response.data?['reference']?.toString() ?? '',
                // Prefer session_id echoed back by the backend; fall back to
                // the one we obtained from the SafeHaven name enquiry so the
                // receipt shown immediately after sending matches the value
                // later returned by transaction history.
                'session_id':      response.data?['session_id']?.toString() ?? sessionId,
                'description':     'Transfer to $accName',
                'initial_balance': response.data?['initial_balance']?.toString() ?? '',
                'final_balance':   response.data?['final_balance']?.toString() ?? '',
              };
            },
          ),
        ),
      ),
    );
  }

  // ── Build ──────────────────────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    // Read current balance for validation
    final user = ref.watch(authNotifierProvider).user;
    _userBalance = double.tryParse(user?.balance ?? '0') ?? 0;

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          const SizedBox(height: 30),
          Row(children: [
            IconButton(
              icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
              onPressed: () => Navigator.of(context).pop(),
            ),
            Expanded(
              child: Text('Bank Transfer',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                      fontSize: 22.sp,
                      fontWeight: FontWeight.w600,
                      color: Colors.white)),
            ),
            const SizedBox(width: 48),
          ]),
          const SizedBox(height: 20),
          Expanded(
            child: Container(
              decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                      topLeft: Radius.circular(20),
                      topRight: Radius.circular(20))),
              child: Padding(
                padding: const EdgeInsets.only(top: 20),
                child: Container(
                  decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                          topRight: Radius.circular(20))),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SizedBox(height: 8.h),

                        // ── Balance display ──────────────────────────────────
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 14, vertical: 10),
                          decoration: BoxDecoration(
                            color: _userBalance <= 0
                                ? Colors.red.withOpacity(0.08)
                                : const Color(0xFF34544A).withOpacity(0.06),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(
                              color: _userBalance <= 0
                                  ? Colors.red.withOpacity(0.3)
                                  : const Color(0xFF34544A).withOpacity(0.2),
                            ),
                          ),
                          child: Row(children: [
                            Icon(
                              _userBalance <= 0
                                  ? Icons.warning_amber_rounded
                                  : Icons.account_balance_wallet_outlined,
                              size: 16,
                              color: _userBalance <= 0
                                  ? Colors.red[700]
                                  : const Color(0xFF34544A),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              _userBalance <= 0
                                  ? 'Insufficient balance — please fund your wallet first'
                                  // : 'Available Balance: ₦${_userBalance.toStringAsFixed(2)}',
                                  : 'Available Balance: ₦${formatNumber(_userBalance.toStringAsFixed(2))}',
                                  
                                  
                              style: TextStyle(
                                fontSize: 12.sp,
                                fontWeight: FontWeight.w600,
                                color: _userBalance <= 0
                                    ? Colors.red[700]
                                    : const Color(0xFF34544A),
                              ),
                            ),
                          ]),
                        ),
                        SizedBox(height: 16.h),

                        // ── Destination Bank ─────────────────────────────────
                        _label('Destination Bank'),
                        GestureDetector(
                          onTap: _showBankPicker,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                                horizontal: 16, vertical: 18),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAF9),
                              borderRadius: BorderRadius.circular(12.r),
                              border: _selectedBank != null
                                  ? Border.all(
                                      color: const Color(0xFF34544A),
                                      width: 1.5)
                                  : null,
                            ),
                            child: Row(children: [
                              Icon(Icons.account_balance,
                                  color: _selectedBank != null
                                      ? const Color(0xFF34544A)
                                      : Colors.grey[400]),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  _selectedBank != null
                                      ? (_selectedBank!['name']?.toString() ?? '')
                                      : _loadingBanks
                                          ? 'Loading banks...'
                                          : 'Select Bank',
                                  style: TextStyle(
                                      color: _selectedBank != null
                                          ? Colors.black87
                                          : Colors.grey[400],
                                      fontSize: 16.sp),
                                ),
                              ),
                              Icon(Icons.chevron_right,
                                  color: Colors.grey[400]),
                            ]),
                          ),
                        ),
                        SizedBox(height: 18.h),

                        // ── Account Number ────────────────────────────────────
                        _label('Account Number'),
                        _textField(
                          controller: _accountNumberCtrl,
                          hint: '0123456789',
                          type: TextInputType.number,
                          maxLength: 10,
                        ),
                        SizedBox(height: 18.h),

                        // ── Account Name (auto-resolved via name enquiry) ──────
                        if (_isResolving)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(children: [
                              const SizedBox(width: 16, height: 16,
                                  child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Color(0xFF34544A))),
                              const SizedBox(width: 8),
                              Text('Verifying account...',
                                  style: TextStyle(
                                      color: Colors.grey[600], fontSize: 13.sp)),
                            ]),
                          ),
                        if (_resolvedName != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(children: [
                              const Icon(Icons.check_circle,
                                  color: Colors.green, size: 18),
                              const SizedBox(width: 6),
                              Text(_resolvedName!,
                                  style: const TextStyle(
                                      color: Colors.green,
                                      fontWeight: FontWeight.bold)),
                            ]),
                          ),
                        if (_resolveError != null)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: Row(children: [
                              const Icon(Icons.error_outline,
                                  color: Colors.red, size: 18),
                              const SizedBox(width: 6),
                              Text(_resolveError!,
                                  style: const TextStyle(
                                      color: Colors.red, fontSize: 13)),
                            ]),
                          ),
                        SizedBox(height: 18.h),
                        _label('Account Name'),
                        _textField(
                          controller: _accountNameCtrl,
                          hint: 'Auto-filled after verification',
                          readOnly: _resolvedName != null,
                        ),
                        SizedBox(height: 18.h),

                        // ── Amount ────────────────────────────────────────────
                      
                        const SizedBox(height: 6),
                        _textField(
                          controller: _amountCtrl,
                          hint: '₦ Enter Amount',
                          type: const TextInputType.numberWithOptions(
                              decimal: true),
                          inputFormatters: [
                            ThousandsSeparatorInputFormatter(),
                          ],
                        ),
                        if ((double.tryParse(_rawAmount) ?? 0) > _userBalance &&
                            _rawAmount.isNotEmpty)
                          Padding(
                            padding: const EdgeInsets.only(top: 6),
                            child: Row(children: [
                              const Icon(Icons.warning_amber_rounded,
                                  color: Colors.red, size: 14),
                              const SizedBox(width: 4),
                              Text('Insufficient balance',
                                  style: TextStyle(
                                      color: Colors.red, fontSize: 12.sp)),
                            ]),
                          ),
                        SizedBox(height: 18.h),

                        // ── Narration (optional) ──────────────────────────────
                        _label('Narration (Optional)'),
                        _textField(
                          controller: _commentCtrl,
                          hint: 'What is this transfer for?',
                        ),
                        SizedBox(height: 36.h),

                        // ── Next button ───────────────────────────────────────
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: _isValid
                                  ? const Color(0xFF34544A)
                                  : Colors.grey[300],
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(
                                  borderRadius:
                                      BorderRadius.circular(32.r)),
                              padding:
                                  EdgeInsets.symmetric(vertical: 16.h),
                            ),
                            onPressed: _isValid && _userBalance > 0 ? _proceed : null,
                            child: Text('Next',
                                style: TextStyle(
                                    fontSize: 18.sp,
                                    fontWeight: FontWeight.bold,
                                    color: _isValid
                                        ? Colors.white
                                        : Colors.grey[600])),
                          ),
                        ),
                        SizedBox(height: 24.h),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 6),
    child: Text(text,
        style: TextStyle(
            fontSize: 13.sp,
            color: Colors.grey[600],
            fontWeight: FontWeight.w500)),
  );

  Widget _textField({
    required TextEditingController controller,
    required String hint,
    TextInputType type = TextInputType.text,
    int? maxLength,
    bool readOnly = false,
    List<TextInputFormatter>? inputFormatters,
  }) =>
      TextField(
        controller: controller,
        keyboardType: type,
        maxLength: maxLength,
        readOnly: readOnly,
        inputFormatters: inputFormatters,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle:
              TextStyle(color: Colors.grey[400], fontSize: 15.sp),
          filled: true,
          fillColor: const Color(0xFFF8FAF9),
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12.r),
              borderSide: BorderSide.none),
          focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12.r),
              borderSide: const BorderSide(
                  color: Color(0xFF34544A), width: 1.5)),
          counterText: '',
          contentPadding: EdgeInsets.symmetric(
              vertical: 18.h, horizontal: 16.w),
        ),
      );
}

String formatNumber(String val) {
  final n = double.tryParse(val);
  if (n == null) return val;
  return n.toStringAsFixed(2).replaceAllMapped(
        RegExp(r'(\d)(?=(\d{3})+\.)', caseSensitive: false),
        (m) => '${m[1]},',
      );
}

/// Adds thousand separators to a numeric text field as the user types, while
/// keeping the cursor in a sensible place. Allows a single decimal point and
/// up to 2 decimal places. The underlying digits (with commas) live in the
/// controller — strip commas with `text.replaceAll(',', '')` before parsing
/// or sending to the backend.
class ThousandsSeparatorInputFormatter extends TextInputFormatter {
  final NumberFormat _fmt = NumberFormat('#,##0.##', 'en_US');

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    String text = newValue.text;
    if (text.isEmpty) return newValue;

    // Keep only digits and at most one dot.
    text = text.replaceAll(',', '');
    if ('.'.allMatches(text).length > 1) {
      // Reject the extra dot — keep the old value.
      return oldValue;
    }

    // Split off the decimal part so we don't reformat it away while typing.
    final hasDot = text.contains('.');
    final parts = text.split('.');
    String intPart = parts[0].replaceAll(RegExp(r'[^0-9]'), '');
    String decPart = parts.length > 1
        ? parts[1].replaceAll(RegExp(r'[^0-9]'), '')
        : '';
    if (decPart.length > 2) decPart = decPart.substring(0, 2);

    String formatted;
    if (intPart.isEmpty) {
      formatted = hasDot ? '0.$decPart' : '';
    } else {
      final intVal = int.tryParse(intPart) ?? 0;
      formatted = _fmt.format(intVal);
      if (hasDot) formatted = '$formatted.$decPart';
    }

    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}