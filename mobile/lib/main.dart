import 'package:dupay/screen/airtime/airtime.dart';
import 'package:dupay/screen/atm_request/atm_card_screen.dart';
import 'package:dupay/screen/atm_request/atm_request_screen.dart';
import 'package:dupay/screen/auth/email_verification_screen.dart';
import 'package:dupay/screen/auth/forgot_password.dart';
import 'package:dupay/screen/auth/kyc_screen.dart';
import 'package:dupay/screen/cable/cable_subscription_screen.dart';
import 'package:dupay/screen/foreign_currency/wallet_overview_screen.dart';
import 'package:dupay/screen/fund_wallet/fund_wallet.dart';
import 'package:dupay/screen/home/home_nav.dart';
import 'package:dupay/screen/live_support/live_support_screen.dart';
import 'package:dupay/screen/more/create_pin_screen.dart';
import 'package:dupay/screen/more/change_pin_screen.dart';
import 'package:dupay/screen/more/referral_screen.dart';
import 'package:dupay/screen/more/transaction_pin_screen.dart';
import 'package:dupay/screen/more/transaction_pin_setup_screen.dart';
import 'package:dupay/screen/more/transaction_reset_screen.dart';
import 'package:dupay/screen/notifications_screen.dart';
import 'package:dupay/screen/more/edit_profile_screen.dart';
import 'package:dupay/screen/more/update_password_screen.dart';
import 'package:dupay/screen/qr_code/scan_qrcode.dart';
import 'package:dupay/screen/recharge_card/recharge_card_screen.dart';
import 'package:dupay/screen/send_money/send_money_screen.dart';
import 'package:dupay/services/app_initialization_service.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart'
    hide SizeExtension, RadiusExtension;
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'screen/auth/splash_screen.dart';
import 'screen/auth/login_screen.dart';
import 'screen/auth/sign_up_screen.dart';
import 'package:dupay/screen/pos/pos_request_screen.dart';
import 'package:dupay/screen/data/buy_data_screen.dart';
import 'package:dupay/screen/bill/bill_payment_screen.dart';
import 'package:dupay/screen/transaction/transaction_receipt_screen.dart';
import 'package:dupay/screen/card/claim_cards_screen.dart';
// import 'package:dupay/screen/card/card_management_screen.dart';

import 'package:dupay/screen/auth/contact_support_screen.dart';
import 'dart:async' show unawaited;
import 'dart:io' show Platform;
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:dupay/services/notifications/notification_service.dart';
import 'package:dupay/services/notifications/fcm_token_sync.dart';
import 'package:dupay/services/auth/session_events.dart';

// Import the initialization service (you'll need to create this file)

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
final GlobalKey<ScaffoldMessengerState> notificationMessengerKey =
    GlobalKey<ScaffoldMessengerState>();

void main() async {
  // Ensure Flutter binding is initialized
  final widgetsBinding = WidgetsFlutterBinding.ensureInitialized();
  SessionEvents.expired.listen((_) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      navigatorKey.currentState?.pushNamedAndRemoveUntil(
        '/login',
        (_) => false,
      );
    });
  });

  // Keep the NATIVE splash on screen until SplashScreen finishes its auth
  // routing (it then calls FlutterNativeSplash.remove()). This is what removes
  // the "double splash": the user only ever sees the native splash, which stays
  // up through startup and reveals the destination directly — the Flutter
  // SplashScreen no longer flashes its own logo underneath.
  FlutterNativeSplash.preserve(widgetsBinding: widgetsBinding);

  // Firebase + FCM on both platforms. iOS reads its config from
  // ios/Runner/GoogleService-Info.plist (bundled into the Runner target) and
  // Android from android/app/google-services.json — Firebase.initializeApp()
  // picks the right one up automatically. The try/catch keeps a missing or
  // malformed config non-fatal: the app still starts, just without push.
  try {
    await Firebase.initializeApp();
    NotificationService.navigatorKey = navigatorKey;
    FcmTokenSync.install();
    unawaited(NotificationService().initialize());
  } catch (e) {
    print('⚠️ Firebase/FCM init skipped: $e');
    if (Platform.isIOS) {
      print(
        '   iOS: make sure ios/Runner/GoogleService-Info.plist exists and '
        'is added to the Runner target — see docs/ios_push_setup.md',
      );
    }
  }

  try {
    print('🚀 Starting DuPay app initialization...');

    await AppInitializationService.initialize();

    print('✅ App initialization completed successfully');
    runApp(const MyApp());
  } catch (e) {
    print('❌ Failed to initialize app: $e');

    // Init failed — reveal the error screen (otherwise the native splash would
    // stay up forever).
    FlutterNativeSplash.remove();

    // Show error screen if initialization fails
    runApp(
      MaterialApp(
        navigatorKey: navigatorKey,
        debugShowCheckedModeBanner: false,
        home: AppInitializationErrorScreen(error: e.toString()),
      ),
    );
  }
}

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ProviderScope(
      child: ScreenUtilInit(
        designSize: const Size(428, 926),
        minTextAdapt: true,
        splitScreenMode: true,
        builder:
            (context, child) => MaterialApp(
              navigatorKey: navigatorKey,
              scaffoldMessengerKey: notificationMessengerKey,
              debugShowCheckedModeBanner: false,
              initialRoute: '/',
              routes: {
                '/': (context) => const SplashScreen(),
                '/login': (context) => const LoginScreen(),
                '/contactSupport': (context) => const ContactSupportScreen(),
                '/signup': (context) => const SignUpScreen(),
                '/kyc': (context) => const KycScreen(),
                '/liveChat': (context) => const LiveChatScreen(),

                '/homeNav': (context) => const HomeNav(),
                '/sendmoney': (context) => const SendMoneyScreen(),
                '/notification': (context) => const NotificationsScreen(),
                '/posrequest': (context) => const PosRequestScreen(),
                '/atmrequest': (context) => AtmRequestScreen(),
                '/atmCardScreen': (context) => const AtmCardScreen(),
                '/createPin': (context) => const CreatePinScreen(),
                '/changePin': (context) => const ChangePinScreen(),
                '/transactionPin': (context) => const TransactionPinScreen(),
                '/buydata': (context) => const BuyDataScreen(),
                '/cablesub': (context) => const CableSubscriptionScreen(),
                '/billpayment': (context) => const BillPaymentScreen(),
                '/airtime': (context) => const AirtimeScreen(),
                '/scanqr': (context) => const ScanQrcodeScreen(),
                '/forgotPassword': (context) => const ForgotPasswordScreen(),
                '/rechargeCard': (context) => const RechargeCardScreen(),
                '/fundwallet': (context) => const FundWalletScreen(),
                '/foreignCurrency': (context) => const WalletOverviewScreen(),
                '/referralScreen': (context) => const ReferralScreen(),
                '/editProfile': (context) => const EditProfileScreen(),
                '/updatePassword': (context) => const UpdatePasswordScreen(),
                '/emailVerification':
                    (context) => const EmailVerificationScreen(),
                '/transactionReset':
                    (context) => const TransactionPinResetScreen(),
                '/transactionPinSetup':
                    (context) => const TransactionPinSetupScreen(),

                '/transactionReceipt':
                    (context) => TransactionReceiptScreen(
                      data: TransactionReceiptData(
                        service: 'Transaction',
                        type: 'Credit',
                        description: '',
                        amount: '',
                        status: 'success',
                        reference: '',
                        initialBalance: '',
                        finalBalance: '',
                        date: DateTime.now(),
                      ),
                    ),
                '/claimCards': (context) => const ClaimCardsScreen(),

                // '/cardManagement': (context) => const CardManagementScreen(),
                // These routes are reached via Navigator.push with a real card.
                // The named routes are kept as fallbacks with an empty placeholder.
                // '/cardActivity': (context) => const CardManagementScreen(),
                // '/addFunds': (context) => const CardManagementScreen(),
              },
            ),
      ),
    );
  }
}

/// Error screen shown when app initialization fails
class AppInitializationErrorScreen extends StatelessWidget {
  final String error;

  const AppInitializationErrorScreen({super.key, required this.error});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 80, color: Colors.red),
              const SizedBox(height: 24),
              const Text(
                'Failed to Initialize App',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 16),
              Text(
                'An error occurred while setting up the app configuration.',
                style: TextStyle(fontSize: 16, color: Colors.grey[600]),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.red[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red[200]!),
                ),
                child: Text(
                  error,
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.red[700],
                    fontFamily: 'monospace',
                  ),
                ),
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF34544A),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  onPressed: () {
                    // Restart the app
                    main();
                  },
                  child: const Text(
                    'Retry',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: () {
                  // You can add additional debug info or contact support
                  showDialog(
                    context: context,
                    builder:
                        (context) => AlertDialog(
                          title: const Text('Debug Information'),
                          content: SingleChildScrollView(
                            child: Text(
                              'Error Details:\n\n$error\n\n'
                              'Please contact support if this issue persists.',
                              style: const TextStyle(fontFamily: 'monospace'),
                            ),
                          ),
                          actions: [
                            TextButton(
                              onPressed: () => Navigator.of(context).pop(),
                              child: const Text('Close'),
                            ),
                          ],
                        ),
                  );
                },
                child: const Text(
                  'View Details',
                  style: TextStyle(color: Color(0xFF34544A)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
