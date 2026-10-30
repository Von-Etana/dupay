// lib/screen/more/more_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../providers/auth_provider.dart';
import '../../core/network/api_service.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class MoreScreen extends ConsumerStatefulWidget {
  const MoreScreen({super.key});

  @override
  ConsumerState<MoreScreen> createState() => _MoreScreenState();
}

class _MoreScreenState extends ConsumerState<MoreScreen> {

  // smsAlert is derived from user state — refreshed from the backend below
  // so the toggle always reflects the server's current value (not just
  // whatever was cached at login or last left in memory).

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        ref.read(authNotifierProvider.notifier).forceRefreshUserInfo();
      }
    });
  }

  Future<void> _logout() async {
    // Confirm dialog
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Logout', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) =>
          const Center(child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    await ref.read(authNotifierProvider.notifier).logout();

    if (!mounted) return;
    // Close loader then push to login, clearing the entire stack
    Navigator.of(context).pop();
    Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
  }

  Future<void> _deleteAccount() async {
    final passwordController = TextEditingController();

    // Confirmation dialog: requires the user's password and shows the
    // retention disclosure (required for a financial app).
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Account'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'This will permanently delete your Dupay account. You will be '
              'logged out and will not be able to sign in again.\n\n'
              'For regulatory compliance, certain transaction records are '
              'retained for the period required by financial law. '
              'This action cannot be undone.',
            ),
            const SizedBox(height: 16),
            TextField(
              controller: passwordController,
              obscureText: true,
              decoration: const InputDecoration(
                labelText: 'Enter your password',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final password = passwordController.text.trim();
    if (password.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter your password to confirm.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) =>
          const Center(child: CircularProgressIndicator(color: Color(0xFF34544A))),
    );

    try {
      // postSimple throws a ServerException when the backend returns
      // success:false (e.g. wrong password, or a remaining wallet balance),
      // so reaching past this line means deletion succeeded.
      await ApiService.instance.postSimple(
        '/setting/delete-account',
        data: {'password': password},
      );

      // Deletion succeeded — log the user out everywhere and clear the stack.
      await ref.read(authNotifierProvider.notifier).logout();

      if (!mounted) return;
      Navigator.of(context).pop(); // close loader
      Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Your account has been deleted.'),
          backgroundColor: Color(0xFF34544A),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.of(context).pop(); // close loader
      // ServerException.toString() returns the backend message directly
      // (e.g. "Incorrect password..." or the withdraw-funds-first message).
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString().replaceFirst('Exception: ', '')),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(currentUserProvider);
    final smsAlert = user?.receiveSMS == 1;

    // A user is KYC verified when hasVerifyWallet == '1'
    final bool isKycVerified = user?.isWalletVerified == true;

    return Scaffold(
      backgroundColor: const Color(0xFF34544A),
      body: SafeArea(
        child: Stack(
          children: [
            Column(
              children: [
                const SizedBox(height: 30),
                Center(
                  child: Text(
                    'Profile',
                    style: TextStyle(
                      fontSize: 25.sp,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
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
                        child: ListView(
                          padding: const EdgeInsets.symmetric(
                              vertical: 24, horizontal: 0),
                          children: [
                            _ProfileTile(
                              icon: Icons.person,
                              label: 'Information',
                              onTap: () =>
                                  Navigator.pushNamed(context, '/editProfile'),
                            ),
                            _ProfileTile(
                              icon: Icons.qr_code,
                              label: 'QR Code',
                              onTap: () =>
                                  Navigator.pushNamed(context, '/scanqr'),
                            ),

                            // ── Only show KYC tile if NOT yet verified ──
                            if (!isKycVerified)
                              _ProfileTile(
                                icon: Icons.verified_user,
                                label: 'KYC Verification',
                                onTap: () =>
                                    Navigator.pushNamed(context, '/kyc'),
                              ),

                            // If verified, show a non-tappable verified badge instead
                            if (isKycVerified)
                              _ProfileTile(
                                icon: Icons.verified,
                                label: 'KYC Verified ✓',
                               
                                onTap: null,
                              ),

                            _ProfileTile(
                              icon: Icons.pin,
                              label: 'Transaction Pin',
                              onTap: () => Navigator.pushNamed(
                                  context, '/transactionPin'),
                            ),
                            _ProfileTile(
                              icon: Icons.lock,
                              label: 'Password',
                              onTap: () => Navigator.pushNamed(
                                  context, '/updatePassword'),
                            ),
                            _ProfileTile(
                              icon: Icons.money,
                              label: 'Referral',
                              onTap: () => Navigator.pushNamed(
                                  context, '/referralScreen'),
                            ),
                            
                            _ProfileSwitchTile(
                              icon: Icons.sms,
                              label: 'SMS Alert',
                              value: smsAlert,
                              onChanged: (val) async {
                                // Optimistically update via auth provider
                                ref.read(authNotifierProvider.notifier)
                                    .updateSmsAlert(val);
                                try {
                                  // The backend reads `receive_sms` (snake_case)
                                  // as an int (1/0) — the old payload sent
                                  // `receiveSMS` as a bool, so the value never
                                  // persisted and the toggle reverted on the
                                  // next refresh. Send both keys as ints to be
                                  // safe.
                                  final response = await ApiService.instance
                                      .postSimple(
                                    '/update-sms-alerts',
                                    data: {
                                      'receive_sms': val ? 1 : 0,
                                      'receiveSMS': val ? 1 : 0,
                                    },
                                  );
                                  if (!response.success) {
                                    // revert if API failed
                                    ref.read(authNotifierProvider.notifier)
                                        .updateSmsAlert(!val);
                                  }
                                } catch (_) {
                                  ref.read(authNotifierProvider.notifier)
                                      .updateSmsAlert(!val);
                                }
                              },
                            ),
                            const SizedBox(height: 32),
                            Padding(
                              padding:
                                  const EdgeInsets.symmetric(horizontal: 16),
                              child: SizedBox(
                                width: double.infinity,
                                child: ElevatedButton(
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.red,
                                    foregroundColor: Colors.white,
                                    shape: RoundedRectangleBorder(
                                      borderRadius:
                                          BorderRadius.circular(32.r),
                                    ),
                                    padding:
                                        EdgeInsets.symmetric(vertical: 16.h),
                                  ),
                                  onPressed: _logout,
                                  child: Text(
                                    'Logout',
                                    style: TextStyle(
                                      fontSize: 18.sp,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                            SizedBox(height: 12.h),
                            // ── Delete Account (destructive, in-app —
                            //    required by App Store Guideline 5.1.1(v)) ──
                            Center(
                              child: TextButton.icon(
                                onPressed: _deleteAccount,
                                icon: const Icon(Icons.delete_forever,
                                    color: Colors.red),
                                label: Text(
                                  'Delete Account',
                                  style: TextStyle(
                                    fontSize: 15.sp,
                                    color: Colors.red,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 80),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _ProfileTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final Color? iconColor;

  const _ProfileTile({
    required this.icon,
    required this.label,
    this.onTap,
    this.iconColor,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ListTile(
          contentPadding:
              const EdgeInsets.symmetric(vertical: 10, horizontal: 20),
          leading: CircleAvatar(
            backgroundColor:
                iconColor?.withOpacity(0.15) ?? const Color(0xFF34544A),
            child: Icon(icon, color: iconColor ?? Colors.white),
          ),
          title: Text(
            label,
            style: TextStyle(fontSize: 17.sp, fontWeight: FontWeight.w500),
          ),
          trailing: onTap != null
              ? Icon(Icons.chevron_right, color: Colors.grey[400])
              : null,
          onTap: onTap,
        ),
      ],
    );
  }
}

class _ProfileSwitchTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool value;
  final ValueChanged<bool> onChanged;

  const _ProfileSwitchTile({
    required this.icon,
    required this.label,
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding:
          const EdgeInsets.symmetric(vertical: 10, horizontal: 20),
      leading: CircleAvatar(
        backgroundColor: const Color(0xFF34544A),
        child: Icon(icon, color: Colors.white),
      ),
      title: Text(
        label,
        style: TextStyle(fontSize: 17.sp, fontWeight: FontWeight.w500),
      ),
      trailing: Switch(
        value: value,
        onChanged: onChanged,
        activeColor: const Color(0xFF34544A),
      ),
    );
  }
}