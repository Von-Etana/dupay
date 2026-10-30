// lib/screen/auth/sign_up_screen.dart
//
// Multi-step sign-up that captures every field the backend /register endpoint
// expects (basic account info + BVN/NIN KYC + ID image + selfie).
//
import 'dart:io';

import 'package:dupay/models/auth/register_request.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../providers/auth_provider.dart';
import 'package:dupay/services/notifications/notification_service.dart';
import '../../services/notifications/fcm_token_sync.dart';
import 'dart:async' show unawaited;

class SignUpScreen extends ConsumerStatefulWidget {
  const SignUpScreen({super.key});

  @override
  ConsumerState<SignUpScreen> createState() => _SignUpScreenState();
}

class _SignUpScreenState extends ConsumerState<SignUpScreen> {
  final PageController _pageController = PageController();
  int _currentStep = 0;
  static const int _totalSteps = 4;

  // ===== Step 1: Personal info =====
  final TextEditingController _firstnameController = TextEditingController();
  final TextEditingController _lastnameController = TextEditingController();
  final TextEditingController _otherNamesController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _dobController = TextEditingController();
  String _gender = 'Male';

  // ===== Step 2: Account credentials =====
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _passwordConfirmationController =
      TextEditingController();
  final TextEditingController _referralController = TextEditingController();

  bool _rememberMe = false;

  // ===== Step 3: KYC details =====
  final TextEditingController _bvnController = TextEditingController();
  final TextEditingController _mobileNoController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _cityController = TextEditingController();
  final TextEditingController _stateController = TextEditingController();
  final TextEditingController _zipCodeController = TextEditingController();
  final TextEditingController _houseNumberController = TextEditingController();
  final TextEditingController _issuedCountryController = TextEditingController(
    text: 'Nigeria',
  );
  String _verificationMethod = 'BVN'; // BVN or NIN

  // ===== Step 4: Document uploads =====
  File? _idImage;
  File? _userPhoto;
  final ImagePicker _picker = ImagePicker();

  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;

  final List<Map<String, String>> _nigerianStates = [
    {'name': 'Abia', 'code': 'AB'},
    {'name': 'Adamawa', 'code': 'AD'},
    {'name': 'Akwa Ibom', 'code': 'AK'},
    {'name': 'Anambra', 'code': 'AN'},
    {'name': 'Bauchi', 'code': 'BA'},
    {'name': 'Bayelsa', 'code': 'BY'},
    {'name': 'Benue', 'code': 'BE'},
    {'name': 'Borno', 'code': 'BO'},
    {'name': 'Cross River', 'code': 'CR'},
    {'name': 'Delta', 'code': 'DE'},
    {'name': 'Ebonyi', 'code': 'EB'},
    {'name': 'Edo', 'code': 'ED'},
    {'name': 'Ekiti', 'code': 'EK'},
    {'name': 'Enugu', 'code': 'EN'},
    {'name': 'Gombe', 'code': 'GO'},
    {'name': 'Imo', 'code': 'IM'},
    {'name': 'Jigawa', 'code': 'JI'},
    {'name': 'Kaduna', 'code': 'KD'},
    {'name': 'Kano', 'code': 'KN'},
    {'name': 'Katsina', 'code': 'KT'},
    {'name': 'Kebbi', 'code': 'KE'},
    {'name': 'Kogi', 'code': 'KO'},
    {'name': 'Kwara', 'code': 'KW'},
    {'name': 'Lagos', 'code': 'LA'},
    {'name': 'Nasarawa', 'code': 'NA'},
    {'name': 'Niger', 'code': 'NI'},
    {'name': 'Ogun', 'code': 'OG'},
    {'name': 'Ondo', 'code': 'ON'},
    {'name': 'Osun', 'code': 'OS'},
    {'name': 'Oyo', 'code': 'OY'},
    {'name': 'Plateau', 'code': 'PL'},
    {'name': 'Rivers', 'code': 'RI'},
    {'name': 'Sokoto', 'code': 'SO'},
    {'name': 'Taraba', 'code': 'TA'},
    {'name': 'Yobe', 'code': 'YO'},
    {'name': 'Zamfara', 'code': 'ZA'},
    {'name': 'FCT', 'code': 'FC'},
  ];

  @override
  void dispose() {
    _pageController.dispose();
    _firstnameController.dispose();
    _lastnameController.dispose();
    _otherNamesController.dispose();
    _phoneController.dispose();
    _dobController.dispose();
    _emailController.dispose();
    _usernameController.dispose();
    _passwordController.dispose();
    _passwordConfirmationController.dispose();
    _referralController.dispose();
    _bvnController.dispose();
    _mobileNoController.dispose();
    _addressController.dispose();
    _cityController.dispose();
    _stateController.dispose();
    _zipCodeController.dispose();
    _houseNumberController.dispose();
    _issuedCountryController.dispose();
    super.dispose();
  }

  // ---------- Navigation ----------
  void _goToStep(int step) {
    setState(() => _currentStep = step);
    _pageController.animateToPage(
      step,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeInOut,
    );
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(msg), backgroundColor: Colors.red));
  }

  bool _validateStep(int step) {
    switch (step) {
      case 0:
        if (_firstnameController.text.trim().isEmpty ||
            _lastnameController.text.trim().isEmpty ||
            _phoneController.text.trim().isEmpty ||
            _dobController.text.trim().isEmpty) {
          _showError('Please fill in all required fields');
          return false;
        }
        if (_phoneController.text.trim().length < 10) {
          _showError('Enter a valid phone number');
          return false;
        }
        return true;
      case 1:
        if (_emailController.text.trim().isEmpty ||
            _usernameController.text.trim().isEmpty ||
            _passwordController.text.isEmpty ||
            _passwordConfirmationController.text.isEmpty) {
          _showError('Please fill in all required fields');
          return false;
        }
        if (!_emailController.text.contains('@')) {
          _showError('Enter a valid email address');
          return false;
        }
        if (_passwordController.text.length < 6) {
          _showError('Password must be at least 6 characters');
          return false;
        }
        if (_passwordController.text != _passwordConfirmationController.text) {
          _showError('Passwords do not match');
          return false;
        }
        return true;
      case 2:
        if (_bvnController.text.trim().isEmpty ||
            _addressController.text.trim().isEmpty ||
            _cityController.text.trim().isEmpty ||
            _stateController.text.trim().isEmpty ||
            _zipCodeController.text.trim().isEmpty ||
            _houseNumberController.text.trim().isEmpty) {
          _showError('Please fill in all KYC fields');
          return false;
        }
        if (_bvnController.text.trim().length != 11) {
          _showError('${_verificationMethod} must be exactly 11 digits');
          return false;
        }
        return true;
      case 3:
        if (_idImage == null) {
          _showError('Please upload a photo of your ID document');
          return false;
        }
        if (_userPhoto == null) {
          _showError('Please take a selfie');
          return false;
        }
        return true;
    }
    return true;
  }

  Future<void> _onPrimaryPressed() async {
    if (!_validateStep(_currentStep)) return;

    if (_currentStep < _totalSteps - 1) {
      _goToStep(_currentStep + 1);
      return;
    }

    // Final step → submit
    await _submitRegistration();
  }

  Future<void> _submitRegistration() async {
    final authProvider = ref.read(authNotifierProvider.notifier);

    final request = RegisterRequest(
      firstName: _firstnameController.text.trim(),
      lastName: _lastnameController.text.trim(),
      username: _usernameController.text.trim().toLowerCase(),
      phoneNumber: _phoneController.text.trim(),
      email: _emailController.text.trim(),
      password: _passwordController.text,
      passwordConfirmation: _passwordConfirmationController.text,
      referral:
          _referralController.text.trim().isEmpty
              ? null
              : _referralController.text.trim(),
      bvn: _bvnController.text.trim(),
      dateOfBirth: _dobController.text.trim(),
      mobileNo:
          _mobileNoController.text.trim().isEmpty
              ? _phoneController.text.trim()
              : _mobileNoController.text.trim(),
      address: _addressController.text.trim(),
      city: _cityController.text.trim(),
      // Stored as the 2-letter state code (matches what the backend stores).
      state: _stateController.text.trim(),
      zipCode: _zipCodeController.text.trim(),
      otherNames:
          _otherNamesController.text.trim().isEmpty
              ? null
              : _otherNamesController.text.trim(),
      method: _verificationMethod,
      gender: _gender,
      houseNumber: _houseNumberController.text.trim(),
      issuedCountry:
          _issuedCountryController.text.trim().isEmpty
              ? 'Nigeria'
              : _issuedCountryController.text.trim(),
      idImage: _idImage,
      userPhoto: _userPhoto,
      fcmToken: NotificationService.fcmToken,
    );

    final success = await authProvider.register(request);

    if (!mounted) return;
    if (success) {
      // Same reason as login: on iOS the token often isn't ready when the
      // register payload is built, so push it now the account exists.
      unawaited(FcmTokenSync.syncCurrentToken());
      final user = ref.read(currentUserProvider);
      Navigator.of(context).pushReplacementNamed(
        user?.isEmailVerified == false
            ? '/emailVerification'
            : '/transactionPinSetup',
      );
    } else {
      final error = ref.read(authErrorProvider);
      if (error != null) _showError(error);
    }
  }

  // ---------- Pickers ----------
  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime(2000, 1, 1),
      firstDate: DateTime(1900),
      lastDate: DateTime.now(),
    );
    if (picked != null) {
      _dobController.text =
          '${picked.year}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
      setState(() {});
    }
  }

  Future<void> _showStateSheet() async {
    String search = '';
    final selected = await showModalBottomSheet<Map<String, String>>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setSheet) {
            final filtered =
                _nigerianStates
                    .where(
                      (s) => s['name']!.toLowerCase().contains(
                        search.toLowerCase(),
                      ),
                    )
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
                        const Text(
                          'Select State',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 18,
                          ),
                        ),
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
                        fillColor: const Color(0xFFF8FAF9),
                      ),
                      onChanged: (v) => setSheet(() => search = v),
                    ),
                    const SizedBox(height: 12),
                    Expanded(
                      child: ListView.builder(
                        itemCount: filtered.length,
                        itemBuilder: (ctx, i) {
                          final s = filtered[i];
                          return ListTile(
                            title: Text('${s['name']} - ${s['code']}'),
                            onTap: () => Navigator.of(ctx).pop(s),
                          );
                        },
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
    if (selected != null) {
      // Backend stores the 2-letter code (matches existing user records).
      _stateController.text = selected['code']!;
      setState(() {});
    }
  }

  Future<void> _pickIdImage() async {
    try {
      final XFile? img = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
      );
      if (img != null) setState(() => _idImage = File(img.path));
    } catch (_) {
      _showError('Could not open gallery. Check permissions.');
    }
  }

  Future<void> _takeSelfie() async {
    try {
      final XFile? img = await _picker.pickImage(
        source: ImageSource.camera,
        preferredCameraDevice: CameraDevice.front,
        imageQuality: 80,
      );
      if (img != null) setState(() => _userPhoto = File(img.path));
    } catch (_) {
      _showError('Could not open camera. Check permissions.');
    }
  }

  // ---------- UI ----------
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            SizedBox(height: 20.h),
            _header(),
            SizedBox(height: 16.h),
            _stepIndicator(),
            SizedBox(height: 12.h),
            Expanded(
              child: Container(
                decoration: const BoxDecoration(
                  color: Color(0xFF7CA183),
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(20),
                    topRight: Radius.circular(20),
                  ),
                ),
                child: Padding(
                  padding: const EdgeInsets.only(top: 16),
                  child: Container(
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(20),
                        topRight: Radius.circular(20),
                      ),
                    ),
                    child: Column(
                      children: [
                        Expanded(
                          child: PageView(
                            controller: _pageController,
                            physics: const NeverScrollableScrollPhysics(),
                            children: [
                              _step1Personal(),
                              _step2Account(),
                              _step3Kyc(),
                              _step4Documents(),
                            ],
                          ),
                        ),
                        _bottomNav(),
                      ],
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

  Widget _header() {
    return Stack(
      alignment: Alignment.center,
      children: [
        Align(
          alignment: Alignment.centerLeft,
          child: IconButton(
            icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
            onPressed: () {
              if (_currentStep > 0) {
                _goToStep(_currentStep - 1);
              } else {
                Navigator.of(context).pop();
              }
            },
          ),
        ),
        Text(
          'SIGN UP',
          style: TextStyle(
            fontSize: 22.sp,
            fontWeight: FontWeight.w700,
            color: Colors.white,
          ),
        ),
      ],
    );
  }

  Widget _stepIndicator() {
    final titles = ['Personal', 'Account', 'KYC', 'Documents'];
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: List.generate(_totalSteps, (i) {
          final isDone = i < _currentStep;
          final isCurrent = i == _currentStep;
          final color =
              isDone || isCurrent
                  ? Colors.white
                  : Colors.white.withOpacity(0.35);
          return Expanded(
            child: Column(
              children: [
                Container(
                  height: 4,
                  margin: EdgeInsets.symmetric(horizontal: 2.w),
                  decoration: BoxDecoration(
                    color: color,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  titles[i],
                  style: TextStyle(
                    color: color,
                    fontSize: 11.sp,
                    fontWeight: isCurrent ? FontWeight.bold : FontWeight.normal,
                  ),
                ),
              ],
            ),
          );
        }),
      ),
    );
  }

  Widget _bottomNav() {
    return Padding(
      padding: EdgeInsets.fromLTRB(20.w, 12.h, 20.w, 24.h),
      child: Column(
        children: [
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF34544A),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(32.r),
                ),
                padding: EdgeInsets.symmetric(vertical: 16.h),
              ),
              onPressed: _onPrimaryPressed,
              child: Consumer(
                builder: (ctx, ref, _) {
                  final isLoading = ref.watch(authLoadingProvider);
                  if (isLoading) {
                    return SizedBox(
                      height: 20.h,
                      width: 20.w,
                      child: const CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2,
                      ),
                    );
                  }
                  return Text(
                    _currentStep == _totalSteps - 1 ? 'Register' : 'Next',
                    style: TextStyle(
                      fontSize: 17.sp,
                      fontWeight: FontWeight.bold,
                    ),
                  );
                },
              ),
            ),
          ),
          SizedBox(height: 10.h),
          GestureDetector(
            onTap: () => Navigator.of(context).pushNamed('/login'),
            child: Text(
              'Already have an account? Login',
              style: TextStyle(
                fontSize: 14.sp,
                color: const Color(0xFF34544A),
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ===== Step screens =====
  Widget _step1Personal() {
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(20.w, 24.h, 20.w, 20.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('Personal Information', 'Tell us a bit about yourself'),
          SizedBox(height: 20.h),
          Row(
            children: [
              Expanded(
                child: _Field(
                  hint: 'First name',
                  controller: _firstnameController,
                ),
              ),
              SizedBox(width: 12.w),
              Expanded(
                child: _Field(
                  hint: 'Last name',
                  controller: _lastnameController,
                ),
              ),
            ],
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Other names (optional)',
            controller: _otherNamesController,
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Phone number',
            controller: _phoneController,
            keyboardType: TextInputType.phone,
          ),
          SizedBox(height: 14.h),
          GestureDetector(
            onTap: _pickDate,
            child: AbsorbPointer(
              child: _Field(
                hint: 'Date of birth (YYYY-MM-DD)',
                controller: _dobController,
              ),
            ),
          ),
          SizedBox(height: 14.h),
          Text(
            'Gender',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: Colors.grey[700],
            ),
          ),
          SizedBox(height: 8.h),
          Row(
            children: [
              _genderChip('Male'),
              SizedBox(width: 12.w),
              _genderChip('Female'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _step2Account() {
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(20.w, 24.h, 20.w, 20.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle('Account Credentials', 'You will use these to sign in'),
          SizedBox(height: 20.h),
          _Field(
            hint: 'Email',
            controller: _emailController,
            keyboardType: TextInputType.emailAddress,
          ),
          SizedBox(height: 14.h),
          _Field(hint: 'Username', controller: _usernameController),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Password',
            controller: _passwordController,
            obscureText: _obscurePassword,
            suffix: IconButton(
              icon: Icon(
                _obscurePassword ? Icons.visibility_off : Icons.visibility,
              ),
              onPressed:
                  () => setState(() => _obscurePassword = !_obscurePassword),
            ),
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Confirm password',
            controller: _passwordConfirmationController,
            obscureText: _obscureConfirmPassword,
            suffix: IconButton(
              icon: Icon(
                _obscureConfirmPassword
                    ? Icons.visibility_off
                    : Icons.visibility,
              ),
              onPressed:
                  () => setState(
                    () => _obscureConfirmPassword = !_obscureConfirmPassword,
                  ),
            ),
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Referral code (optional)',
            controller: _referralController,
          ),
          SizedBox(height: 10.h),
          // ── Remember Me ─────────────────────────────────────────────
          Row(
            children: [
              SizedBox(
                width: 24,
                height: 24,
                child: Checkbox(
                  value: _rememberMe,
                  activeColor: const Color(0xFF34544A),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(4),
                  ),
                  onChanged:
                      (val) => setState(() => _rememberMe = val ?? false),
                ),
              ),
              const SizedBox(width: 10),
              Text(
                'Remember me',
                style: TextStyle(
                  fontSize: 14.sp,
                  color: Colors.grey[700],
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _step3Kyc() {
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(20.w, 24.h, 20.w, 20.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle(
            'KYC Verification',
            'Required by law to operate your wallet',
          ),
          SizedBox(height: 16.h),
          Text(
            'Verification method',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: Colors.grey[700],
            ),
          ),
          SizedBox(height: 8.h),
          Row(
            children: [
              _methodChip('BVN'),
              SizedBox(width: 12.w),
              _methodChip('NIN'),
            ],
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: '$_verificationMethod (11 digits)',
            controller: _bvnController,
            keyboardType: TextInputType.number,
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Mobile number on $_verificationMethod (optional)',
            controller: _mobileNoController,
            keyboardType: TextInputType.phone,
          ),
          SizedBox(height: 14.h),
          _Field(hint: 'House number', controller: _houseNumberController),
          SizedBox(height: 14.h),
          _Field(hint: 'Street address', controller: _addressController),
          SizedBox(height: 14.h),
          _Field(hint: 'City', controller: _cityController),
          SizedBox(height: 14.h),
          GestureDetector(
            onTap: _showStateSheet,
            child: AbsorbPointer(
              child: _Field(
                hint: 'State (tap to select)',
                controller: _stateController,
              ),
            ),
          ),
          SizedBox(height: 14.h),
          _Field(
            hint: 'Zip code',
            controller: _zipCodeController,
            keyboardType: TextInputType.number,
          ),
          SizedBox(height: 14.h),
          _Field(hint: 'Issued country', controller: _issuedCountryController),
        ],
      ),
    );
  }

  Widget _step4Documents() {
    return SingleChildScrollView(
      padding: EdgeInsets.fromLTRB(20.w, 24.h, 20.w, 20.h),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _sectionTitle(
            'Documents',
            'Upload a clear photo of your ID and a selfie',
          ),
          SizedBox(height: 20.h),
          Text(
            'ID Document',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: Colors.grey[700],
            ),
          ),
          SizedBox(height: 8.h),
          _docPicker(
            file: _idImage,
            placeholder: 'Tap to upload ID',
            onTap: _pickIdImage,
            onRemove: () => setState(() => _idImage = null),
          ),
          SizedBox(height: 24.h),
          Text(
            'Selfie',
            style: TextStyle(
              fontWeight: FontWeight.w600,
              color: Colors.grey[700],
            ),
          ),
          SizedBox(height: 8.h),
          _docPicker(
            file: _userPhoto,
            placeholder: 'Tap to take selfie',
            icon: Icons.camera_alt_outlined,
            onTap: _takeSelfie,
            onRemove: () => setState(() => _userPhoto = null),
          ),
          SizedBox(height: 24.h),
        ],
      ),
    );
  }

  // ===== Small UI helpers =====
  Widget _sectionTitle(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: TextStyle(
            fontSize: 18.sp,
            fontWeight: FontWeight.w700,
            color: Colors.grey[800],
          ),
        ),
        SizedBox(height: 4.h),
        Text(
          subtitle,
          style: TextStyle(fontSize: 13.sp, color: Colors.grey[600]),
        ),
      ],
    );
  }

  Widget _genderChip(String value) {
    final selected = _gender == value;
    return GestureDetector(
      onTap: () => setState(() => _gender = value),
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 24.w),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFF34544A) : Colors.white,
          border: Border.all(color: const Color(0xFF34544A)),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          value,
          style: TextStyle(
            color: selected ? Colors.white : const Color(0xFF34544A),
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  Widget _methodChip(String value) {
    final selected = _verificationMethod == value;
    return GestureDetector(
      onTap: () => setState(() => _verificationMethod = value),
      child: Container(
        padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 24.w),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFF34544A) : Colors.white,
          border: Border.all(color: const Color(0xFF34544A)),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Text(
          value,
          style: TextStyle(
            color: selected ? Colors.white : const Color(0xFF34544A),
            fontWeight: FontWeight.bold,
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
          height: 160,
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAF9),
            border: Border.all(
              color: const Color(0xFF34544A),
              width: 2,
              style: BorderStyle.solid,
            ),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 40, color: const Color(0xFF34544A)),
              const SizedBox(height: 8),
              Text(
                placeholder,
                style: const TextStyle(
                  color: Color(0xFF34544A),
                  fontWeight: FontWeight.w600,
                ),
              ),
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
            height: 200,
            fit: BoxFit.cover,
          ),
        ),
        Positioned(
          top: 8,
          right: 8,
          child: GestureDetector(
            onTap: onRemove,
            child: Container(
              width: 30,
              height: 30,
              decoration: const BoxDecoration(
                color: Colors.red,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.close, color: Colors.white, size: 20),
            ),
          ),
        ),
        Positioned(
          bottom: 8,
          right: 8,
          child: GestureDetector(
            onTap: onTap,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFF34544A),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Text(
                'Change',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _Field extends StatelessWidget {
  final String hint;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final bool obscureText;
  final Widget? suffix;

  const _Field({
    required this.hint,
    this.controller,
    this.keyboardType,
    this.obscureText = false,
    this.suffix,
  });

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      obscureText: obscureText,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(color: Colors.grey[500], fontSize: 15.sp),
        filled: true,
        fillColor: const Color(0xFFF8FAF9),
        suffixIcon: suffix,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12.r),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12.r),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12.r),
          borderSide: const BorderSide(color: Color(0xFF34544A), width: 1.5),
        ),
        contentPadding: EdgeInsets.symmetric(vertical: 16.h, horizontal: 14.w),
      ),
    );
  }
}
