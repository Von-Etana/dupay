// lib/screen/auth/kyc_screen.dart
//
// ─────────────────────────────────────────────────────────────────────────────
// KYC screen — single page, styled to match sign_up_screen.dart.
//
// WHAT CHANGED AND WHY
//
// 1. ENDPOINT (this was the "upload error")
//    The old screen posted to ApiConstants.profileKycVerification
//    ('/kyc/profile-verification'). That route does not exist — the backend has
//    'verification/profile-kyc'. Every request 404'd. _loadKycStatus() caught
//    the error and deliberately failed open to the form, so the screen looked
//    fine while nothing was reaching the server.
//
//    Worse, even with the path corrected, submitProfileKYC writes to the
//    `profile_kycs` table, and no admin page reads that table. Submissions
//    would have been invisible to review.
//
//    This screen now uses the WALLET KYC flow — walletKycDetails /
//    walletKycVerification — which writes to `wallet_verificaitons`. That is
//    the table the admin Verifications page reads, and the one signup already
//    populates.
//
//    NOTE: api_contants.dart must also be corrected. See PATCH-api_constants.md.
//
// 2. SIGNUP ALREADY DID THE KYC
//    /register collects bvn, method, date_of_birth, id_image, user_photo… and
//    writes them straight to wallet_verificaitons with status "0". So a user
//    reaching this screen normally ALREADY has a pending record. This screen is
//    therefore primarily a STATUS screen; the form only appears when there is
//    no record, or when the submission was rejected and needs fixing.
//
// 3. FIELD NAMES
//    Matched to verifyUserWalletUsingMonnifyBVN exactly — the same contract
//    RegisterRequest.toFormData() already uses:
//      bvn, first_name, last_name, other_names, method, gender,
//      date_of_birth, mobile_no, address, city, state, zip_code,
//      house_number, issued_country, id_image (file), user_photo (file)
//
// 4. IMAGE PICKERS
//    Both tiles now ask camera-or-gallery via a bottom sheet. The selfie
//    defaults the sheet to Camera (front lens), the ID to Gallery. The old
//    screen forced camera-only; the interim version forced gallery-only, which
//    left no way to reach the camera at all.
// ─────────────────────────────────────────────────────────────────────────────

import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/constants/api_contants.dart';
import '../../core/exceptions/api_exception.dart';
import '../../core/network/api_service.dart';

// Design tokens lifted from sign_up_screen.dart so the two screens match.
const Color _kDark = Color(0xFF34544A);
const Color _kMid = Color(0xFF7CA183);
const Color _kFill = Color(0xFFF8FAF9);

class KycScreen extends StatefulWidget {
  const KycScreen({super.key});

  @override
  State<KycScreen> createState() => _KycScreenState();
}

class _KycScreenState extends State<KycScreen> {
  // ── Form controllers ───────────────────────────────────────────────────────
  final _firstName = TextEditingController();
  final _lastName = TextEditingController();
  final _otherNames = TextEditingController();
  final _idNumber = TextEditingController(); // sent as `bvn`
  final _mobileNo = TextEditingController();
  final _address = TextEditingController();
  final _houseNumber = TextEditingController();
  final _city = TextEditingController();
  final _state = TextEditingController();
  final _zipCode = TextEditingController();
  final _issuedCountry = TextEditingController(text: 'Nigeria');
  final _dob = TextEditingController();

  String _gender = 'Male';
  String _method = 'BVN'; // BVN | NIN — admin can still change this at review

  File? _idImage; // id_image
  File? _userPhoto; // user_photo

  final ImagePicker _picker = ImagePicker();

  // ── Screen state ───────────────────────────────────────────────────────────
  bool _loading = true;
  bool _submitting = false;

  /// none | pending | approved | rejected
  String _status = 'none';
  String? _rejectReason;
  Map<String, dynamic>? _record;

  /// The backend only permits (re)submission when there is no record yet, or
  /// the previous one was rejected. Mirrored here so the form is not offered
  /// when it would be refused.
  /// Set from the API's own `can_resubmit` flag when the backend sends one.
  /// The local rule below stays as the fallback for older builds of the API.
  bool? _serverCanResubmit;

  bool get _canSubmit =>
      _serverCanResubmit ?? (_status == 'none' || _status == 'rejected');

  @override
  void initState() {
    super.initState();
    _loadStatus();
  }

  @override
  void dispose() {
    for (final c in [
      _firstName,
      _lastName,
      _otherNames,
      _idNumber,
      _mobileNo,
      _address,
      _houseNumber,
      _city,
      _state,
      _zipCode,
      _issuedCountry,
      _dob,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Status
  // ───────────────────────────────────────────────────────────────────────────

  Future<void> _loadStatus() async {
    setState(() => _loading = true);

    try {
      // GET wallet-kyc-details returns:
      //   { success, kyc_status: 'none'|'0'|'1'|'2', status_label, data, user }
      final res = await ApiService.instance.get<Map<String, dynamic>>(
        ApiConstants.walletKycDetails,
        fromJson: (json) => json,
      );

      final body = res.data ?? const <String, dynamic>{};
      final record = body['data'];

      final raw = (body['kyc_status'] ??
              (record is Map ? record['status'] : null) ??
              'none')
          .toString();

      String mapped;
      switch (raw) {
        case '0':
          mapped = 'pending';
          break;
        case '1':
          mapped = 'approved';
          break;
        case '2':
          mapped = 'rejected';
          break;
        default:
          mapped = 'none';
      }

      if (!mounted) return;

      setState(() {
        _status = mapped;
        _record = (record is Map && record.isNotEmpty)
            ? Map<String, dynamic>.from(record)
            : null;

        final canResubmit = body['can_resubmit'];
        _serverCanResubmit = canResubmit is bool ? canResubmit : null;

        // Prefer the top-level comment the API now sends; fall back to the one
        // on the record for older API builds. Only meaningful when rejected.
        final reason = (body['comment'] ?? _record?['comment'])?.toString();
        _rejectReason =
            (mapped == 'rejected' && reason != null && reason.isNotEmpty)
                ? reason
                : null;

        _loading = false;
      });

      // Pre-fill the form from the existing record so a rejected user only has
      // to correct what was wrong rather than retype everything.
      if (_record != null) _prefill(_record!);
    } catch (_) {
      // Fail open to the form rather than hard-blocking on a network blip.
      // The backend still enforces the real rule.
      if (!mounted) return;
      setState(() {
        _status = 'none';
        _serverCanResubmit = null;
        _loading = false;
      });
    }
  }

  void _prefill(Map<String, dynamic> r) {
    String s(dynamic v) => v == null ? '' : v.toString();

    _firstName.text = s(r['first_name']);
    _lastName.text = s(r['last_name']);
    _otherNames.text = s(r['other_names']);
    _idNumber.text = s(r['bvn']);
    _mobileNo.text = s(r['mobile_no']);
    _address.text = s(r['address']);
    _houseNumber.text = s(r['house_number']);
    _city.text = s(r['city']);
    _state.text = s(r['state']);
    _zipCode.text = s(r['zip_code']);

    final country = s(r['issued_country']);
    if (country.isNotEmpty) _issuedCountry.text = country;

    // date_of_birth may come back as a full timestamp — keep the date part.
    final dob = s(r['date_of_birth']);
    if (dob.isNotEmpty) _dob.text = dob.split('T').first.split(' ').first;

    final gender = s(r['gender']);
    if (gender == 'Male' || gender == 'Female') _gender = gender;

    final method = s(r['method']).toUpperCase();
    if (method == 'BVN' || method == 'NIN') _method = method;
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Pickers — camera OR gallery, user's choice
  //
  // Previously both tiles went straight to ImageSource.gallery, so "Photo of
  // you" opened the file browser and there was no way to reach the camera at
  // all. Forcing the camera instead is not the answer either — plenty of users
  // already have a usable photo saved, and a hard camera-only step is what the
  // ORIGINAL screen did before it was replaced.
  //
  // Both tiles now ask. The selfie defaults the sheet to Camera (front lens);
  // the ID document defaults to Gallery, since an ID is usually photographed
  // once and reused.
  // ───────────────────────────────────────────────────────────────────────────

  Future<void> _pickImage({required bool isSelfie}) async {
    final ImageSource? source = await _chooseSource(isSelfie: isSelfie);
    if (source == null) return; // user dismissed the sheet

    try {
      final XFile? picked = await _picker.pickImage(
        source: source,
        imageQuality: 80,
        maxWidth: 1600,
        // Only meaningful for the camera; ignored for the gallery.
        preferredCameraDevice:
            isSelfie ? CameraDevice.front : CameraDevice.rear,
      );

      if (picked == null) return;

      final file = File(picked.path);

      // Backend caps uploads at 2 MB (max:2048). Catch it here so the user gets
      // a clear message instead of a validation error after a long upload.
      final bytes = await file.length();
      if (bytes > 2 * 1024 * 1024) {
        _snack(
          source == ImageSource.camera
              ? 'That photo is over 2MB. Try again in better light, or pick a smaller one from your gallery.'
              : 'That image is over 2MB. Please choose a smaller one.',
          error: true,
        );
        return;
      }

      setState(() {
        if (isSelfie) {
          _userPhoto = file;
        } else {
          _idImage = file;
        }
      });
    } on PlatformException catch (e) {
      // camera_access_denied / photo_access_denied are the two that actually
      // reach users. Anything else gets the generic line.
      final denied = e.code.contains('denied') || e.code.contains('access');
      if (!mounted) return;

      _snack(
        denied
            ? (source == ImageSource.camera
                ? 'Camera access is blocked. Enable it for DuPay in your phone settings.'
                : 'Photo access is blocked. Enable it for DuPay in your phone settings.')
            : 'Could not open the ${source == ImageSource.camera ? 'camera' : 'gallery'}. Please try again.',
        error: true,
      );
    } catch (_) {
      if (!mounted) return;
      _snack(
        'Could not open the ${source == ImageSource.camera ? 'camera' : 'gallery'}. Please try again.',
        error: true,
      );
    }
  }

  /// Bottom sheet asking camera vs gallery. Returns null if dismissed.
  Future<ImageSource?> _chooseSource({required bool isSelfie}) {
    final options = isSelfie
        ? const [ImageSource.camera, ImageSource.gallery]
        : const [ImageSource.gallery, ImageSource.camera];

    return showModalBottomSheet<ImageSource>(
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
            Text(
              isSelfie ? 'Photo of you' : 'ID document',
              style: TextStyle(
                fontSize: 15.sp,
                fontWeight: FontWeight.w700,
                color: _kDark,
              ),
            ),
            SizedBox(height: 4.h),
            Text(
              isSelfie
                  ? 'Take a clear photo of your face'
                  : 'A clear photo of your ID, all four corners visible',
              style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
              textAlign: TextAlign.center,
            ),
            SizedBox(height: 8.h),
            for (final source in options)
              ListTile(
                leading: Icon(
                  source == ImageSource.camera
                      ? Icons.photo_camera_outlined
                      : Icons.photo_library_outlined,
                  color: _kDark,
                ),
                title: Text(
                  source == ImageSource.camera
                      ? 'Take a photo'
                      : 'Choose from gallery',
                  style: TextStyle(
                    fontSize: 14.sp,
                    fontWeight: FontWeight.w600,
                    color: _kDark,
                  ),
                ),
                onTap: () => Navigator.of(ctx).pop(source),
              ),
            SizedBox(height: 8.h),
          ],
        ),
      ),
    );
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Pickers / modals
  // ───────────────────────────────────────────────────────────────────────────

  static const List<String> _states = [
    'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue',
    'Borno', 'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu',
    'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi',
    'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo',
    'Plateau', 'Rivers', 'Sokoto', 'Taraba', 'Yobe', 'Zamfara', 'FCT',
  ];

  Future<void> _showStateModal() async {
    final selected = await showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        String search = '';
        return StatefulBuilder(
          builder: (ctx, setSheet) {
            final filtered = _states
                .where((s) => s.toLowerCase().contains(search.toLowerCase()))
                .toList();

            return Padding(
              padding: EdgeInsets.only(
                top: 16,
                left: 16,
                right: 16,
                bottom: MediaQuery.of(ctx).viewInsets.bottom + 16,
              ),
              child: SizedBox(
                height: 500,
                child: Column(
                  children: [
                    Row(
                      children: [
                        const Text('Select State',
                            style: TextStyle(
                                fontWeight: FontWeight.bold, fontSize: 18)),
                        const Spacer(),
                        IconButton(
                          icon: const Icon(Icons.close),
                          onPressed: () => Navigator.of(ctx).pop(),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      decoration: InputDecoration(
                        hintText: 'Search state...',
                        prefixIcon: const Icon(Icons.search),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        filled: true,
                        fillColor: _kFill,
                      ),
                      onChanged: (v) => setSheet(() => search = v),
                    ),
                    const SizedBox(height: 12),
                    Expanded(
                      child: ListView.builder(
                        itemCount: filtered.length,
                        itemBuilder: (ctx, i) => ListTile(
                          title: Text(filtered[i]),
                          onTap: () => Navigator.of(ctx).pop(filtered[i]),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    if (selected != null) setState(() => _state.text = selected);
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(now.year - 20),
      firstDate: DateTime(1900),
      lastDate: now,
      builder: (ctx, child) => Theme(
        data: Theme.of(ctx).copyWith(
          colorScheme: const ColorScheme.light(primary: _kDark),
        ),
        child: child!,
      ),
    );

    if (picked != null) {
      final m = picked.month.toString().padLeft(2, '0');
      final d = picked.day.toString().padLeft(2, '0');
      setState(() => _dob.text = '${picked.year}-$m-$d');
    }
  }

  void _snack(String msg, {bool error = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        backgroundColor: error ? Colors.red : _kDark,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Submit
  // ───────────────────────────────────────────────────────────────────────────

  String? _validate() {
    if (_firstName.text.trim().isEmpty) return 'Enter your first name.';
    if (_lastName.text.trim().isEmpty) return 'Enter your last name.';

    final id = _idNumber.text.trim();
    if (id.length != 11 || int.tryParse(id) == null) {
      return 'Your $_method must be exactly 11 digits.';
    }

    if (_dob.text.trim().isEmpty) return 'Select your date of birth.';
    if (_mobileNo.text.trim().isEmpty) return 'Enter your mobile number.';
    if (_address.text.trim().isEmpty) return 'Enter your address.';
    if (_city.text.trim().isEmpty) return 'Enter your city.';
    if (_state.text.trim().isEmpty) return 'Select your state.';
    if (_zipCode.text.trim().isEmpty) return 'Enter your zip code.';
    if (_idImage == null) return 'Upload a photo of your ID document.';
    if (_userPhoto == null) return 'Upload a photo of yourself.';
    return null;
  }

  Future<void> _submit() async {
    if (!_canSubmit) {
      _snack(
        _status == 'pending'
            ? 'Your verification is already under review.'
            : 'Your verification has already been approved.',
        error: true,
      );
      return;
    }

    final error = _validate();
    if (error != null) {
      _snack(error, error: true);
      return;
    }

    setState(() => _submitting = true);

    try {
      // Field names match verifyUserWalletUsingMonnifyBVN exactly — the same
      // contract RegisterRequest already uses at signup.
      final form = FormData.fromMap({
        'bvn': _idNumber.text.trim(),
        'method': _method,
        'first_name': _firstName.text.trim(),
        'last_name': _lastName.text.trim(),
        'other_names': _otherNames.text.trim(),
        'gender': _gender,
        'date_of_birth': _dob.text.trim(),
        'mobile_no': _mobileNo.text.trim(),
        'address': _address.text.trim(),
        'house_number': _houseNumber.text.trim(),
        'city': _city.text.trim(),
        'state': _state.text.trim(),
        'zip_code': _zipCode.text.trim(),
        'issued_country': _issuedCountry.text.trim(),
        'id_image': await MultipartFile.fromFile(
          _idImage!.path,
          filename: _idImage!.path.split(Platform.pathSeparator).last,
        ),
        'user_photo': await MultipartFile.fromFile(
          _userPhoto!.path,
          filename: _userPhoto!.path.split(Platform.pathSeparator).last,
        ),
      });

      final res = await ApiService.instance.postSimple(
        ApiConstants.walletKycVerification,
        data: form,
      );

      if (!mounted) return;
      setState(() => _submitting = false);

      if (res.success) {
        setState(() {
          _status = 'pending';
          _rejectReason = null;
        });
        _snack('Submitted. Your verification is now under review.');
        _loadStatus();
      } else {
        _snack(
          res.message.isNotEmpty
              ? res.message
              : 'Submission failed. Please try again.',
          error: true,
        );

        // A rejected submission leaves the local screen out of step with the
        // server (the old code guessed at the new state from the message text).
        // Re-read the record instead so the form, the status chip and the
        // rejection reason all agree with what the admin actually sees.
        _loadStatus();
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);

      final msg = (e is ApiException && e.message.trim().isNotEmpty)
          ? e.message
          : 'Something went wrong. Please try again.';
      _snack(msg, error: true);

      // Never infer state from the wording of an error message. Doing that is
      // what made a rejected user see the "Verified" screen: the API replied
      // "KYC Already Verified" (its duplicate-BVN guard, not a real verdict)
      // and this branch flipped _status to approved locally, while the record
      // was still status 2 in the database. Pulling to refresh then snapped it
      // back to Rejected, which is how the bug surfaced.
      //
      // The record on the server is the only source of truth, so re-read it.
      _loadStatus();
    }
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Build
  // ───────────────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        backgroundColor: _kDark,
        body: Center(child: CircularProgressIndicator(color: Colors.white)),
      );
    }

    return _shell(
      child: _canSubmit ? _formView() : _statusView(),
    );
  }

  /// The green header + stacked rounded panels used across signup and KYC.
  Widget _shell({required Widget child}) {
    return Scaffold(
      backgroundColor: _kDark,
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            SizedBox(height: 12.h),
            _header(),
            SizedBox(height: 8.h),
            Expanded(
              child: Container(
                width: double.infinity,
                decoration: const BoxDecoration(
                  color: _kMid,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(top: 12),
                  child: Container(
                    width: double.infinity,
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20),
                      ),
                    ),
                    child: child,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _header() {
    return Row(
      children: [
        IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
          onPressed: () => Navigator.of(context).maybePop(),
        ),
        Expanded(
          child: Text(
            'KYC Verification',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 20.sp,
              fontWeight: FontWeight.bold,
              color: Colors.white,
            ),
          ),
        ),
        IconButton(
          icon: const Icon(Icons.refresh, color: Colors.white),
          onPressed: _loading ? null : _loadStatus,
        ),
      ],
    );
  }

  // ── Status view (pending / approved) ───────────────────────────────────────

  Widget _statusView() {
    final approved = _status == 'approved';

    return RefreshIndicator(
      onRefresh: _loadStatus,
      child: ListView(
        padding: EdgeInsets.fromLTRB(24.w, 32.h, 24.w, 32.h),
        children: [
          Center(
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: approved
                    ? Colors.green.withOpacity(0.08)
                    : Colors.orange.withOpacity(0.08),
                shape: BoxShape.circle,
              ),
              child: Icon(
                approved ? Icons.verified_rounded : Icons.hourglass_top_rounded,
                size: 56,
                color: approved ? Colors.green.shade600 : Colors.orange.shade700,
              ),
            ),
          ),
          SizedBox(height: 20.h),
          Text(
            approved ? 'Verification approved' : 'Verification under review',
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 18.sp,
              fontWeight: FontWeight.w700,
              color: Colors.grey[850],
            ),
          ),
          SizedBox(height: 8.h),
          Text(
            approved
                ? 'Your identity has been verified. All wallet features are now available.'
                : 'We have received your details and they are being reviewed. '
                    'You will be notified once this is complete.',
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 13.sp, color: Colors.grey[600]),
          ),
          SizedBox(height: 28.h),
          if (_record != null) _submittedSummary(),
          SizedBox(height: 24.h),
          Center(
            child: TextButton.icon(
              onPressed: _loadStatus,
              icon: const Icon(Icons.refresh, size: 18, color: _kDark),
              label: const Text('Check for updates',
                  style: TextStyle(color: _kDark, fontWeight: FontWeight.w600)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _submittedSummary() {
    final r = _record!;
    String s(dynamic v) => (v == null || v.toString().isEmpty)
        ? '—'
        : v.toString();

    // Show only the last 4 digits of the identity number.
    final id = s(r['bvn']);
    final masked =
        id.length >= 4 ? '•••• •••• ${id.substring(id.length - 4)}' : id;

    final name = [
      s(r['first_name']),
      s(r['other_names']) == '—' ? '' : s(r['other_names']),
      s(r['last_name']),
    ].where((e) => e.isNotEmpty && e != '—').join(' ');

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _kFill,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('What you submitted',
              style: TextStyle(
                  fontSize: 13.sp,
                  fontWeight: FontWeight.w700,
                  color: Colors.grey[800])),
          SizedBox(height: 12.h),
          _summaryRow('Name', name.isEmpty ? '—' : name),
          _summaryRow('Document', s(r['method']).toUpperCase()),
          _summaryRow('Number', masked),
          _summaryRow('Date of birth',
              s(r['date_of_birth']).split('T').first.split(' ').first),
          _summaryRow('Phone', s(r['mobile_no'])),
          _summaryRow('State', s(r['state'])),
        ],
      ),
    );
  }

  Widget _summaryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(label,
                style: TextStyle(fontSize: 12.sp, color: Colors.grey[600])),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                  fontSize: 12.5.sp,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey[850]),
            ),
          ),
        ],
      ),
    );
  }

  // ── Form view (no record / rejected) ───────────────────────────────────────

  Widget _formView() {
    return ListView(
      padding: EdgeInsets.fromLTRB(20.w, 24.h, 20.w, 40.h),
      children: [
        if (_status == 'rejected') ...[
          _rejectionBanner(),
          SizedBox(height: 24.h),
        ],

        _sectionTitle('Personal details', 'As they appear on your ID document'),
        SizedBox(height: 16.h),
        _field(_firstName, 'First name', Icons.person_outline),
        _field(_lastName, 'Last name', Icons.person_outline),
        _field(_otherNames, 'Other names (optional)', Icons.person_outline),

        SizedBox(height: 8.h),
        Text('Gender',
            style: TextStyle(fontSize: 12.5.sp, color: Colors.grey[700])),
        SizedBox(height: 8.h),
        Row(
          children: [
            _chip('Male', _gender, (v) => setState(() => _gender = v)),
            SizedBox(width: 12.w),
            _chip('Female', _gender, (v) => setState(() => _gender = v)),
          ],
        ),
        SizedBox(height: 16.h),

        GestureDetector(
          onTap: _pickDate,
          child: AbsorbPointer(
            child: _field(_dob, 'Date of birth', Icons.calendar_today_outlined),
          ),
        ),

        SizedBox(height: 24.h),
        _sectionTitle('Identity document', 'Choose which document you are using'),
        SizedBox(height: 12.h),
        Row(
          children: [
            _chip('BVN', _method, (v) => setState(() => _method = v)),
            SizedBox(width: 12.w),
            _chip('NIN', _method, (v) => setState(() => _method = v)),
          ],
        ),
        SizedBox(height: 16.h),
        _field(
          _idNumber,
          '$_method number (11 digits)',
          Icons.badge_outlined,
          keyboardType: TextInputType.number,
          maxLength: 11,
          digitsOnly: true,
        ),

        SizedBox(height: 16.h),
        _sectionTitle('Contact & address', 'Where you currently live'),
        SizedBox(height: 16.h),
        _field(_mobileNo, 'Mobile number', Icons.phone_outlined,
            keyboardType: TextInputType.phone),
        _field(_address, 'Street address', Icons.location_on_outlined),
        _field(_houseNumber, 'House number (optional)', Icons.home_outlined),
        _field(_city, 'City', Icons.location_city_outlined),

        GestureDetector(
          onTap: _showStateModal,
          child: AbsorbPointer(
            child: _field(_state, 'State', Icons.map_outlined),
          ),
        ),

        _field(_zipCode, 'Zip code', Icons.markunread_mailbox_outlined,
            keyboardType: TextInputType.number, maxLength: 10),
        _field(_issuedCountry, 'Issued country', Icons.public_outlined),

        SizedBox(height: 16.h),
        _sectionTitle('Documents', 'Take a photo or pick one from your gallery'),
        SizedBox(height: 16.h),

        Text('ID document',
            style: TextStyle(fontSize: 12.5.sp, color: Colors.grey[700])),
        SizedBox(height: 8.h),
        _docPicker(
          file: _idImage,
          placeholder: 'Add your ID photo',
          icon: Icons.badge_outlined,
          onTap: () => _pickImage(isSelfie: false),
          onRemove: () => setState(() => _idImage = null),
        ),

        SizedBox(height: 20.h),
        Text('Photo of you',
            style: TextStyle(fontSize: 12.5.sp, color: Colors.grey[700])),
        SizedBox(height: 8.h),
        _docPicker(
          file: _userPhoto,
          placeholder: 'Add a photo of yourself',
          icon: Icons.person_outline,
          onTap: () => _pickImage(isSelfie: true),
          onRemove: () => setState(() => _userPhoto = null),
        ),

        SizedBox(height: 32.h),
        SizedBox(
          width: double.infinity,
          height: 52.h,
          child: ElevatedButton(
            onPressed: _submitting ? null : _submit,
            style: ElevatedButton.styleFrom(
              backgroundColor: _kDark,
              disabledBackgroundColor: _kDark.withOpacity(0.5),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(32.r),
              ),
            ),
            child: _submitting
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white),
                  )
                : Text(
                    _status == 'rejected'
                        ? 'Resubmit for review'
                        : 'Submit for review',
                    style: TextStyle(
                      fontSize: 15.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
          ),
        ),
        SizedBox(height: 12.h),
        Text(
          'Your details are reviewed manually. This usually takes a short while.',
          textAlign: TextAlign.center,
          style: TextStyle(fontSize: 11.5.sp, color: Colors.grey[600]),
        ),
      ],
    );
  }

  Widget _rejectionBanner() {
    final reason = (_rejectReason ?? '').trim();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.red.withOpacity(0.06),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.red.withOpacity(0.25)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.error_outline, color: Colors.red.shade600, size: 22),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Verification rejected',
                  style: TextStyle(
                    fontSize: 14.sp,
                    fontWeight: FontWeight.w700,
                    color: Colors.red.shade700,
                  ),
                ),
                SizedBox(height: 4.h),
                Text(
                  reason.isEmpty
                      ? 'Please check your details and documents, then submit again.'
                      : reason,
                  style: TextStyle(fontSize: 12.5.sp, color: Colors.grey[800]),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ── Small UI helpers (mirrored from sign_up_screen.dart) ───────────────────

  Widget _sectionTitle(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title,
            style: TextStyle(
                fontSize: 16.sp,
                fontWeight: FontWeight.w700,
                color: Colors.grey[800])),
        SizedBox(height: 4.h),
        Text(subtitle,
            style: TextStyle(fontSize: 12.5.sp, color: Colors.grey[600])),
      ],
    );
  }

  Widget _chip(String value, String groupValue, ValueChanged<String> onTap) {
    final selected = groupValue == value;
    return GestureDetector(
      onTap: () => onTap(value),
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 24.w),
        decoration: BoxDecoration(
          color: selected ? _kDark : Colors.white,
          border: Border.all(color: _kDark),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          value,
          style: TextStyle(
            color: selected ? Colors.white : _kDark,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String hint,
    IconData icon, {
    TextInputType? keyboardType,
    int? maxLength,
    bool digitsOnly = false,
  }) {
    return Padding(
      padding: EdgeInsets.only(bottom: 14.h),
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        maxLength: maxLength,
        inputFormatters:
            digitsOnly ? [FilteringTextInputFormatter.digitsOnly] : null,
        style: TextStyle(fontSize: 14.sp),
        decoration: InputDecoration(
          counterText: '',
          prefixIcon: Icon(icon, size: 20, color: _kDark),
          hintText: hint,
          hintStyle: TextStyle(fontSize: 13.sp, color: Colors.grey[500]),
          filled: true,
          fillColor: _kFill,
          contentPadding:
              const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: _kDark, width: 1.5),
          ),
        ),
      ),
    );
  }

  Widget _docPicker({
    required File? file,
    required String placeholder,
    required VoidCallback onTap,
    required VoidCallback onRemove,
    IconData icon = Icons.upload_file,
  }) {
    if (file == null) {
      return GestureDetector(
        onTap: onTap,
        child: Container(
          width: double.infinity,
          height: 150,
          decoration: BoxDecoration(
            color: _kFill,
            border: Border.all(color: _kDark, width: 1.5),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 36, color: _kDark),
              const SizedBox(height: 8),
              Text(
                placeholder,
                style: TextStyle(
                  color: _kDark,
                  fontSize: 13.sp,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 4),
              Text('Camera or gallery — JPG or PNG, up to 2MB',
                  style: TextStyle(fontSize: 11.sp, color: Colors.grey[600])),
            ],
          ),
        ),
      );
    }

    return Stack(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.file(
            file,
            width: double.infinity,
            height: 150,
            fit: BoxFit.cover,
          ),
        ),
        Positioned(
          top: 8,
          right: 8,
          child: GestureDetector(
            onTap: onRemove,
            child: Container(
              padding: const EdgeInsets.all(6),
              decoration: const BoxDecoration(
                color: Colors.black54,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.close, size: 18, color: Colors.white),
            ),
          ),
        ),
        Positioned(
          bottom: 8,
          right: 8,
          child: GestureDetector(
            onTap: onTap,
            child: Container(
              padding:
                  const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.black54,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text('Change',
                  style: TextStyle(fontSize: 11.sp, color: Colors.white)),
            ),
          ),
        ),
      ],
    );
  }
}





// This this kyc was rejected but after resubmitting it im seeing this Kyc already verify.....class
// But this is whta is showing in log "flutter: │ 🐛 Response data: {success: false, message: KYC Already Verified}"
// But when i check my admin panel its showing Rejecte meaning thwe KYC did not even re-upload still shoiwng rejected
// But when i click check for update it goes back to show KYC rejected

// Log "flutter: └───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────
// Example response omitted because it contained personal information.
// "
