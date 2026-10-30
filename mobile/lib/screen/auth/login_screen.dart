import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:local_auth/local_auth.dart';
import 'package:local_auth_android/local_auth_android.dart';
import 'package:local_auth_darwin/local_auth_darwin.dart';
import '../../providers/auth_provider.dart';
import 'package:dupay/services/notifications/notification_service.dart';
import '../../services/storage/secure_storage_service.dart';
import 'dart:async' show unawaited;
import '../../services/notifications/fcm_token_sync.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  bool _isPasswordObscured = true;
  final LocalAuthentication _localAuth = LocalAuthentication();
  bool _rememberMe = false;
  bool _biometricLoading = false;
  bool _loginSubmitting = false;

  // ── Top message (replaces bottom SnackBar) ──────────────────────────────────
  OverlayEntry? _topMessageEntry;

  void _showTopMessage(String message, {bool isError = true}) {
    // Remove any existing message first
    _topMessageEntry?.remove();
    _topMessageEntry = null;

    final overlay = Overlay.of(context);
    final entry = OverlayEntry(
      builder:
          (ctx) => Positioned(
            top: MediaQuery.of(ctx).padding.top + 12,
            left: 16,
            right: 16,
            child: Material(
              color: Colors.transparent,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 14,
                ),
                decoration: BoxDecoration(
                  color: isError ? Colors.red : const Color(0xFF34544A),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.15),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    Icon(
                      isError ? Icons.error_outline : Icons.info_outline,
                      color: Colors.white,
                      size: 20,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        message,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w600,
                          fontSize: 14,
                        ),
                      ),
                    ),
                    GestureDetector(
                      onTap: () {
                        _topMessageEntry?.remove();
                        _topMessageEntry = null;
                      },
                      child: const Icon(
                        Icons.close,
                        color: Colors.white,
                        size: 18,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
    );

    overlay.insert(entry);
    _topMessageEntry = entry;

    // Auto-dismiss after 3 seconds
    Future.delayed(const Duration(seconds: 3), () {
      if (_topMessageEntry == entry) {
        _topMessageEntry?.remove();
        _topMessageEntry = null;
      }
    });
  }

  @override
  void initState() {
    super.initState();
    _loadSavedCredentials();
  }

  Future<void> _loadSavedCredentials() async {
    final prefs = await SharedPreferences.getInstance();

    // Purge any credentials an earlier build may have written to the (plaintext)
    // SharedPreferences store — passwords must never be persisted there.
    await prefs.remove('saved_password');
    await prefs.remove('saved_email');

    final remembered = prefs.getBool('remember_me') ?? false;
    if (remembered) {
      final email = await SecureStorageService.getRememberedEmail() ?? '';
      if (email.isNotEmpty) {
        setState(() {
          _emailController.text = email;
          _rememberMe = true;
        });
      }
    }
  }

  @override
  void dispose() {
    _topMessageEntry?.remove();
    _topMessageEntry = null;
    _passwordController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (_loginSubmitting) return;
    setState(() => _loginSubmitting = true);
    try {
    if (_emailController.text.isEmpty || _passwordController.text.isEmpty) {
      _showTopMessage('Please fill in all fields');
      return;
    }

    final authProvider = ref.read(authNotifierProvider.notifier);
    final success = await authProvider.login(
      email: _emailController.text.trim(),
      password: _passwordController.text,
      fcmToken: NotificationService.fcmToken,
    );

    if (success && mounted) {
      // On iOS the FCM token usually isn't ready yet when the login request is
      // built above (it waits on APNs), so `fcmToken` was null and the server
      // stored nothing. Push it now that we're authenticated. Fire-and-forget —
      // it must never delay or block entering the app.
      unawaited(FcmTokenSync.syncCurrentToken());

      // Remember ONLY the email (never the password), in encrypted storage.
      if (_rememberMe) {
        final prefs = await SharedPreferences.getInstance();
        await SecureStorageService.saveRememberedEmail(
          _emailController.text.trim(),
        );
        await prefs.setBool('remember_me', true);
      }
      await _offerBiometricLogin();
      if (!mounted) return;
      final user = ref.read(currentUserProvider);
      final route =
          user?.isEmailVerified == false
              ? '/emailVerification'
              : (user?.hasTransactionPin == true
                  ? '/homeNav'
                  : '/transactionPinSetup');
      Navigator.of(context).pushReplacementNamed(route);
    } else if (mounted) {
      final error = ref.read(authErrorProvider);
      if (error != null) {
        _showTopMessage(error);
      }
    }
    } finally {
      if (mounted) setState(() => _loginSubmitting = false);
    }
  }

  // ───────────────────────────────────────────────────────────────────────────
  // Biometric login
  Future<void> _offerBiometricLogin() async {
    try {
      if (await SecureStorageService.hasBiometricPreference() ||
          !await _localAuth.canCheckBiometrics ||
          !await _localAuth.isDeviceSupported() ||
          !mounted)
        return;
      final enable = await showDialog<bool>(
        context: context,
        builder:
            (dialogContext) => AlertDialog(
              title: const Text('Use biometrics next time?'),
              content: const Text(
                'Sign in faster with your fingerprint or face on this device.',
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(dialogContext, false),
                  child: const Text('Not now'),
                ),
                TextButton(
                  onPressed: () => Navigator.pop(dialogContext, true),
                  child: const Text('Enable'),
                ),
              ],
            ),
      );
      if (enable == true) {
        final verified = await _localAuth.authenticate(
          localizedReason: 'Enable biometric sign in',
          options: const AuthenticationOptions(biometricOnly: true),
        );
        await SecureStorageService.setBiometricEnabled(verified);
      } else {
        await SecureStorageService.setBiometricEnabled(false);
      }
    } catch (_) {
      // Password sign in remains available if device biometrics fail.
    }
  }

  //
  // The `no_fragment_activity` PlatformException this used to throw is NOT a
  // Dart-side problem. `local_auth` shows the fingerprint sheet with a
  // BiometricPrompt, which needs an androidx FragmentActivity to attach to.
  // Flutter's default host activity is `FlutterActivity`, which is not one, so
  // the plugin bails out the moment authenticate() is called.
  //
  // The real fix is one line in the Android host — see ANDROID-BIOMETRIC-FIX.md.
  // Everything below is the Dart-side hardening that goes with it: check for a
  // stored session before letting a fingerprint through, and turn the remaining
  // platform errors into messages a user can act on.
  // ───────────────────────────────────────────────────────────────────────────
  Future<void> _handleBiometricLogin() async {
    if (_biometricLoading) return;
    setState(() => _biometricLoading = true);
    try {
      await _performBiometricLogin();
    } finally {
      if (mounted) setState(() => _biometricLoading = false);
    }
  }

  Future<void> _performBiometricLogin() async {
    try {
      if (!await SecureStorageService.isBiometricEnabled()) {
        if (mounted)
          _showTopMessage(
            'Sign in with your password to enable biometric login.',
          );
        return;
      }
      // A fingerprint proves WHO is holding the phone. It does not prove there
      // is a session to resume. Without this check a successful scan pushed
      // straight to /homeNav with no token, and every request on the home
      // screen then failed with a 401.
      final bool hasSession = await SecureStorageService.isAuthenticated();
      if (!hasSession) {
        if (mounted) {
          _showTopMessage(
            'Sign in with your password once, then fingerprint login will work.',
          );
        }
        return;
      }

      final bool isAvailable = await _localAuth.canCheckBiometrics;
      final bool isDeviceSupported = await _localAuth.isDeviceSupported();
      if (!isAvailable || !isDeviceSupported) {
        if (mounted)
          _showTopMessage(
            'Biometric authentication not available on this device',
          );
        return;
      }
      final List<BiometricType> availableBiometrics =
          await _localAuth.getAvailableBiometrics();
      if (availableBiometrics.isEmpty) {
        if (mounted) _showTopMessage('No biometrics enrolled on this device');
        return;
      }
      final bool didAuthenticate = await _localAuth.authenticate(
        localizedReason: 'Please authenticate to login to your account',
        authMessages: const [
          AndroidAuthMessages(
            signInTitle: 'Biometric Authentication',
            cancelButton: 'Cancel',
            biometricHint: 'Touch the fingerprint sensor',
            biometricNotRecognized: 'Biometric not recognized, try again',
            biometricSuccess: 'Biometric authentication successful',
          ),
          IOSAuthMessages(
            cancelButton: 'Cancel',
            goToSettingsButton: 'Go to Settings',
            goToSettingsDescription:
                'Please set up biometric authentication in settings',
            lockOut:
                'Biometric authentication is disabled. Please lock and unlock your screen.',
          ),
        ],
        options: const AuthenticationOptions(
          biometricOnly: true,
          stickyAuth: true,
          sensitiveTransaction: true,
        ),
      );
      if (didAuthenticate && mounted) {
        final account = await ref
            .read(authNotifierProvider.notifier)
            .getUserInfoWithStats(force: true);
        if (!mounted) return;
        if (account == null) {
          _showTopMessage(
            'Could not verify your session. Sign in with your password.',
          );
          return;
        }
        final user = ref.read(currentUserProvider);
        final route =
            user?.isEmailVerified == false
                ? '/emailVerification'
                : (user?.hasTransactionPin == true
                    ? '/homeNav'
                    : '/transactionPinSetup');
        Navigator.of(context).pushReplacementNamed(route);
      } else if (mounted) {
        _showTopMessage('Biometric authentication failed');
      }
    } on PlatformException catch (e) {
      if (!mounted) return;

      // Map the plugin's error codes to something a user can act on, rather
      // than dumping the raw exception into a red banner.
      switch (e.code) {
        case 'no_fragment_activity':
          _showTopMessage(
            'Fingerprint login is unavailable in this build. Please sign in with your password.',
          );
          // Loud in debug so this never ships silently broken again.
          assert(() {
            debugPrint(
              'local_auth: MainActivity must extend FlutterFragmentActivity. '
              'See ANDROID-BIOMETRIC-FIX.md.',
            );
            return true;
          }());
          break;
        case 'NotAvailable':
          _showTopMessage(
            'Biometric authentication is not available on this device',
          );
          break;
        case 'NotEnrolled':
          _showTopMessage('No fingerprint or face is set up on this device');
          break;
        case 'LockedOut':
        case 'PermanentlyLockedOut':
          _showTopMessage(
            'Too many failed attempts. Unlock your phone with your PIN, then try again.',
          );
          break;
        case 'PasscodeNotSet':
          _showTopMessage(
            'Set a screen lock on your device to use fingerprint login',
          );
          break;
        default:
          _showTopMessage(
            e.message?.isNotEmpty == true
                ? e.message!
                : 'Biometric authentication failed',
          );
      }
    } catch (e) {
      if (mounted)
        _showTopMessage('Biometric authentication failed. Please try again.');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      resizeToAvoidBottomInset: false,
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        bottom: false,
        child: Column(
          children: [
            const SizedBox(height: 30),
            Text(
              'LOGIN',
              style: TextStyle(
                fontSize: 25.sp,
                fontWeight: FontWeight.w700,
                color: Colors.white,
              ),
            ),
            const SizedBox(height: 30),
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
                  padding: const EdgeInsets.only(top: 20),
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
                        // Scrollable content
                        Expanded(
                          child: SingleChildScrollView(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                SizedBox(height: 30.h),
                                Text(
                                  'Welcome',
                                  style: TextStyle(
                                    fontSize: 17.sp,
                                    fontWeight: FontWeight.w700,
                                    color: Colors.grey[600],
                                  ),
                                ),
                                SizedBox(height: 6.h),
                                Text(
                                  'Sign in to your account to enjoy our amazing services',
                                  style: TextStyle(
                                    fontSize: 13.sp,
                                    color: Colors.grey[600],
                                  ),
                                  textAlign: TextAlign.center,
                                ),
                                SizedBox(height: 28.h),

                                // Email
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 20,
                                  ),
                                  child: _StyledTextField(
                                    hint: 'Email or Phone Number',
                                    controller: _emailController,
                                    keyboardType: TextInputType.emailAddress,
                                    textInputAction: TextInputAction.next,
                                  ),
                                ),
                                SizedBox(height: 16.h),

                                // Password
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 20,
                                  ),
                                  child: _StyledTextField(
                                    hint: 'Password',
                                    controller: _passwordController,
                                    keyboardType: TextInputType.visiblePassword,
                                    textInputAction: TextInputAction.done,
                                    obscureText: _isPasswordObscured,
                                    suffixIcon: IconButton(
                                      icon: Icon(
                                        _isPasswordObscured
                                            ? Icons.visibility_off_outlined
                                            : Icons.visibility_outlined,
                                        color: const Color(0xFF34544A),
                                      ),
                                      onPressed:
                                          () => setState(
                                            () =>
                                                _isPasswordObscured =
                                                    !_isPasswordObscured,
                                          ),
                                    ),
                                  ),
                                ),
                                SizedBox(height: 12.h),

                                // Remember Me
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 20,
                                  ),
                                  child: Row(
                                    children: [
                                      SizedBox(
                                        width: 24,
                                        height: 24,
                                        child: Checkbox(
                                          value: _rememberMe,
                                          activeColor: const Color(0xFF34544A),
                                          shape: RoundedRectangleBorder(
                                            borderRadius: BorderRadius.circular(
                                              4,
                                            ),
                                          ),
                                          onChanged: (val) async {
                                            setState(
                                              () => _rememberMe = val ?? false,
                                            );
                                            if (!(val ?? false)) {
                                              final prefs =
                                                  await SharedPreferences.getInstance();
                                              await prefs.setBool(
                                                'remember_me',
                                                false,
                                              );
                                              await SecureStorageService.clearRememberedEmail();
                                            }
                                          },
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
                                ),
                                SizedBox(height: 24.h),

                                // Login button
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 24,
                                  ),
                                  child: SizedBox(
                                    width: double.infinity,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(
                                        backgroundColor: const Color(
                                          0xFF34544A,
                                        ),
                                        foregroundColor: Colors.white,
                                        shape: RoundedRectangleBorder(
                                          borderRadius: BorderRadius.circular(
                                            32.r,
                                          ),
                                        ),
                                        padding: EdgeInsets.symmetric(
                                          vertical: 16.h,
                                        ),
                                      ),
                                      onPressed: _loginSubmitting ? null : _handleLogin,
                                      child: Consumer(
                                        builder: (context, ref, child) {
                                          final isLoading = ref.watch(
                                            authLoadingProvider,
                                          );
                                          return isLoading
                                              ? SizedBox(
                                                height: 20.h,
                                                width: 20.w,
                                                child:
                                                    const CircularProgressIndicator(
                                                      color: Colors.white,
                                                      strokeWidth: 2,
                                                    ),
                                              )
                                              : Text(
                                                'Submit',
                                                style: TextStyle(
                                                  fontSize: 18.sp,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              );
                                        },
                                      ),
                                    ),
                                  ),
                                ),
                                SizedBox(height: 16.h),

                                // Forgot Password (left) | Sign Up (right)
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 24,
                                  ),
                                  child: Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      GestureDetector(
                                        onTap:
                                            () => Navigator.of(
                                              context,
                                            ).pushNamed('/forgotPassword'),
                                        child: Text(
                                          'Forgot Password?',
                                          style: TextStyle(
                                            fontSize: 14.sp,
                                            color: const Color(0xFF34544A),
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      GestureDetector(
                                        onTap:
                                            () => Navigator.of(
                                              context,
                                            ).pushNamed('/signup'),
                                        child: Text(
                                          'Sign Up',
                                          style: TextStyle(
                                            fontSize: 14.sp,
                                            color: const Color(0xFF34544A),
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                SizedBox(height: 20.h),

                                // Biometrics
                                Column(
                                  children: [
                                    const SizedBox(height: 4),
                                    IconButton(
                                      onPressed: _biometricLoading ? null : _handleBiometricLogin,
                                      icon: _biometricLoading
                                          ? const SizedBox(width: 42, height: 42, child: CircularProgressIndicator(strokeWidth: 3, color: Color(0xFF34544A)))
                                          : const Icon(Icons.fingerprint_rounded, size: 52, color: Color(0xFF34544A)),
                                    ),
                                  ],
                                ),
                                SizedBox(height: 12.h),
                              ],
                            ),
                          ),
                        ),

                        // Contact Support pinned at bottom
                        Padding(
                          padding: const EdgeInsets.only(bottom: 32, top: 12),
                          child: GestureDetector(
                            onTap:
                                () => Navigator.of(
                                  context,
                                ).pushNamed('/contactSupport'),
                            child: Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 40,
                                vertical: 13,
                              ),
                              decoration: BoxDecoration(
                                color: const Color(0xFF34544A),
                                borderRadius: BorderRadius.circular(32),
                              ),
                              child: Text(
                                'Contact Support',
                                style: TextStyle(
                                  fontSize: 14.sp,
                                  color: Colors.white,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                        ),
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
}

class _StyledTextField extends StatelessWidget {
  final String hint;
  final TextEditingController? controller;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool obscureText;
  final Widget? suffixIcon;
  final ValueChanged<String>? onChanged;

  const _StyledTextField({
    required this.hint,
    this.controller,
    this.keyboardType,
    this.textInputAction,
    this.obscureText = false,
    this.suffixIcon,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      textInputAction: textInputAction,
      obscureText: obscureText,
      onChanged: onChanged,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(color: Colors.grey[500], fontSize: 16.sp),
        filled: true,
        fillColor: const Color(0xFFF8FAF9),
        suffixIcon: suffixIcon,
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
        contentPadding: EdgeInsets.symmetric(vertical: 18.h, horizontal: 16.w),
      ),
    );
  }
}
