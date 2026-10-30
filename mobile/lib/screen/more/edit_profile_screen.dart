// lib/screen/more/edit_profile_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import '../../providers/auth_provider.dart';
import '../../core/network/api_service.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  final _firstNameCtrl = TextEditingController();
  final _lastNameCtrl  = TextEditingController();
  final _usernameCtrl  = TextEditingController();
  final _emailCtrl     = TextEditingController();
  final _phoneCtrl     = TextEditingController();

  bool _isLoading  = false;
  bool _hasChanges = false;

  // Store originals to detect changes
  String _origEmail = '';
  String _origPhone = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final user = ref.read(currentUserProvider);
      if (user == null) return;

      _firstNameCtrl.text = user.profile?.firstName ?? '';
      _lastNameCtrl.text  = user.profile?.lastName  ?? '';
      _usernameCtrl.text  = user.profile?.username  ?? '';
      _emailCtrl.text     = user.email;
      _phoneCtrl.text     = user.phoneNumber ?? '';

      _origEmail = user.email;
      _origPhone = user.phoneNumber ?? '';

      for (final c in [_firstNameCtrl, _lastNameCtrl, _usernameCtrl,
                        _emailCtrl, _phoneCtrl]) {
        c.addListener(_onChanged);
      }
    });
  }

  void _onChanged() => setState(() => _hasChanges = true);

  @override
  void dispose() {
    for (final c in [_firstNameCtrl, _lastNameCtrl, _usernameCtrl,
                      _emailCtrl, _phoneCtrl]) {
      c.removeListener(_onChanged);
      c.dispose();
    }
    super.dispose();
  }

  String? _validate() {
    if (_firstNameCtrl.text.trim().isEmpty) return 'First name is required';
    if (_lastNameCtrl.text.trim().isEmpty)  return 'Last name is required';
    if (_usernameCtrl.text.trim().isEmpty)  return 'Username is required';
    if (_emailCtrl.text.trim().isEmpty)     return 'Email is required';
    if (!RegExp(r'^[^@]+@[^@]+\.[^@]+').hasMatch(_emailCtrl.text.trim()))
      return 'Enter a valid email address';
    if (_phoneCtrl.text.trim().length != 11)
      return 'Phone number must be exactly 11 digits';
    return null;
  }

  Future<void> _submit() async {
    final err = _validate();
    if (err != null) { _snack(err, error: true); return; }

    setState(() => _isLoading = true);

    final response = await ApiService.instance.postSimple(
      '/setting/update-profile',
      data: {
        'first_name':   _firstNameCtrl.text.trim(),
        'last_name':    _lastNameCtrl.text.trim(),
        'username':     _usernameCtrl.text.trim(),
        'email':        _emailCtrl.text.trim(),
        'phone_number': _phoneCtrl.text.trim(),
      },
    );

    if (!mounted) return;
    setState(() { _isLoading = false; _hasChanges = false; });

    if (response.success) {
      // Refresh user in Riverpod so entire app sees new data
      await ref.read(authNotifierProvider.notifier).refreshUser();

      // Warn about re-verification if email/phone changed
      final emailChanged = _emailCtrl.text.trim() != _origEmail;
      final phoneChanged = _phoneCtrl.text.trim() != _origPhone;
      String msg = 'Profile updated successfully!';
      if (emailChanged && phoneChanged) {
        msg += '\nPlease verify your new email and phone number.';
      } else if (emailChanged) {
        msg += '\nPlease verify your new email address.';
      } else if (phoneChanged) {
        msg += '\nPlease verify your new phone number.';
      }
      _snack(msg);
    } else {
      _snack(response.message.isNotEmpty
          ? response.message : 'Update failed. Try again.', error: true);
    }
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: error ? Colors.red : const Color(0xFF34544A),
      duration: const Duration(seconds: 4),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);

    return Scaffold(
      resizeToAvoidBottomInset: true,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(children: [
          // ── Header ────────────────────────────────────────────────────────
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Row(children: [
              IconButton(
                onPressed: () => Navigator.pop(context),
                icon: const Icon(Icons.arrow_back_ios,
                    color: Colors.white, size: 24),
              ),
              Expanded(
                child: Text('Edit Profile',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                        fontSize: 20.sp,
                        fontWeight: FontWeight.bold,
                        color: Colors.white)),
              ),
              const SizedBox(width: 48),
            ]),
          ),

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
                    padding: const EdgeInsets.symmetric(horizontal: 24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        SizedBox(height: 30.h),

                        // Avatar initials
                        Center(
                          child: CircleAvatar(
                            radius: 38,
                            backgroundColor:
                                const Color(0xFF34544A).withOpacity(0.1),
                            child: Text(
                              _initials(user),
                              style: const TextStyle(
                                  fontSize: 26,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF34544A)),
                            ),
                          ),
                        ),
                        SizedBox(height: 6.h),
                        if (user != null)
                          Center(
                            child: Text(user.email,
                                style: TextStyle(
                                    fontSize: 13.sp,
                                    color: Colors.grey[500])),
                          ),
                        SizedBox(height: 28.h),

                        Text('Personal Information',
                            style: TextStyle(
                                fontSize: 18.sp,
                                fontWeight: FontWeight.bold,
                                color: Colors.grey[800])),
                        SizedBox(height: 24.h),

                        // Read-only fields (greyed out)
                        _ProfileField(
                          icon: Icons.person,
                          label: 'First Name',
                          controller: _firstNameCtrl,
                          isReadOnly: true,
                        ),
                        _ProfileField(
                          icon: Icons.person,
                          label: 'Last Name',
                          controller: _lastNameCtrl,
                          isReadOnly: true,
                        ),
                        _ProfileField(
                          icon: Icons.alternate_email,
                          label: 'Username',
                          controller: _usernameCtrl,
                          isReadOnly: true,
                        ),

                        // Editable fields
                        _ProfileField(
                          icon: Icons.email_outlined,
                          label: 'Email',
                          controller: _emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                        ),
                        _ProfileField(
                          icon: Icons.phone_outlined,
                          label: 'Phone Number',
                          controller: _phoneCtrl,
                          keyboardType: TextInputType.phone,
                          maxLength: 11,
                          hint: '08012345678',
                        ),

                        SizedBox(height: 40.h),

                        // Submit button
                        Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 0),
                          child: SizedBox(
                            width: double.infinity,
                            child: ElevatedButton(
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _hasChanges && !_isLoading
                                    ? const Color(0xFF34544A)
                                    : Colors.grey[300],
                                foregroundColor: Colors.white,
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(32.r)),
                                padding:
                                    EdgeInsets.symmetric(vertical: 16.h),
                              ),
                              onPressed:
                                  _hasChanges && !_isLoading ? _submit : null,
                              child: _isLoading
                                  ? const SizedBox(
                                      width: 22, height: 22,
                                      child: CircularProgressIndicator(
                                          color: Colors.white, strokeWidth: 2))
                                  : Text(
                                      _hasChanges ? 'Submit' : 'No Changes',
                                      style: TextStyle(
                                          fontSize: 18.sp,
                                          fontWeight: FontWeight.bold,
                                          color: _hasChanges
                                              ? Colors.white
                                              : Colors.grey[600])),
                            ),
                          ),
                        ),
                        SizedBox(height: 40.h),
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

  String _initials(dynamic user) {
    if (user == null) return '?';
    final first = user.profile?.firstName ?? '';
    final last  = user.profile?.lastName  ?? '';
    if (first.isNotEmpty && last.isNotEmpty)
      return '${first[0]}${last[0]}'.toUpperCase();
    if (first.isNotEmpty) return first[0].toUpperCase();
    return user.email.isNotEmpty ? user.email[0].toUpperCase() : '?';
  }
}

// ─── Field widget ─────────────────────────────────────────────────────────────

class _ProfileField extends StatelessWidget {
  final IconData icon;
  final String label;
  final TextEditingController controller;
  final TextInputType? keyboardType;
  final bool isReadOnly;
  final int? maxLength;
  final String? hint;

  const _ProfileField({
    required this.icon,
    required this.label,
    required this.controller,
    this.keyboardType,
    this.isReadOnly = false,
    this.maxLength,
    this.hint,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: TextStyle(
                color: Colors.grey[600],
                fontSize: 14.sp,
                fontWeight: FontWeight.w500)),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: keyboardType,
          readOnly: isReadOnly,
          maxLength: maxLength,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: TextStyle(color: Colors.grey[400]),
            prefixIcon: Icon(icon,
                color: isReadOnly
                    ? Colors.grey[400]
                    : const Color(0xFF34544A)),
            border: const UnderlineInputBorder(),
            enabledBorder: UnderlineInputBorder(
              borderSide: BorderSide(
                  color: isReadOnly
                      ? Colors.grey[300]!
                      : Colors.grey[400]!),
            ),
            focusedBorder: const UnderlineInputBorder(
              borderSide:
                  BorderSide(color: Color(0xFF34544A), width: 2),
            ),
            counterText: '',
            contentPadding: EdgeInsets.symmetric(
                vertical: 16.h, horizontal: 16.w),
          ),
          style: TextStyle(
              fontSize: 16.sp,
              color: isReadOnly
                  ? Colors.grey[500]
                  : Colors.grey[800],
              fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}