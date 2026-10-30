// // lib/screen/foreign_currency/create_account_flow.dart

// import 'dart:convert';
// import 'dart:io';
// import 'package:flutter/material.dart';
// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import 'package:flutter_screenutil/flutter_screenutil.dart';
// import 'package:file_picker/file_picker.dart';
// import '../../providers/foreign_currency_provider.dart';
// import '../../models/foreign_currency/foreign_account_model.dart';

// class CreateAccountFlow extends ConsumerStatefulWidget {
//   final String currency;
//   final String currencyCode;

//   const CreateAccountFlow({
//     super.key,
//     required this.currency,
//     required this.currencyCode,
//   });

//   @override
//   ConsumerState<CreateAccountFlow> createState() => _CreateAccountFlowState();
// }

// class _CreateAccountFlowState extends ConsumerState<CreateAccountFlow> {

//   int _step = 0;
//   static const int _totalSteps = 4;

//   // ── Step 0: Identity & Employment ─────────────────────────────────────────
//   final _bvnCtrl        = TextEditingController();
//   final _occupationCtrl = TextEditingController();
//   String _employmentStatus = '';

//   // ── Step 1: Address & Funding ──────────────────────────────────────────────
//   final _address1Ctrl    = TextEditingController();
//   final _address2Ctrl    = TextEditingController();
//   final _cityCtrl        = TextEditingController();
//   final _stateCtrl       = TextEditingController();
//   final _countryCtrl     = TextEditingController();
//   final _postalCtrl      = TextEditingController();
//   String _primaryPurpose       = '';
//   String _sourceOfFunds        = '';
//   final _monthlyInflowCtrl     = TextEditingController();

//   // ── Step 2: Document Information ──────────────────────────────────────────
//   String _documentType  = ''; // 'passport' | 'national_id'
//   DateTime? _docIssueDate;
//   DateTime? _docExpiryDate;

//   // ── Step 3: Document Upload ────────────────────────────────────────────────
//   String? _doc1Base64;   // ID document (passport or NIN)
//   String? _doc2Base64;   // Utility bill
//   File?   _doc1File;
//   File?   _doc2File;

//   // ── Options ────────────────────────────────────────────────────────────────
//   final _empOptions = [
//     {'label': 'Employed',      'value': 'employed'},
//     {'label': 'Self Employed', 'value': 'self_employed'},
//     {'label': 'Student',       'value': 'student'},
//     {'label': 'Retired',       'value': 'retired'},
//     {'label': 'Unemployed',    'value': 'unemployed'},
//   ];

//   final _purposeOptions = [
//     {'label': 'Personal',   'value': 'personal'},
//     {'label': 'Business',   'value': 'business'},
//     {'label': 'Salary',     'value': 'salary'},
//     {'label': 'Freelance',  'value': 'freelance'},
//   ];

//   final _sourceOptions = [
//     {'label': 'Salary',              'value': 'salary'},
//     {'label': 'Savings',             'value': 'savings'},
//     {'label': 'Business',            'value': 'business'},
//     {'label': 'Freelance',           'value': 'freelance'},
//     {'label': 'Investment',          'value': 'investment'},
//     {'label': 'Government Benefits', 'value': 'government_benefits'},
//     {'label': 'Pension',             'value': 'pension'},
//   ];

//   // Document type options matching backend exactly
//   final _docTypeOptions = [
//     {'label': 'International Passport', 'value': 'passport'},
//     {'label': 'NIN',                    'value': 'national_id'},
//   ];

//   @override
//   void dispose() {
//     _bvnCtrl.dispose();
//     _occupationCtrl.dispose();
//     _address1Ctrl.dispose();
//     _address2Ctrl.dispose();
//     _cityCtrl.dispose();
//     _stateCtrl.dispose();
//     _countryCtrl.dispose();
//     _postalCtrl.dispose();
//     _monthlyInflowCtrl.dispose();
//     super.dispose();
//   }

//   // ── Validation ─────────────────────────────────────────────────────────────

//   bool get _step0Valid =>
//       _bvnCtrl.text.length == 11 &&
//       _occupationCtrl.text.isNotEmpty &&
//       _employmentStatus.isNotEmpty;

//   bool get _step1Valid =>
//       _address1Ctrl.text.isNotEmpty &&
//       _cityCtrl.text.isNotEmpty &&
//       _stateCtrl.text.isNotEmpty &&
//       _countryCtrl.text.isNotEmpty &&
//       _postalCtrl.text.isNotEmpty &&
//       _primaryPurpose.isNotEmpty &&
//       _sourceOfFunds.isNotEmpty &&
//       _monthlyInflowCtrl.text.isNotEmpty;

//   bool get _step2Valid => _documentType.isNotEmpty;

//   bool get _step3Valid => _doc1Base64 != null && _doc2Base64 != null;

//   // ── File picker ────────────────────────────────────────────────────────────

//   Future<void> _pickFile(bool isDoc1) async {
//     final result = await FilePicker.platform.pickFiles(
//       type: FileType.custom,
//       allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
//     );
//     if (result == null) return;
//     final file  = File(result.files.single.path!);
//     final bytes = await file.readAsBytes();
//     final b64   = base64Encode(bytes);
//     final ext   = result.files.single.extension ?? 'jpg';
//     final mime  = ext == 'pdf'
//         ? 'data:application/pdf;base64,'
//         : 'data:image/$ext;base64,';
//     setState(() {
//       if (isDoc1) { _doc1File = file; _doc1Base64 = '$mime$b64'; }
//       else        { _doc2File = file; _doc2Base64 = '$mime$b64'; }
//     });
//   }

//   // ── Date picker ────────────────────────────────────────────────────────────

//   Future<void> _pickDate(bool isIssue) async {
//     final picked = await showDatePicker(
//       context: context,
//       initialDate: DateTime.now(),
//       firstDate: DateTime(1900),
//       lastDate: DateTime(2100),
//       builder: (ctx, child) => Theme(
//         data: ThemeData.light().copyWith(
//           colorScheme: const ColorScheme.light(
//             primary: Color(0xFF34544A), onPrimary: Colors.white),
//         ),
//         child: child!,
//       ),
//     );
//     if (picked != null) {
//       setState(() {
//         if (isIssue) _docIssueDate = picked;
//         else _docExpiryDate = picked;
//       });
//     }
//   }

//   String _dateStr(DateTime d) =>
//       '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

//   String _displayDate(DateTime d) =>
//       '${d.day.toString().padLeft(2,'0')}/${d.month.toString().padLeft(2,'0')}/${d.year}';

//   // ── Submit ─────────────────────────────────────────────────────────────────

//   Future<void> _submit() async {
//     showDialog(
//       context: context,
//       barrierDismissible: false,
//       builder: (_) => const Center(
//           child: CircularProgressIndicator(color: Color(0xFF34544A))),
//     );

//     final payload = ForeignAccountRequestPayload(
//       currency:             widget.currencyCode,
//       bvn:                  _bvnCtrl.text.trim(),
//       addressLine1:         _address1Ctrl.text.trim(),
//       addressLine2:         _address2Ctrl.text.trim().isEmpty
//                                 ? null : _address2Ctrl.text.trim(),
//       city:                 _cityCtrl.text.trim(),
//       state:                _stateCtrl.text.trim(),
//       country:              _countryCtrl.text.trim(),
//       postalCode:           _postalCtrl.text.trim(),
//       employmentStatus:     _employmentStatus,
//       occupation:           _occupationCtrl.text.trim(),
//       primaryPurpose:       _primaryPurpose,
//       sourceOfFunds:        _sourceOfFunds,
//       expectedMonthlyInflow: double.tryParse(_monthlyInflowCtrl.text) ?? 0,
//       document:             _doc1Base64!,
//       documentType:         _documentType,
//       documentIssueDate:    _docIssueDate != null ? _dateStr(_docIssueDate!) : null,
//       documentExpiryDate:   _docExpiryDate != null ? _dateStr(_docExpiryDate!) : null,
//       document2:            _doc2Base64!,
//       document2Type:        'utility_bill',
//     );

//     final response = await ref
//         .read(foreignCurrencyNotifierProvider.notifier)
//         .requestForeignAccount(payload);

//     if (!mounted) return;
//     Navigator.of(context).pop(); // close loader

//     if (response.success) {
//       setState(() => _step = _totalSteps); // success screen
//     } else {
//       ScaffoldMessenger.of(context).showSnackBar(SnackBar(
//         content: Text(response.message.isNotEmpty
//             ? response.message : 'Submission failed. Please try again.'),
//         backgroundColor: Colors.red,
//       ));
//     }
//   }

//   // ── Build ──────────────────────────────────────────────────────────────────

//   @override
//   Widget build(BuildContext context) {
//     final isSuccess = _step == _totalSteps;
//     return Scaffold(
//       backgroundColor: Colors.white,
//       appBar: AppBar(
//         backgroundColor: Colors.white,
//         elevation: 0,
//         leading: IconButton(
//           icon: const Icon(Icons.arrow_back_ios, color: Colors.black),
//           onPressed: () {
//             if (_step == 0 || isSuccess) Navigator.of(context).pop();
//             else setState(() => _step--);
//           },
//         ),
//         title: Text(
//           '${widget.currency} Account Creation',
//           style: const TextStyle(
//               color: Colors.black, fontWeight: FontWeight.bold, fontSize: 17),
//         ),
//         centerTitle: true,
//       ),
//       body: Column(
//         children: [
//           if (!isSuccess)
//             Padding(
//               padding: const EdgeInsets.symmetric(horizontal: 20),
//               child: LinearProgressIndicator(
//                 value: (_step + 1) / _totalSteps,
//                 backgroundColor: Colors.grey[300],
//                 valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF34544A)),
//                 minHeight: 5,
//                 borderRadius: BorderRadius.circular(4),
//               ),
//             ),
//           Expanded(
//             child: SingleChildScrollView(
//               padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
//               child: isSuccess ? _successView() : _buildCurrentStep(),
//             ),
//           ),
//         ],
//       ),
//     );
//   }

//   Widget _buildCurrentStep() {
//     switch (_step) {
//       case 0: return _step0Identity();
//       case 1: return _step1Address();
//       case 2: return _step2DocumentInfo();
//       case 3: return _step3Upload();
//       default: return _successView();
//     }
//   }

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 0 — Identity & Employment
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step0Identity() => _StepShell(
//     title: 'Identity & Employment',
//     subtitle: 'Provide BVN and employment information',
//     isValid: _step0Valid,
//     onNext: () => setState(() => _step++),
//     child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
//       _label('BVN (11 digits)'),
//       _textField(_bvnCtrl, 'Enter your BVN',
//           type: TextInputType.number, maxLength: 11,
//           onChanged: (_) => setState(() {})),
//       if (_bvnCtrl.text.isNotEmpty && _bvnCtrl.text.length != 11)
//         Padding(
//           padding: const EdgeInsets.only(top: 4),
//           child: Text('BVN must be exactly 11 digits',
//               style: TextStyle(color: Colors.red[400], fontSize: 12)),
//         ),
//       const SizedBox(height: 20),
//       _label('Occupation'),
//       _textField(_occupationCtrl, 'Enter your occupation',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 20),
//       _label('Employment Status'),
//       _dropdownField(
//         hint: 'Select employment status',
//         value: _employmentStatus.isEmpty ? null : _employmentStatus,
//         options: _empOptions,
//         onChanged: (v) => setState(() => _employmentStatus = v ?? ''),
//       ),
//     ]),
//   );

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 1 — Address & Funding
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step1Address() => _StepShell(
//     title: 'Address & Funding',
//     subtitle: 'Tell us where you live and how you will use the account',
//     isValid: _step1Valid,
//     onNext: () => setState(() => _step++),
//     child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
//       _label('Address Line 1'),
//       _textField(_address1Ctrl, 'Enter Address Line 1',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('Address Line 2 (Optional)'),
//       _textField(_address2Ctrl, 'Enter Address Line 2 (Optional)'),
//       const SizedBox(height: 16),
//       _label('City'),
//       _textField(_cityCtrl, 'Enter City',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('State'),
//       _textField(_stateCtrl, 'Enter State',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('Country'),
//       _textField(_countryCtrl, 'Enter Country',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('Postal Code'),
//       _textField(_postalCtrl, 'Enter Postal Code',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 20),
//       _label('Primary Purpose'),
//       _dropdownField(
//         hint: 'Select an option',
//         value: _primaryPurpose.isEmpty ? null : _primaryPurpose,
//         options: _purposeOptions,
//         onChanged: (v) => setState(() => _primaryPurpose = v ?? ''),
//       ),
//       const SizedBox(height: 16),
//       _label('Source of Funds'),
//       _dropdownField(
//         hint: 'Select an option',
//         value: _sourceOfFunds.isEmpty ? null : _sourceOfFunds,
//         options: _sourceOptions,
//         onChanged: (v) => setState(() => _sourceOfFunds = v ?? ''),
//       ),
//       const SizedBox(height: 16),
//       _label('Expected Monthly Inflow'),
//       _textField(_monthlyInflowCtrl, 'Enter Expected Monthly Inflow',
//           type: TextInputType.number,
//           onChanged: (_) => setState(() {})),
//     ]),
//   );

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 2 — Document Information
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step2DocumentInfo() => _StepShell(
//     title: 'Document Information',
//     subtitle: 'Provide document types and dates',
//     isValid: _step2Valid,
//     onNext: () => setState(() => _step++),
//     child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
//       _label('Document Type (document)'),
//       _dropdownField(
//         hint: 'Select an option',
//         value: _documentType.isEmpty ? null : _documentType,
//         options: _docTypeOptions,
//         onChanged: (v) => setState(() => _documentType = v ?? ''),
//       ),
//       const SizedBox(height: 20),
//       _label('Document Issue Date (Optional)'),
//       _dateTile(
//         hint: 'Select date',
//         date: _docIssueDate,
//         onTap: () => _pickDate(true),
//       ),
//       const SizedBox(height: 16),
//       _label('Document Expiry Date (Optional)'),
//       _dateTile(
//         hint: 'Select date',
//         date: _docExpiryDate,
//         onTap: () => _pickDate(false),
//       ),
//     ]),
//   );

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 3 — Document Upload
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step3Upload() {
//     final docLabel = _docTypeOptions
//         .firstWhere((d) => d['value'] == _documentType,
//             orElse: () => {'label': 'ID Document', 'value': ''})['label']!;

//     return _StepShell(
//       title: 'Document Upload',
//       subtitle: 'Please upload the required documents',
//       isValid: _step3Valid,
//       onNext: _submit,
//       nextLabel: 'Submit Application',
//       child: Column(children: [
//         _UploadTile(
//           label: docLabel,
//           required: true,
//           subtitle: 'Upload file for document (base64 encoded at submit)',
//           file: _doc1File,
//           onTap: () => _pickFile(true),
//         ),
//         const SizedBox(height: 16),
//         _UploadTile(
//           label: 'Utility',
//           required: true,
//           subtitle: 'Upload your most recent utility document (not older than 3 months)',
//           file: _doc2File,
//           onTap: () => _pickFile(false),
//         ),
//       ]),
//     );
//   }

//   // ─────────────────────────────────────────────────────────────────────────
//   // SUCCESS
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _successView() => Center(
//     child: Column(
//       mainAxisAlignment: MainAxisAlignment.center,
//       children: [
//         const SizedBox(height: 40),
//         Container(
//           width: 88, height: 88,
//           decoration: const BoxDecoration(
//               color: Color(0xFFDDF3E8), shape: BoxShape.circle),
//           child: const Icon(Icons.check_circle_rounded,
//               color: Color(0xFF34544A), size: 56),
//         ),
//         const SizedBox(height: 24),
//         Text('Application Submitted!',
//             style: TextStyle(fontSize: 24.sp, fontWeight: FontWeight.bold),
//             textAlign: TextAlign.center),
//         const SizedBox(height: 12),
//         Padding(
//           padding: const EdgeInsets.symmetric(horizontal: 32),
//           child: Text(
//             'Your ${widget.currency} account request is being reviewed. '
//             'We\'ll notify you within 3–5 working days.',
//             style: TextStyle(fontSize: 15.sp, color: Colors.grey[600], height: 1.5),
//             textAlign: TextAlign.center,
//           ),
//         ),
//         const SizedBox(height: 40),
//         SizedBox(
//           width: double.infinity,
//           child: ElevatedButton(
//             onPressed: () => Navigator.of(context).pop('completed'),
//             style: ElevatedButton.styleFrom(
//               backgroundColor: const Color(0xFF34544A),
//               shape: RoundedRectangleBorder(
//                   borderRadius: BorderRadius.circular(12)),
//               padding: const EdgeInsets.symmetric(vertical: 14),
//             ),
//             child: const Text('Back to Wallet',
//                 style: TextStyle(
//                     fontWeight: FontWeight.bold,
//                     fontSize: 16,
//                     color: Colors.white)),
//           ),
//         ),
//       ],
//     ),
//   );

//   // ── Widget helpers ──────────────────────────────────────────────────────────

//   Widget _label(String text) => Padding(
//     padding: const EdgeInsets.only(bottom: 8),
//     child: Text(text,
//         style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
//   );

//   Widget _textField(
//     TextEditingController ctrl,
//     String hint, {
//     TextInputType type = TextInputType.text,
//     int? maxLength,
//     ValueChanged<String>? onChanged,
//   }) =>
//       TextField(
//         controller: ctrl,
//         keyboardType: type,
//         maxLength: maxLength,
//         onChanged: onChanged,
//         decoration: InputDecoration(
//           hintText: hint,
//           hintStyle: TextStyle(color: Colors.grey[400]),
//           filled: true,
//           fillColor: const Color(0xFFF4F6F5),
//           border: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(10),
//               borderSide: BorderSide.none),
//           counterText: '',
//           contentPadding:
//               const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
//         ),
//       );

//   Widget _dropdownField({
//     required String hint,
//     required String? value,
//     required List<Map<String, String>> options,
//     required ValueChanged<String?> onChanged,
//   }) =>
//       Container(
//         padding: const EdgeInsets.symmetric(horizontal: 12),
//         decoration: BoxDecoration(
//           color: const Color(0xFFF4F6F5),
//           borderRadius: BorderRadius.circular(10),
//           border: Border.all(color: Colors.grey[300]!),
//         ),
//         child: DropdownButton<String>(
//           value: value,
//           hint: Text(hint, style: TextStyle(color: Colors.grey[400])),
//           isExpanded: true,
//           underline: const SizedBox(),
//           dropdownColor: Colors.white,
//           items: options
//               .map((o) => DropdownMenuItem(
//                     value: o['value'],
//                     child: Text(o['label']!),
//                   ))
//               .toList(),
//           onChanged: onChanged,
//         ),
//       );

//   Widget _dateTile({
//     required String hint,
//     required DateTime? date,
//     required VoidCallback onTap,
//   }) =>
//       InkWell(
//         onTap: onTap,
//         child: Container(
//           padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
//           decoration: BoxDecoration(
//             color: const Color(0xFFF4F6F5),
//             borderRadius: BorderRadius.circular(10),
//             border: Border.all(color: Colors.grey[300]!),
//           ),
//           child: Row(children: [
//             Expanded(
//               child: Text(
//                 date != null ? _displayDate(date) : hint,
//                 style: TextStyle(
//                     color: date != null ? Colors.black87 : Colors.grey[400],
//                     fontSize: 15),
//               ),
//             ),
//             Icon(Icons.calendar_month_outlined,
//                 color: Colors.grey[600], size: 22),
//           ]),
//         ),
//       );
// }

// // ─── Step Shell ───────────────────────────────────────────────────────────────

// class _StepShell extends StatelessWidget {
//   final String title;
//   final String subtitle;
//   final bool isValid;
//   final VoidCallback onNext;
//   final String nextLabel;
//   final Widget child;

//   const _StepShell({
//     required this.title,
//     required this.subtitle,
//     required this.isValid,
//     required this.onNext,
//     required this.child,
//     this.nextLabel = 'Next',
//   });

//   @override
//   Widget build(BuildContext context) {
//     return Column(
//       crossAxisAlignment: CrossAxisAlignment.start,
//       children: [
//         Text(title,
//             style: const TextStyle(
//                 fontSize: 22, fontWeight: FontWeight.bold)),
//         const SizedBox(height: 6),
//         Text(subtitle,
//             style: TextStyle(fontSize: 14, color: Colors.grey[600])),
//         const SizedBox(height: 24),
//         child,
//         const SizedBox(height: 32),
//         SizedBox(
//           width: double.infinity,
//           child: ElevatedButton(
//             onPressed: isValid ? onNext : null,
//             style: ElevatedButton.styleFrom(
//               backgroundColor:
//                   isValid ? const Color(0xFF34544A) : Colors.grey[300],
//               shape: RoundedRectangleBorder(
//                   borderRadius: BorderRadius.circular(32)),
//               padding: const EdgeInsets.symmetric(vertical: 16),
//             ),
//             child: Text(nextLabel,
//                 style: TextStyle(
//                     fontWeight: FontWeight.bold,
//                     fontSize: 16,
//                     color: isValid ? Colors.white : Colors.grey[600])),
//           ),
//         ),
//         const SizedBox(height: 24),
//       ],
//     );
//   }
// }

// // ─── Upload Tile ──────────────────────────────────────────────────────────────

// class _UploadTile extends StatelessWidget {
//   final String label;
//   final bool required;
//   final String subtitle;
//   final File? file;
//   final VoidCallback onTap;

//   const _UploadTile({
//     required this.label,
//     required this.required,
//     required this.subtitle,
//     required this.file,
//     required this.onTap,
//   });

//   @override
//   Widget build(BuildContext context) {
//     final uploaded = file != null;
//     return InkWell(
//       onTap: onTap,
//       borderRadius: BorderRadius.circular(12),
//       child: Container(
//         padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
//         decoration: BoxDecoration(
//           color: uploaded
//               ? Colors.green.withOpacity(0.05)
//               : const Color(0xFFF4F6F5),
//           borderRadius: BorderRadius.circular(12),
//           border: Border.all(
//               color: uploaded ? Colors.green : Colors.grey[300]!,
//               width: uploaded ? 1.5 : 1),
//         ),
//         child: Row(children: [
//           Icon(
//             uploaded ? Icons.insert_drive_file : Icons.upload_file_outlined,
//             color: uploaded ? Colors.green : Colors.grey[600],
//             size: 32,
//           ),
//           const SizedBox(width: 14),
//           Expanded(
//             child: Column(
//                 crossAxisAlignment: CrossAxisAlignment.start,
//                 children: [
//               Row(children: [
//                 Text(label,
//                     style: const TextStyle(
//                         fontWeight: FontWeight.bold, fontSize: 15)),
//                 if (required)
//                   const Text(' *',
//                       style: TextStyle(
//                           color: Colors.red,
//                           fontWeight: FontWeight.bold,
//                           fontSize: 15)),
//               ]),
//               const SizedBox(height: 4),
//               Text(
//                 uploaded ? 'File uploaded ✓' : subtitle,
//                 style: TextStyle(
//                     fontSize: 13,
//                     color: uploaded ? Colors.green : Colors.grey[600]),
//               ),
//             ]),
//           ),
//           Icon(Icons.chevron_right,
//               color: Colors.grey[400], size: 22),
//         ]),
//       ),
//     );
//   }
// }


// lib/screen/foreign_currency/create_account_flow.dart


// import 'dart:convert';
// import 'dart:io';
// import 'package:flutter/material.dart';
// import 'package:flutter_riverpod/flutter_riverpod.dart';
// import 'package:flutter_screenutil/flutter_screenutil.dart';
// import 'package:file_picker/file_picker.dart';
// import '../../providers/foreign_currency_provider.dart';
// import '../../models/foreign_currency/foreign_account_model.dart';
// import '../../widgets/pin_entry_screen.dart';

// class CreateAccountFlow extends ConsumerStatefulWidget {
//   final String currency;
//   final String currencyCode;

//   const CreateAccountFlow({
//     super.key,
//     required this.currency,
//     required this.currencyCode,
//   });

//   @override
//   ConsumerState<CreateAccountFlow> createState() => _CreateAccountFlowState();
// }

// class _CreateAccountFlowState extends ConsumerState<CreateAccountFlow> {

//   int _step = 0;
//   static const int _totalSteps = 4;

//   // ── Step 0: Identity & Employment ─────────────────────────────────────────
//   final _bvnCtrl        = TextEditingController();
//   final _occupationCtrl = TextEditingController();
//   String _employmentStatus = '';

//   // ── Step 1: Address & Funding ──────────────────────────────────────────────
//   final _address1Ctrl    = TextEditingController();
//   final _address2Ctrl    = TextEditingController();
//   final _cityCtrl        = TextEditingController();
//   final _stateCtrl       = TextEditingController();
//   final _countryCtrl     = TextEditingController();
//   final _postalCtrl      = TextEditingController();
//   String _primaryPurpose       = '';
//   String _sourceOfFunds        = '';
//   final _monthlyInflowCtrl     = TextEditingController();

//   // ── Step 2: Document Information ──────────────────────────────────────────
//   String _documentType  = ''; // 'passport' | 'national_id'
//   DateTime? _docIssueDate;
//   DateTime? _docExpiryDate;

//   // ── Step 3: Document Upload ────────────────────────────────────────────────
//   String? _doc1Base64;   // ID document (passport or NIN)
//   String? _doc2Base64;   // Utility bill
//   File?   _doc1File;
//   File?   _doc2File;

//   // ── Options ────────────────────────────────────────────────────────────────
//   final _empOptions = [
//     {'label': 'Employed',      'value': 'employed'},
//     {'label': 'Self Employed', 'value': 'self_employed'},
//     {'label': 'Student',       'value': 'student'},
//     {'label': 'Retired',       'value': 'retired'},
//     {'label': 'Unemployed',    'value': 'unemployed'},
//   ];

//   final _purposeOptions = [
//     {'label': 'Personal',   'value': 'personal'},
//     {'label': 'Business',   'value': 'business'},
//     {'label': 'Salary',     'value': 'salary'},
//     {'label': 'Freelance',  'value': 'freelance'},
//   ];

//   final _sourceOptions = [
//     {'label': 'Salary',              'value': 'salary'},
//     {'label': 'Savings',             'value': 'savings'},
//     {'label': 'Business',            'value': 'business'},
//     {'label': 'Freelance',           'value': 'freelance'},
//     {'label': 'Investment',          'value': 'investment'},
//     {'label': 'Government Benefits', 'value': 'government_benefits'},
//     {'label': 'Pension',             'value': 'pension'},
//   ];

//   // Document type options matching backend exactly
//   final _docTypeOptions = [
//     {'label': 'International Passport', 'value': 'passport'},
//     {'label': 'NIN',                    'value': 'national_id'},
//   ];

//   @override
//   void dispose() {
//     _bvnCtrl.dispose();
//     _occupationCtrl.dispose();
//     _address1Ctrl.dispose();
//     _address2Ctrl.dispose();
//     _cityCtrl.dispose();
//     _stateCtrl.dispose();
//     _countryCtrl.dispose();
//     _postalCtrl.dispose();
//     _monthlyInflowCtrl.dispose();
//     super.dispose();
//   }

//   // ── Validation ─────────────────────────────────────────────────────────────

//   bool get _step0Valid =>
//       _bvnCtrl.text.length == 11 &&
//       _occupationCtrl.text.isNotEmpty &&
//       _employmentStatus.isNotEmpty;

//   bool get _step1Valid =>
//       _address1Ctrl.text.isNotEmpty &&
//       _cityCtrl.text.isNotEmpty &&
//       _stateCtrl.text.isNotEmpty &&
//       _countryCtrl.text.isNotEmpty &&
//       _postalCtrl.text.isNotEmpty &&
//       _primaryPurpose.isNotEmpty &&
//       _sourceOfFunds.isNotEmpty &&
//       _monthlyInflowCtrl.text.isNotEmpty;

//   bool get _step2Valid => _documentType.isNotEmpty;

//   bool get _step3Valid => _doc1Base64 != null && _doc2Base64 != null;

//   // ── File picker ────────────────────────────────────────────────────────────

//   Future<void> _pickFile(bool isDoc1) async {
//     final result = await FilePicker.platform.pickFiles(
//       type: FileType.custom,
//       allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
//     );
//     if (result == null) return;
//     final file  = File(result.files.single.path!);
//     final bytes = await file.readAsBytes();
//     final b64   = base64Encode(bytes);
//     final ext   = result.files.single.extension ?? 'jpg';
//     final mime  = ext == 'pdf'
//         ? 'data:application/pdf;base64,'
//         : 'data:image/$ext;base64,';
//     setState(() {
//       if (isDoc1) { _doc1File = file; _doc1Base64 = '$mime$b64'; }
//       else        { _doc2File = file; _doc2Base64 = '$mime$b64'; }
//     });
//   }

//   // ── Date picker ────────────────────────────────────────────────────────────

//   Future<void> _pickDate(bool isIssue) async {
//     final picked = await showDatePicker(
//       context: context,
//       initialDate: DateTime.now(),
//       firstDate: DateTime(1900),
//       lastDate: DateTime(2100),
//       builder: (ctx, child) => Theme(
//         data: ThemeData.light().copyWith(
//           colorScheme: const ColorScheme.light(
//             primary: Color(0xFF34544A), onPrimary: Colors.white),
//         ),
//         child: child!,
//       ),
//     );
//     if (picked != null) {
//       setState(() {
//         if (isIssue) _docIssueDate = picked;
//         else _docExpiryDate = picked;
//       });
//     }
//   }

//   String _dateStr(DateTime d) =>
//       '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

//   String _displayDate(DateTime d) =>
//       '${d.day.toString().padLeft(2,'0')}/${d.month.toString().padLeft(2,'0')}/${d.year}';

//   // ── Submit ─────────────────────────────────────────────────────────────────

//   Future<void> _submit() async {
//     // ── Ask for transaction PIN before submitting ──────────────────────────
//     final pin = await showPinEntry(
//       context,
//       title: 'Enter Transaction PIN',
//       subtitle: "Confirm your identity to submit the ${widget.currency} account request",
//     );
//     if (!mounted) return;
//     if (pin == null) return; // user cancelled

//     showDialog(
//       context: context,
//       barrierDismissible: false,
//       builder: (_) => const Center(
//           child: CircularProgressIndicator(color: Color(0xFF34544A))),
//     );

//     final payload = ForeignAccountRequestPayload(
//       currency:             widget.currencyCode,
//       bvn:                  _bvnCtrl.text.trim(),
//       addressLine1:         _address1Ctrl.text.trim(),
//       addressLine2:         _address2Ctrl.text.trim().isEmpty
//                                 ? null : _address2Ctrl.text.trim(),
//       city:                 _cityCtrl.text.trim(),
//       state:                _stateCtrl.text.trim(),
//       country:              _countryCtrl.text.trim(),
//       postalCode:           _postalCtrl.text.trim(),
//       employmentStatus:     _employmentStatus,
//       occupation:           _occupationCtrl.text.trim(),
//       primaryPurpose:       _primaryPurpose,
//       sourceOfFunds:        _sourceOfFunds,
//       expectedMonthlyInflow: double.tryParse(_monthlyInflowCtrl.text) ?? 0,
//       document:             _doc1Base64!,
//       documentType:         _documentType,
//       documentIssueDate:    _docIssueDate != null ? _dateStr(_docIssueDate!) : null,
//       documentExpiryDate:   _docExpiryDate != null ? _dateStr(_docExpiryDate!) : null,
//       document2:            _doc2Base64!,
//       document2Type:        'utility_bill',
//       transactionPin:       pin,
//     );

//     final response = await ref
//         .read(foreignCurrencyNotifierProvider.notifier)
//         .requestForeignAccount(payload);

//     if (!mounted) return;
//     Navigator.of(context).pop(); // close loader

//     if (response.success) {
//       setState(() => _step = _totalSteps); // success screen
//     } else {
//       ScaffoldMessenger.of(context).showSnackBar(SnackBar(
//         content: Text(response.message.isNotEmpty
//             ? response.message : 'Submission failed. Please try again.'),
//         backgroundColor: Colors.red,
//       ));
//     }
//   }

//   // ── Build ──────────────────────────────────────────────────────────────────

//   @override
//   Widget build(BuildContext context) {
//     final isSuccess = _step == _totalSteps;
//     return Scaffold(
//       backgroundColor: Colors.white,
//       appBar: AppBar(
//         backgroundColor: Colors.white,
//         elevation: 0,
//         leading: IconButton(
//           icon: const Icon(Icons.arrow_back_ios, color: Colors.black),
//           onPressed: () {
//             if (_step == 0 || isSuccess) Navigator.of(context).pop();
//             else setState(() => _step--);
//           },
//         ),
//         title: Text(
//           '${widget.currency} Account Creation',
//           style: const TextStyle(
//               color: Colors.black, fontWeight: FontWeight.bold, fontSize: 17),
//         ),
//         centerTitle: true,
//       ),
//       body: Column(
//         children: [
//           if (!isSuccess)
//             Padding(
//               padding: const EdgeInsets.symmetric(horizontal: 20),
//               child: LinearProgressIndicator(
//                 value: (_step + 1) / _totalSteps,
//                 backgroundColor: Colors.grey[300],
//                 valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF34544A)),
//                 minHeight: 5,
//                 borderRadius: BorderRadius.circular(4),
//               ),
//             ),
//           Expanded(
//             child: SingleChildScrollView(
//               padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
//               child: isSuccess ? _successView() : _buildCurrentStep(),
//             ),
//           ),
//         ],
//       ),
//     );
//   }

//   Widget _buildCurrentStep() {
//     switch (_step) {
//       case 0: return _step0Identity();
//       case 1: return _step1Address();
//       case 2: return _step2DocumentInfo();
//       case 3: return _step3Upload();
//       default: return _successView();
//     }
//   }

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 0 — Identity & Employment
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step0Identity() => _StepShell(
//     title: 'Identity & Employment',
//     subtitle: 'Provide BVN and employment information',
//     isValid: _step0Valid,
//     onNext: () => setState(() => _step++),
//     child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
//       _label('BVN (11 digits)'),
//       _textField(_bvnCtrl, 'Enter your BVN',
//           type: TextInputType.number, maxLength: 11,
//           onChanged: (_) => setState(() {})),
//       if (_bvnCtrl.text.isNotEmpty && _bvnCtrl.text.length != 11)
//         Padding(
//           padding: const EdgeInsets.only(top: 4),
//           child: Text('BVN must be exactly 11 digits',
//               style: TextStyle(color: Colors.red[400], fontSize: 12)),
//         ),
//       const SizedBox(height: 20),
//       _label('Occupation'),
//       _textField(_occupationCtrl, 'Enter your occupation',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 20),
//       _label('Employment Status'),
//       _dropdownField(
//         hint: 'Select employment status',
//         value: _employmentStatus.isEmpty ? null : _employmentStatus,
//         options: _empOptions,
//         onChanged: (v) => setState(() => _employmentStatus = v ?? ''),
//       ),
//     ]),
//   );

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 1 — Address & Funding
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step1Address() => _StepShell(
//     title: 'Address & Funding',
//     subtitle: 'Tell us where you live and how you will use the account',
//     isValid: _step1Valid,
//     onNext: () => setState(() => _step++),
//     child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
//       _label('Address Line 1'),
//       _textField(_address1Ctrl, 'Enter Address Line 1',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('Address Line 2 (Optional)'),
//       _textField(_address2Ctrl, 'Enter Address Line 2 (Optional)'),
//       const SizedBox(height: 16),
//       _label('City'),
//       _textField(_cityCtrl, 'Enter City',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('State'),
//       _textField(_stateCtrl, 'Enter State',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('Country'),
//       _textField(_countryCtrl, 'Enter Country',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 16),
//       _label('Postal Code'),
//       _textField(_postalCtrl, 'Enter Postal Code',
//           onChanged: (_) => setState(() {})),
//       const SizedBox(height: 20),
//       _label('Primary Purpose'),
//       _dropdownField(
//         hint: 'Select an option',
//         value: _primaryPurpose.isEmpty ? null : _primaryPurpose,
//         options: _purposeOptions,
//         onChanged: (v) => setState(() => _primaryPurpose = v ?? ''),
//       ),
//       const SizedBox(height: 16),
//       _label('Source of Funds'),
//       _dropdownField(
//         hint: 'Select an option',
//         value: _sourceOfFunds.isEmpty ? null : _sourceOfFunds,
//         options: _sourceOptions,
//         onChanged: (v) => setState(() => _sourceOfFunds = v ?? ''),
//       ),
//       const SizedBox(height: 16),
//       _label('Expected Monthly Inflow'),
//       _textField(_monthlyInflowCtrl, 'Enter Expected Monthly Inflow',
//           type: TextInputType.number,
//           onChanged: (_) => setState(() {})),
//     ]),
//   );

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 2 — Document Information
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step2DocumentInfo() => _StepShell(
//     title: 'Document Information',
//     subtitle: 'Provide document types and dates',
//     isValid: _step2Valid,
//     onNext: () => setState(() => _step++),
//     child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
//       _label('Document Type (document)'),
//       _dropdownField(
//         hint: 'Select an option',
//         value: _documentType.isEmpty ? null : _documentType,
//         options: _docTypeOptions,
//         onChanged: (v) => setState(() => _documentType = v ?? ''),
//       ),
//       const SizedBox(height: 20),
//       _label('Document Issue Date (Optional)'),
//       _dateTile(
//         hint: 'Select date',
//         date: _docIssueDate,
//         onTap: () => _pickDate(true),
//       ),
//       const SizedBox(height: 16),
//       _label('Document Expiry Date (Optional)'),
//       _dateTile(
//         hint: 'Select date',
//         date: _docExpiryDate,
//         onTap: () => _pickDate(false),
//       ),
//     ]),
//   );

//   // ─────────────────────────────────────────────────────────────────────────
//   // STEP 3 — Document Upload
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _step3Upload() {
//     final docLabel = _docTypeOptions
//         .firstWhere((d) => d['value'] == _documentType,
//             orElse: () => {'label': 'ID Document', 'value': ''})['label']!;

//     return _StepShell(
//       title: 'Document Upload',
//       subtitle: 'Please upload the required documents',
//       isValid: _step3Valid,
//       onNext: _submit,
//       nextLabel: 'Submit Application',
//       child: Column(children: [
//         _UploadTile(
//           label: docLabel,
//           required: true,
//           subtitle: 'Upload file for document (base64 encoded at submit)',
//           file: _doc1File,
//           onTap: () => _pickFile(true),
//         ),
//         const SizedBox(height: 16),
//         _UploadTile(
//           label: 'Utility',
//           required: true,
//           subtitle: 'Upload your most recent utility document (not older than 3 months)',
//           file: _doc2File,
//           onTap: () => _pickFile(false),
//         ),
//       ]),
//     );
//   }

//   // ─────────────────────────────────────────────────────────────────────────
//   // SUCCESS
//   // ─────────────────────────────────────────────────────────────────────────
//   Widget _successView() => Center(
//     child: Column(
//       mainAxisAlignment: MainAxisAlignment.center,
//       children: [
//         const SizedBox(height: 40),
//         Container(
//           width: 88, height: 88,
//           decoration: const BoxDecoration(
//               color: Color(0xFFDDF3E8), shape: BoxShape.circle),
//           child: const Icon(Icons.check_circle_rounded,
//               color: Color(0xFF34544A), size: 56),
//         ),
//         const SizedBox(height: 24),
//         Text('Application Submitted!',
//             style: TextStyle(fontSize: 24.sp, fontWeight: FontWeight.bold),
//             textAlign: TextAlign.center),
//         const SizedBox(height: 12),
//         Padding(
//           padding: const EdgeInsets.symmetric(horizontal: 32),
//           child: Text(
//             'Your ${widget.currency} account request is being reviewed. '
//             'We\'ll notify you within 3–5 working days.',
//             style: TextStyle(fontSize: 15.sp, color: Colors.grey[600], height: 1.5),
//             textAlign: TextAlign.center,
//           ),
//         ),
//         const SizedBox(height: 40),
//         SizedBox(
//           width: double.infinity,
//           child: ElevatedButton(
//             onPressed: () => Navigator.of(context).pop('completed'),
//             style: ElevatedButton.styleFrom(
//               backgroundColor: const Color(0xFF34544A),
//               shape: RoundedRectangleBorder(
//                   borderRadius: BorderRadius.circular(12)),
//               padding: const EdgeInsets.symmetric(vertical: 14),
//             ),
//             child: const Text('Back to Wallet',
//                 style: TextStyle(
//                     fontWeight: FontWeight.bold,
//                     fontSize: 16,
//                     color: Colors.white)),
//           ),
//         ),
//       ],
//     ),
//   );

//   // ── Widget helpers ──────────────────────────────────────────────────────────

//   Widget _label(String text) => Padding(
//     padding: const EdgeInsets.only(bottom: 8),
//     child: Text(text,
//         style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
//   );

//   Widget _textField(
//     TextEditingController ctrl,
//     String hint, {
//     TextInputType type = TextInputType.text,
//     int? maxLength,
//     ValueChanged<String>? onChanged,
//   }) =>
//       TextField(
//         controller: ctrl,
//         keyboardType: type,
//         maxLength: maxLength,
//         onChanged: onChanged,
//         decoration: InputDecoration(
//           hintText: hint,
//           hintStyle: TextStyle(color: Colors.grey[400]),
//           filled: true,
//           fillColor: const Color(0xFFF4F6F5),
//           border: OutlineInputBorder(
//               borderRadius: BorderRadius.circular(10),
//               borderSide: BorderSide.none),
//           counterText: '',
//           contentPadding:
//               const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
//         ),
//       );

//   Widget _dropdownField({
//     required String hint,
//     required String? value,
//     required List<Map<String, String>> options,
//     required ValueChanged<String?> onChanged,
//   }) =>
//       Container(
//         padding: const EdgeInsets.symmetric(horizontal: 12),
//         decoration: BoxDecoration(
//           color: const Color(0xFFF4F6F5),
//           borderRadius: BorderRadius.circular(10),
//           border: Border.all(color: Colors.grey[300]!),
//         ),
//         child: DropdownButton<String>(
//           value: value,
//           hint: Text(hint, style: TextStyle(color: Colors.grey[400])),
//           isExpanded: true,
//           underline: const SizedBox(),
//           dropdownColor: Colors.white,
//           items: options
//               .map((o) => DropdownMenuItem(
//                     value: o['value'],
//                     child: Text(o['label']!),
//                   ))
//               .toList(),
//           onChanged: onChanged,
//         ),
//       );

//   Widget _dateTile({
//     required String hint,
//     required DateTime? date,
//     required VoidCallback onTap,
//   }) =>
//       InkWell(
//         onTap: onTap,
//         child: Container(
//           padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
//           decoration: BoxDecoration(
//             color: const Color(0xFFF4F6F5),
//             borderRadius: BorderRadius.circular(10),
//             border: Border.all(color: Colors.grey[300]!),
//           ),
//           child: Row(children: [
//             Expanded(
//               child: Text(
//                 date != null ? _displayDate(date) : hint,
//                 style: TextStyle(
//                     color: date != null ? Colors.black87 : Colors.grey[400],
//                     fontSize: 15),
//               ),
//             ),
//             Icon(Icons.calendar_month_outlined,
//                 color: Colors.grey[600], size: 22),
//           ]),
//         ),
//       );
// }

// // ─── Step Shell ───────────────────────────────────────────────────────────────

// class _StepShell extends StatelessWidget {
//   final String title;
//   final String subtitle;
//   final bool isValid;
//   final VoidCallback onNext;
//   final String nextLabel;
//   final Widget child;

//   const _StepShell({
//     required this.title,
//     required this.subtitle,
//     required this.isValid,
//     required this.onNext,
//     required this.child,
//     this.nextLabel = 'Next',
//   });

//   @override
//   Widget build(BuildContext context) {
//     return Column(
//       crossAxisAlignment: CrossAxisAlignment.start,
//       children: [
//         Text(title,
//             style: const TextStyle(
//                 fontSize: 22, fontWeight: FontWeight.bold)),
//         const SizedBox(height: 6),
//         Text(subtitle,
//             style: TextStyle(fontSize: 14, color: Colors.grey[600])),
//         const SizedBox(height: 24),
//         child,
//         const SizedBox(height: 32),
//         SizedBox(
//           width: double.infinity,
//           child: ElevatedButton(
//             onPressed: isValid ? onNext : null,
//             style: ElevatedButton.styleFrom(
//               backgroundColor:
//                   isValid ? const Color(0xFF34544A) : Colors.grey[300],
//               shape: RoundedRectangleBorder(
//                   borderRadius: BorderRadius.circular(32)),
//               padding: const EdgeInsets.symmetric(vertical: 16),
//             ),
//             child: Text(nextLabel,
//                 style: TextStyle(
//                     fontWeight: FontWeight.bold,
//                     fontSize: 16,
//                     color: isValid ? Colors.white : Colors.grey[600])),
//           ),
//         ),
//         const SizedBox(height: 24),
//       ],
//     );
//   }
// }

// // ─── Upload Tile ──────────────────────────────────────────────────────────────

// class _UploadTile extends StatelessWidget {
//   final String label;
//   final bool required;
//   final String subtitle;
//   final File? file;
//   final VoidCallback onTap;

//   const _UploadTile({
//     required this.label,
//     required this.required,
//     required this.subtitle,
//     required this.file,
//     required this.onTap,
//   });

//   @override
//   Widget build(BuildContext context) {
//     final uploaded = file != null;
//     return InkWell(
//       onTap: onTap,
//       borderRadius: BorderRadius.circular(12),
//       child: Container(
//         padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
//         decoration: BoxDecoration(
//           color: uploaded
//               ? Colors.green.withOpacity(0.05)
//               : const Color(0xFFF4F6F5),
//           borderRadius: BorderRadius.circular(12),
//           border: Border.all(
//               color: uploaded ? Colors.green : Colors.grey[300]!,
//               width: uploaded ? 1.5 : 1),
//         ),
//         child: Row(children: [
//           Icon(
//             uploaded ? Icons.insert_drive_file : Icons.upload_file_outlined,
//             color: uploaded ? Colors.green : Colors.grey[600],
//             size: 32,
//           ),
//           const SizedBox(width: 14),
//           Expanded(
//             child: Column(
//                 crossAxisAlignment: CrossAxisAlignment.start,
//                 children: [
//               Row(children: [
//                 Text(label,
//                     style: const TextStyle(
//                         fontWeight: FontWeight.bold, fontSize: 15)),
//                 if (required)
//                   const Text(' *',
//                       style: TextStyle(
//                           color: Colors.red,
//                           fontWeight: FontWeight.bold,
//                           fontSize: 15)),
//               ]),
//               const SizedBox(height: 4),
//               Text(
//                 uploaded ? 'File uploaded ✓' : subtitle,
//                 style: TextStyle(
//                     fontSize: 13,
//                     color: uploaded ? Colors.green : Colors.grey[600]),
//               ),
//             ]),
//           ),
//           Icon(Icons.chevron_right,
//               color: Colors.grey[400], size: 22),
//         ]),
//       ),
//     );
//   }
// }


// lib/screen/foreign_currency/create_account_flow.dart

import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:file_picker/file_picker.dart';
import 'package:image_picker/image_picker.dart';
import '../../providers/foreign_currency_provider.dart';
import '../../models/foreign_currency/foreign_account_model.dart';
import '../../widgets/pin_entry_screen.dart';

class CreateAccountFlow extends ConsumerStatefulWidget {
  final String currency;
  final String currencyCode;

  const CreateAccountFlow({
    super.key,
    required this.currency,
    required this.currencyCode,
  });

  @override
  ConsumerState<CreateAccountFlow> createState() => _CreateAccountFlowState();
}

class _CreateAccountFlowState extends ConsumerState<CreateAccountFlow> {

  int _step = 0;
  static const int _totalSteps = 4;

  // ── Step 0: Identity & Employment ─────────────────────────────────────────
  final _bvnCtrl        = TextEditingController();
  final _occupationCtrl = TextEditingController();
  String _employmentStatus = '';

  // ── Step 1: Address & Funding ──────────────────────────────────────────────
  final _address1Ctrl    = TextEditingController();
  final _address2Ctrl    = TextEditingController();
  final _cityCtrl        = TextEditingController();
  final _stateCtrl       = TextEditingController();
  final _countryCtrl     = TextEditingController();
  final _postalCtrl      = TextEditingController();
  String _primaryPurpose       = '';
  String _sourceOfFunds        = '';
  final _monthlyInflowCtrl     = TextEditingController();

  // ── Step 2: Document Information ──────────────────────────────────────────
  String _documentType  = ''; // 'passport' | 'national_id'
  DateTime? _docIssueDate;
  DateTime? _docExpiryDate;

  // ── Step 3: Document Upload ────────────────────────────────────────────────
  String? _doc1Base64;   // ID document (passport or NIN)
  String? _doc2Base64;   // Utility bill
  File?   _doc1File;
  File?   _doc2File;

  // ── Options ────────────────────────────────────────────────────────────────
  final _empOptions = [
    {'label': 'Employed',      'value': 'employed'},
    {'label': 'Self Employed', 'value': 'self_employed'},
    {'label': 'Student',       'value': 'student'},
    {'label': 'Retired',       'value': 'retired'},
    {'label': 'Unemployed',    'value': 'unemployed'},
  ];

  final _purposeOptions = [
    {'label': 'Personal',   'value': 'personal'},
    {'label': 'Business',   'value': 'business'},
    {'label': 'Salary',     'value': 'salary'},
    {'label': 'Freelance',  'value': 'freelance'},
  ];

  final _sourceOptions = [
    {'label': 'Salary',              'value': 'salary'},
    {'label': 'Savings',             'value': 'savings'},
    {'label': 'Business',            'value': 'business'},
    {'label': 'Freelance',           'value': 'freelance'},
    {'label': 'Investment',          'value': 'investment'},
    {'label': 'Government Benefits', 'value': 'government_benefits'},
    {'label': 'Pension',             'value': 'pension'},
  ];

  // Document type options matching backend exactly
  final _docTypeOptions = [
    {'label': 'International Passport', 'value': 'passport'},
    {'label': 'NIN',                    'value': 'national_id'},
  ];

  @override
  void dispose() {
    _bvnCtrl.dispose();
    _occupationCtrl.dispose();
    _address1Ctrl.dispose();
    _address2Ctrl.dispose();
    _cityCtrl.dispose();
    _stateCtrl.dispose();
    _countryCtrl.dispose();
    _postalCtrl.dispose();
    _monthlyInflowCtrl.dispose();
    super.dispose();
  }

  // ── Validation ─────────────────────────────────────────────────────────────

  bool get _step0Valid =>
      _bvnCtrl.text.length == 11 &&
      _occupationCtrl.text.isNotEmpty &&
      _employmentStatus.isNotEmpty;

  bool get _step1Valid =>
      _address1Ctrl.text.isNotEmpty &&
      _cityCtrl.text.isNotEmpty &&
      _stateCtrl.text.isNotEmpty &&
      _countryCtrl.text.isNotEmpty &&
      _postalCtrl.text.isNotEmpty &&
      _primaryPurpose.isNotEmpty &&
      _sourceOfFunds.isNotEmpty &&
      _monthlyInflowCtrl.text.isNotEmpty;

  bool get _step2Valid => _documentType.isNotEmpty;

  bool get _step3Valid => _doc1Base64 != null && _doc2Base64 != null;

  // ── File / gallery picker ──────────────────────────────────────────────────
  // Users can upload from either their photo gallery (image_picker) or the
  // file browser (file_picker, which also allows PDFs). The file browser alone
  // often hides the photo gallery on both iOS (opens Files, not Photos) and
  // some Android setups, so we offer both via a bottom sheet.

  final ImagePicker _imagePicker = ImagePicker();

  Future<void> _pickFile(bool isDoc1) async {
    final source = await _chooseUploadSource();
    if (source == null) return; // user dismissed the sheet

    File? file;
    String ext;

    if (source == 'gallery') {
      final XFile? picked = await _imagePicker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 85,
        maxWidth: 1600,
      );
      if (picked == null) return;
      file = File(picked.path);
      final name = picked.name;
      ext = name.contains('.') ? name.split('.').last.toLowerCase() : 'jpg';
    } else {
      final result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['jpg', 'jpeg', 'png', 'pdf'],
      );
      if (result == null) return;
      file = File(result.files.single.path!);
      ext = (result.files.single.extension ?? 'jpg').toLowerCase();
    }

    final bytes = await file.readAsBytes();
    final b64   = base64Encode(bytes);
    final mime  = ext == 'pdf'
        ? 'data:application/pdf;base64,'
        : 'data:image/$ext;base64,';
    setState(() {
      if (isDoc1) { _doc1File = file; _doc1Base64 = '$mime$b64'; }
      else        { _doc2File = file; _doc2Base64 = '$mime$b64'; }
    });
  }

  /// Bottom sheet letting the user pick between gallery and file. Returns
  /// 'gallery', 'file', or null if dismissed.
  Future<String?> _chooseUploadSource() {
    return showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(height: 12.h),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey[300],
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            SizedBox(height: 16.h),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined,
                  color: Color(0xFF34544A)),
              title: const Text('Choose from gallery'),
              onTap: () => Navigator.of(ctx).pop('gallery'),
            ),
            ListTile(
              leading: const Icon(Icons.insert_drive_file_outlined,
                  color: Color(0xFF34544A)),
              title: const Text('Choose a file (PDF or image)'),
              onTap: () => Navigator.of(ctx).pop('file'),
            ),
            SizedBox(height: 8.h),
          ],
        ),
      ),
    );
  }

  // ── Date picker ────────────────────────────────────────────────────────────

  Future<void> _pickDate(bool isIssue) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(1900),
      lastDate: DateTime(2100),
      builder: (ctx, child) => Theme(
        data: ThemeData.light().copyWith(
          colorScheme: const ColorScheme.light(
            primary: Color(0xFF34544A), onPrimary: Colors.white),
        ),
        child: child!,
      ),
    );
    if (picked != null) {
      setState(() {
        if (isIssue) _docIssueDate = picked;
        else _docExpiryDate = picked;
      });
    }
  }

  String _dateStr(DateTime d) =>
      '${d.year}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  String _displayDate(DateTime d) =>
      '${d.day.toString().padLeft(2,'0')}/${d.month.toString().padLeft(2,'0')}/${d.year}';

  // ── Submit ─────────────────────────────────────────────────────────────────

  Future<void> _submit() async {
    // ── Ask for transaction PIN before submitting ──────────────────────────
    final pin = await showPinEntry(
      context,
      title: 'Enter Transaction PIN',
      subtitle: "Confirm your identity to submit the ${widget.currency} account request",
    );
    if (!mounted) return;
    if (pin == null) return; // user cancelled

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
          child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    final payload = ForeignAccountRequestPayload(
      currency:             widget.currencyCode,
      bvn:                  _bvnCtrl.text.trim(),
      addressLine1:         _address1Ctrl.text.trim(),
      addressLine2:         _address2Ctrl.text.trim().isEmpty
                                ? null : _address2Ctrl.text.trim(),
      city:                 _cityCtrl.text.trim(),
      state:                _stateCtrl.text.trim(),
      country:              _countryCtrl.text.trim(),
      postalCode:           _postalCtrl.text.trim(),
      employmentStatus:     _employmentStatus,
      occupation:           _occupationCtrl.text.trim(),
      primaryPurpose:       _primaryPurpose,
      sourceOfFunds:        _sourceOfFunds,
      expectedMonthlyInflow: double.tryParse(_monthlyInflowCtrl.text) ?? 0,
      document:             _doc1Base64!,
      documentType:         _documentType,
      documentIssueDate:    _docIssueDate != null ? _dateStr(_docIssueDate!) : null,
      documentExpiryDate:   _docExpiryDate != null ? _dateStr(_docExpiryDate!) : null,
      document2:            _doc2Base64!,
      document2Type:        'utility_bill',
      transactionPin:       pin,
    );

    final response = await ref
        .read(foreignCurrencyNotifierProvider.notifier)
        .requestForeignAccount(payload);

    if (!mounted) return;
    Navigator.of(context).pop(); // close loader

    if (response.success) {
      setState(() => _step = _totalSteps); // success screen
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(response.message.isNotEmpty
            ? response.message : 'Submission failed. Please try again.'),
        backgroundColor: Colors.red,
      ));
    }
  }

  // ── Build ──────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final isSuccess = _step == _totalSteps;
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.black),
          onPressed: () {
            if (_step == 0 || isSuccess) Navigator.of(context).pop();
            else setState(() => _step--);
          },
        ),
        title: Text(
          '${widget.currency} Account Creation',
          style: const TextStyle(
              color: Colors.black, fontWeight: FontWeight.bold, fontSize: 17),
        ),
        centerTitle: true,
      ),
      body: Column(
        children: [
          if (!isSuccess)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: LinearProgressIndicator(
                value: (_step + 1) / _totalSteps,
                backgroundColor: Colors.grey[300],
                valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF34544A)),
                minHeight: 5,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
              child: isSuccess ? _successView() : _buildCurrentStep(),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCurrentStep() {
    switch (_step) {
      case 0: return _step0Identity();
      case 1: return _step1Address();
      case 2: return _step2DocumentInfo();
      case 3: return _step3Upload();
      default: return _successView();
    }
  }

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 0 — Identity & Employment
  // ─────────────────────────────────────────────────────────────────────────
  Widget _step0Identity() => _StepShell(
    title: 'Identity & Employment',
    subtitle: 'Provide BVN and employment information',
    isValid: _step0Valid,
    onNext: () => setState(() => _step++),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _label('BVN (11 digits)'),
      _textField(_bvnCtrl, 'Enter your BVN',
          type: TextInputType.number, maxLength: 11,
          onChanged: (_) => setState(() {})),
      if (_bvnCtrl.text.isNotEmpty && _bvnCtrl.text.length != 11)
        Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Text('BVN must be exactly 11 digits',
              style: TextStyle(color: Colors.red[400], fontSize: 12)),
        ),
      const SizedBox(height: 20),
      _label('Occupation'),
      _textField(_occupationCtrl, 'Enter your occupation',
          onChanged: (_) => setState(() {})),
      const SizedBox(height: 20),
      _label('Employment Status'),
      _dropdownField(
        hint: 'Select employment status',
        value: _employmentStatus.isEmpty ? null : _employmentStatus,
        options: _empOptions,
        onChanged: (v) => setState(() => _employmentStatus = v ?? ''),
      ),
    ]),
  );

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 1 — Address & Funding
  // ─────────────────────────────────────────────────────────────────────────
  Widget _step1Address() => _StepShell(
    title: 'Address & Funding',
    subtitle: 'Tell us where you live and how you will use the account',
    isValid: _step1Valid,
    onNext: () => setState(() => _step++),
    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      _label('Address Line 1'),
      _textField(_address1Ctrl, 'Enter Address Line 1',
          onChanged: (_) => setState(() {})),
      const SizedBox(height: 16),
      _label('Address Line 2 (Optional)'),
      _textField(_address2Ctrl, 'Enter Address Line 2 (Optional)'),
      const SizedBox(height: 16),
      _label('City'),
      _textField(_cityCtrl, 'Enter City',
          onChanged: (_) => setState(() {})),
      const SizedBox(height: 16),
      _label('State'),
      _textField(_stateCtrl, 'Enter State',
          onChanged: (_) => setState(() {})),
      const SizedBox(height: 16),
      _label('Country'),
      _textField(_countryCtrl, 'Enter Country',
          onChanged: (_) => setState(() {})),
      const SizedBox(height: 16),
      _label('Postal Code'),
      _textField(_postalCtrl, 'Enter Postal Code',
          onChanged: (_) => setState(() {})),
      const SizedBox(height: 20),
      _label('Primary Purpose'),
      _dropdownField(
        hint: 'Select an option',
        value: _primaryPurpose.isEmpty ? null : _primaryPurpose,
        options: _purposeOptions,
        onChanged: (v) => setState(() => _primaryPurpose = v ?? ''),
      ),
      const SizedBox(height: 16),
      _label('Source of Funds'),
      _dropdownField(
        hint: 'Select an option',
        value: _sourceOfFunds.isEmpty ? null : _sourceOfFunds,
        options: _sourceOptions,
        onChanged: (v) => setState(() => _sourceOfFunds = v ?? ''),
      ),
      const SizedBox(height: 16),
      _label('Expected Monthly Inflow'),
      _textField(_monthlyInflowCtrl, 'Enter Expected Monthly Inflow',
          type: TextInputType.number,
          onChanged: (_) => setState(() {})),
    ]),
  );

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 2 — Document Information
  // ─────────────────────────────────────────────────────────────────────────
  Widget _step2DocumentInfo() {
    final isNin = _documentType == 'national_id';
    return _StepShell(
      title: 'Document Information',
      subtitle: 'Provide document types and dates',
      isValid: _step2Valid,
      onNext: () {
        // Clear dates if NIN selected (not applicable)
        if (isNin) {
          _docIssueDate = null;
          _docExpiryDate = null;
        }
        setState(() => _step++);
      },
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        _label('Document Type'),
        _dropdownField(
          hint: 'Select an option',
          value: _documentType.isEmpty ? null : _documentType,
          options: _docTypeOptions,
          onChanged: (v) {
            setState(() {
              _documentType = v ?? '';
              // Clear dates when switching to NIN
              if (_documentType == 'national_id') {
                _docIssueDate = null;
                _docExpiryDate = null;
              }
            });
          },
        ),
        if (!isNin) ...[
          const SizedBox(height: 20),
          _label('Document Issue Date (Optional)'),
          _dateTile(
            hint: 'Select date',
            date: _docIssueDate,
            onTap: () => _pickDate(true),
          ),
          const SizedBox(height: 16),
          _label('Document Expiry Date (Optional)'),
          _dateTile(
            hint: 'Select date',
            date: _docExpiryDate,
            onTap: () => _pickDate(false),
          ),
        ],
      ]),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // STEP 3 — Document Upload
  // ─────────────────────────────────────────────────────────────────────────
  Widget _step3Upload() {
    final docLabel = _docTypeOptions
        .firstWhere((d) => d['value'] == _documentType,
            orElse: () => {'label': 'ID Document', 'value': ''})['label']!;

    return _StepShell(
      title: 'Document Upload',
      subtitle: 'Please upload the required documents',
      isValid: _step3Valid,
      onNext: _submit,
      nextLabel: 'Submit Application',
      child: Column(children: [
        _UploadTile(
          label: docLabel,
          required: true,
          subtitle: 'Upload file for document (base64 encoded at submit)',
          file: _doc1File,
          onTap: () => _pickFile(true),
        ),
        const SizedBox(height: 16),
        _UploadTile(
          label: 'Utility',
          required: true,
          subtitle: 'Upload your most recent utility document (not older than 3 months)',
          file: _doc2File,
          onTap: () => _pickFile(false),
        ),
      ]),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────
  // SUCCESS
  // ─────────────────────────────────────────────────────────────────────────
  Widget _successView() => Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const SizedBox(height: 40),
        Container(
          width: 88, height: 88,
          decoration: const BoxDecoration(
              color: Color(0xFFDDF3E8), shape: BoxShape.circle),
          child: const Icon(Icons.check_circle_rounded,
              color: Color(0xFF34544A), size: 56),
        ),
        const SizedBox(height: 24),
        Text('Application Submitted!',
            style: TextStyle(fontSize: 24.sp, fontWeight: FontWeight.bold),
            textAlign: TextAlign.center),
        const SizedBox(height: 12),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Text(
            'Your ${widget.currency} account request is being reviewed. '
            'We\'ll notify you within 2-3 business days.',
            style: TextStyle(fontSize: 15.sp, color: Colors.grey[600], height: 1.5),
            textAlign: TextAlign.center,
          ),
        ),
        const SizedBox(height: 40),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: () => Navigator.of(context).pop('completed'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF34544A),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
              padding: const EdgeInsets.symmetric(vertical: 14),
            ),
            child: const Text('Back to Wallet',
                style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: Colors.white)),
          ),
        ),
      ],
    ),
  );

  // ── Widget helpers ──────────────────────────────────────────────────────────

  Widget _label(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 8),
    child: Text(text,
        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
  );

  Widget _textField(
    TextEditingController ctrl,
    String hint, {
    TextInputType type = TextInputType.text,
    int? maxLength,
    ValueChanged<String>? onChanged,
  }) =>
      TextField(
        controller: ctrl,
        keyboardType: type,
        maxLength: maxLength,
        onChanged: onChanged,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(color: Colors.grey[400]),
          filled: true,
          fillColor: const Color(0xFFF4F6F5),
          border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(10),
              borderSide: BorderSide.none),
          counterText: '',
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        ),
      );

  Widget _dropdownField({
    required String hint,
    required String? value,
    required List<Map<String, String>> options,
    required ValueChanged<String?> onChanged,
  }) =>
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: const Color(0xFFF4F6F5),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: Colors.grey[300]!),
        ),
        child: DropdownButton<String>(
          value: value,
          hint: Text(hint, style: TextStyle(color: Colors.grey[400])),
          isExpanded: true,
          underline: const SizedBox(),
          dropdownColor: Colors.white,
          items: options
              .map((o) => DropdownMenuItem(
                    value: o['value'],
                    child: Text(o['label']!),
                  ))
              .toList(),
          onChanged: onChanged,
        ),
      );

  Widget _dateTile({
    required String hint,
    required DateTime? date,
    required VoidCallback onTap,
  }) =>
      InkWell(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          decoration: BoxDecoration(
            color: const Color(0xFFF4F6F5),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.grey[300]!),
          ),
          child: Row(children: [
            Expanded(
              child: Text(
                date != null ? _displayDate(date) : hint,
                style: TextStyle(
                    color: date != null ? Colors.black87 : Colors.grey[400],
                    fontSize: 15),
              ),
            ),
            Icon(Icons.calendar_month_outlined,
                color: Colors.grey[600], size: 22),
          ]),
        ),
      );
}

// ─── Step Shell ───────────────────────────────────────────────────────────────

class _StepShell extends StatelessWidget {
  final String title;
  final String subtitle;
  final bool isValid;
  final VoidCallback onNext;
  final String nextLabel;
  final Widget child;

  const _StepShell({
    required this.title,
    required this.subtitle,
    required this.isValid,
    required this.onNext,
    required this.child,
    this.nextLabel = 'Next',
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title,
            style: const TextStyle(
                fontSize: 22, fontWeight: FontWeight.bold)),
        const SizedBox(height: 6),
        Text(subtitle,
            style: TextStyle(fontSize: 14, color: Colors.grey[600])),
        const SizedBox(height: 24),
        child,
        const SizedBox(height: 32),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: isValid ? onNext : null,
            style: ElevatedButton.styleFrom(
              backgroundColor:
                  isValid ? const Color(0xFF34544A) : Colors.grey[300],
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32)),
              padding: const EdgeInsets.symmetric(vertical: 16),
            ),
            child: Text(nextLabel,
                style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                    color: isValid ? Colors.white : Colors.grey[600])),
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }
}

// ─── Upload Tile ──────────────────────────────────────────────────────────────

class _UploadTile extends StatelessWidget {
  final String label;
  final bool required;
  final String subtitle;
  final File? file;
  final VoidCallback onTap;

  const _UploadTile({
    required this.label,
    required this.required,
    required this.subtitle,
    required this.file,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final uploaded = file != null;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 18),
        decoration: BoxDecoration(
          color: uploaded
              ? Colors.green.withOpacity(0.05)
              : const Color(0xFFF4F6F5),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
              color: uploaded ? Colors.green : Colors.grey[300]!,
              width: uploaded ? 1.5 : 1),
        ),
        child: Row(children: [
          Icon(
            uploaded ? Icons.insert_drive_file : Icons.upload_file_outlined,
            color: uploaded ? Colors.green : Colors.grey[600],
            size: 32,
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
              Row(children: [
                Text(label,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 15)),
                if (required)
                  const Text(' *',
                      style: TextStyle(
                          color: Colors.red,
                          fontWeight: FontWeight.bold,
                          fontSize: 15)),
              ]),
              const SizedBox(height: 4),
              Text(
                uploaded ? 'File uploaded ✓' : subtitle,
                style: TextStyle(
                    fontSize: 13,
                    color: uploaded ? Colors.green : Colors.grey[600]),
              ),
            ]),
          ),
          Icon(Icons.chevron_right,
              color: Colors.grey[400], size: 22),
        ]),
      ),
    );
  }
}
