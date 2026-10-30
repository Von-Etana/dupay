// import 'package:dupay/screen/activity/activity_screen.dart';
// import 'package:dupay/screen/send_money/p2p_screen.dart';
// import 'package:dupay/screen/send_money/bank_transfer_screen.dart';
// import 'package:dupay/screen/card/card_screen.dart';
// import 'package:dupay/screen/more/edit_profile_screen.dart';
// import 'package:dupay/screen/more/more_screen.dart';
// import 'package:flutter/material.dart';
// import 'package:flutter/services.dart';
// import 'package:flutter_screenutil/flutter_screenutil.dart';
// import 'package:hooks_riverpod/hooks_riverpod.dart';
// import 'package:dupay/widgets/service_card.dart';
// import 'package:dupay/providers/fund_wallet_provider.dart';
// import 'package:dupay/widgets/action_button.dart';
// import '../../providers/auth_provider.dart';
// import '../../core/utils/formatters.dart';

// class HomeNav extends ConsumerStatefulWidget {
//   const HomeNav({super.key});

//   @override
//   ConsumerState<HomeNav> createState() => _HomeNavState();
// }

// class _HomeNavState extends ConsumerState<HomeNav> {
//   int _selectedIndex = 0;

//   final List<Widget> _screens = [
//     const HomeScreen(),
//     const CardScreen(),
//     ActivityScreen(),
//     const MoreScreen(),
//   ];

//   @override
//   void initState() {
//     super.initState();
//     _checkAuthStatus();
//   }

//   void _checkAuthStatus() {
//     WidgetsBinding.instance.addPostFrameCallback((_) {
//       final isAuthenticated = ref.read(isAuthenticatedProvider);
//       if (!isAuthenticated) {
//         Navigator.of(context).pushReplacementNamed('/login');
//       }
//     });
//   }

//   void _onItemTapped(int index) {
//     setState(() {
//       _selectedIndex = index;
//     });
//   }

//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//       body: _screens[_selectedIndex],
//       floatingActionButton: Padding(
//         padding: const EdgeInsets.only(
//           bottom: 20,
//         ), // Position above bottom navigation
//         child: Material(
//           color: const Color(0xFF34544A),
//           borderRadius: BorderRadius.circular(24),
//           elevation: 4,
//           child: InkWell(
//             borderRadius: BorderRadius.circular(24),
//             onTap: () {
//               Navigator.pushNamed(context, '/liveChat');
//             },
//             child: Padding(
//               padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
//               child: Row(
//                 mainAxisSize: MainAxisSize.min,
//                 children: [
//                   const Icon(Icons.chat_bubble_outline, color: Colors.white),
//                   const SizedBox(width: 8),
//                   Text(
//                     'Live Chat',
//                     style: TextStyle(
//                       color: Colors.white,
//                       fontWeight: FontWeight.bold,
//                       fontSize: 15.sp,
//                     ),
//                   ),
//                 ],
//               ),
//             ),
//           ),
//         ),
//       ),
//       floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
//       bottomNavigationBar: BottomNavigationBar(
//         currentIndex: _selectedIndex,
//         onTap: _onItemTapped,
//         type: BottomNavigationBarType.fixed,
//         selectedItemColor: const Color(0xFF34544A),
//         unselectedItemColor: Colors.grey,
//         backgroundColor: Colors.white,
//         items: const [
//           BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Home'),
//           BottomNavigationBarItem(icon: Icon(Icons.credit_card), label: 'Card'),
//           BottomNavigationBarItem(
//             icon: Icon(Icons.receipt_long),
//             label: 'Activity',
//           ),
//           BottomNavigationBarItem(icon: Icon(Icons.menu), label: 'More'),
//         ],
//       ),
//     );
//   }
// }

// // HomeScreen will be implemented below
// class HomeScreen extends StatelessWidget {
//   const HomeScreen({super.key});
//   @override
//   Widget build(BuildContext context) {
//     // Placeholder for now, will implement UI in next step
//     return HomeDashboard();
//   }
// }

// // HomeDashboard with real-time data
// class HomeDashboard extends ConsumerStatefulWidget {
//   @override
//   ConsumerState<HomeDashboard> createState() => _HomeDashboardState();
// }

// class _HomeDashboardState extends ConsumerState<HomeDashboard> {
//   bool _isBalanceVisible = true; // Track balance visibility state

//   @override
//   void initState() {
//     super.initState();
//     // Only refresh user data if we don't have it already
//     WidgetsBinding.instance.addPostFrameCallback((_) {
//       final authState = ref.read(authNotifierProvider);
//       if (authState.user == null) {
//         print('🔐 HomeScreen: No user data, refreshing...');
//         ref.read(authNotifierProvider.notifier).getUserInfo();
//       } else {
//         print(
//           '🔐 HomeScreen: User data already exists: ${authState.user?.email}',
//         );
//       }
//     });
//   }

//   // Toggle balance visibility
//   void _toggleBalanceVisibility() {
//     HapticFeedback.lightImpact(); // Add haptic feedback
//     setState(() {
//       _isBalanceVisible = !_isBalanceVisible;
//     });
//   }

//   void _showSendOptions(BuildContext context) {
//     showModalBottomSheet(
//       context: context,
//       backgroundColor: Colors.white,
//       shape: const RoundedRectangleBorder(
//           borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
//       builder: (_) => Padding(
//         padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
//         child: Column(
//           mainAxisSize: MainAxisSize.min,
//           crossAxisAlignment: CrossAxisAlignment.start,
//           children: [
//             Center(
//               child: Container(
//                 width: 40, height: 4,
//                 decoration: BoxDecoration(
//                     color: Colors.grey[300],
//                     borderRadius: BorderRadius.circular(2)),
//               ),
//             ),
//             const SizedBox(height: 20),
//             const Text('Send Money',
//                 style: TextStyle(
//                     fontSize: 20, fontWeight: FontWeight.bold)),
//             const SizedBox(height: 4),
//             Text('Choose transfer type',
//                 style: TextStyle(fontSize: 14, color: Colors.grey[600])),
//             const SizedBox(height: 24),

//             // Bank Transfer
//             _SendOption(
//               icon: Icons.account_balance_outlined,
//               title: 'Bank Transfer',
//               subtitle: 'Send to any Nigerian bank account',
//               onTap: () {
//                 Navigator.pop(context);
//                 Navigator.push(context,
//                     MaterialPageRoute(builder: (_) => const BankTransferScreen()));
//               },
//             ),
//             const SizedBox(height: 16),


//             // P2P Transfer
//             _SendOption(
//               icon: Icons.people_outline,
//               title: 'Dupay to Dupay Transfer',
//               subtitle: 'Send to another Dupay user instantly',
//               onTap: () {
//                 Navigator.pop(context);
//                 Navigator.push(context,
//                     MaterialPageRoute(builder: (_) => const P2PScreen()));
//               },
//             ),
//             const SizedBox(height: 12),

            

//           ],
//         ),
//       ),
//     );
//   }

//   @override
//   Widget build(BuildContext context) {
//     final authState = ref.watch(authNotifierProvider);
//     final user = authState.user;
//     final isLoading = authState.isLoading;

//     String formatBalance(String? balance) => formatCurrency(balance);

//     // Get user display name
//     String getUserDisplayName() {
//       if (user?.profile?.firstName != null && user?.profile?.lastName != null) {
//         return '${user!.profile!.firstName}';
//       } else if (user?.profile?.username != null) {
//         return user!.profile!.username!;
//       } else if (user?.email != null) {
//         return user!.email!.split('@')[0];
//       }
//       return 'User';
//     }

//     return RefreshIndicator(
//       color: const Color(0xFF34544A),
//       onRefresh: () async {
//         await ref.read(authNotifierProvider.notifier).refreshUser();
//         if (!context.mounted) return;
       
//       },
//       child: Container(
//         color: Colors.white,
//         child: SingleChildScrollView(
//           physics: const AlwaysScrollableScrollPhysics(),
//           child: Column(
//             children: [
//               // Curved green header
//               Container(
//                 width: double.infinity,
//                 decoration: const BoxDecoration(
//                   gradient: LinearGradient(
//                     colors: [Color(0xFF34544A), Color(0xFF7CA183)],
//                     begin: Alignment.topCenter,
//                     end: Alignment.bottomCenter,
//                   ),
//                   borderRadius: BorderRadius.only(
//                     bottomLeft: Radius.circular(40),
//                     bottomRight: Radius.circular(40),
//                   ),
//                 ),
//                 padding: EdgeInsets.only(
//                   top: 70.h,
//                   left: 20,
//                   right: 20,
//                   bottom: 24,
//                 ),
//                 child: Column(
//                   crossAxisAlignment: CrossAxisAlignment.start,
//                   children: [
//                     Row(
//                       children: [
//                         InkWell(
//                           onTap: () {
//                             Navigator.of(context).push(
//                               MaterialPageRoute(
//                                 builder: (_) => const EditProfileScreen(),
//                               ),
//                             );
//                           },
//                           child: CircleAvatar(
//                             radius: 26,
//                             backgroundColor: Colors.white,
//                             child:
//                                 user?.profile?.profileImg != null
//                                     ? ClipOval(
//                                       child: Image.network(
//                                         user!.profile!.profileImg!,
//                                         width: 52,
//                                         height: 52,
//                                         fit: BoxFit.cover,
//                                         errorBuilder: (
//                                           context,
//                                           error,
//                                           stackTrace,
//                                         ) {
//                                           return const Icon(
//                                             Icons.person,
//                                             size: 36,
//                                             color: Color(0xFF34544A),
//                                           );
//                                         },
//                                       ),
//                                     )
//                                     : const Icon(
//                                       Icons.person,
//                                       size: 36,
//                                       color: Color(0xFF34544A),
//                                     ),
//                           ),
//                         ),
//                         const SizedBox(width: 12),
//                         Column(
//                           crossAxisAlignment: CrossAxisAlignment.start,
//                           children: [
//                             Text(
//                               'Hello ${getUserDisplayName()}',
//                               style: TextStyle(
//                                 color: Colors.white,
//                                 fontWeight: FontWeight.bold,
//                                 fontSize: 16.sp,
//                               ),
//                             ),
//                             Text(
//                               'Welcome Back!',
//                               style: TextStyle(
//                                 color: const Color(0xFFB6E09C),
//                                 fontSize: 13.sp,
//                               ),
//                             ),
//                           ],
//                         ),
//                         const Spacer(),
//                         Row(
//                           children: [
//                             Container(
//                               decoration: BoxDecoration(
//                                 color: Colors.white.withOpacity(0.15),
//                                 shape: BoxShape.circle,
//                               ),
//                               child: IconButton(
//                                 icon: const Icon(
//                                   Icons.refresh,
//                                   color: Colors.white,
//                                 ),
//                                 onPressed: () async {
//                                   await ref
//                                       .read(authNotifierProvider.notifier)
//                                       .refreshUser();
//                                   if (!context.mounted) return;
                                 
//                                 },
//                               ),
//                             ),
//                             const SizedBox(width: 8),
                          
//                           ],
//                         ),
//                       ],
//                     ),
//                     const SizedBox(height: 16),
//                     Center(
//                       child: Column(
//                         children: [
//                           Text(
//                             'Available Balance',
//                             style: TextStyle(
//                               color: Colors.white.withOpacity(0.6),
//                               fontSize: 24.sp,
//                               fontWeight: FontWeight.bold,
//                             ),
//                           ),
//                           Row(
//                             mainAxisAlignment: MainAxisAlignment.center,
//                             children: [
//                               AnimatedSwitcher(
//                                 duration: const Duration(milliseconds: 300),
//                                 child: Text(
//                                   _isBalanceVisible
//                                       ? formatBalance(user?.balance)
//                                       : '₦****',
//                                   style: TextStyle(
//                                     color: Colors.white,
//                                     fontWeight: FontWeight.bold,
//                                     fontSize: 24.sp,
//                                   ),
//                                   key: ValueKey(_isBalanceVisible),
//                                 ),
//                               ),
//                               const SizedBox(width: 8),
//                               Tooltip(
//                                 message:
//                                     _isBalanceVisible
//                                         ? 'Hide balance'
//                                         : 'Show balance',
//                                 child: GestureDetector(
//                                   onTap: _toggleBalanceVisibility,
//                                   child: AnimatedSwitcher(
//                                     duration: const Duration(milliseconds: 200),
//                                     child: Icon(
//                                       _isBalanceVisible
//                                           ? Icons.visibility_outlined
//                                           : Icons.visibility_off_outlined,
//                                       color: const Color(0xFF1CC6B7),
//                                       size: 24,
//                                       key: ValueKey(_isBalanceVisible),
//                                     ),
//                                   ),
//                                 ),
//                               ),
//                             ],
//                           ),
//                         ],
//                       ),
//                     ),
//                     const SizedBox(height: 10),
//                     const Divider(height: 1, color: Colors.grey),
//                     const SizedBox(height: 16),
//                     Row(
//                       mainAxisAlignment: MainAxisAlignment.spaceEvenly,
//                       children: [
//                         ActionButton(
//                           icon: Icons.send,
//                           label: 'Send',
//                           onTap: () => _showSendOptions(context),
//                         ),
//                         ActionButton(
//                           icon: Icons.account_balance_wallet,
//                           label: 'Fund Wallet',
//                           onTap: () async {
//                             final fwState = ref.read(fundWalletNotifierProvider);
//                             // Load account if not yet loaded
//                             if (!fwState.hasAccount && fwState.virtualAccount == null) {
//                               await ref.read(fundWalletNotifierProvider.notifier).loadVirtualAccount();
//                             }
//                             final updated = ref.read(fundWalletNotifierProvider);

//                             // Treat "no account" as either: no account at all,
//                             // or an account record with no/empty account number.
//                             final account = updated.virtualAccount;
//                             final hasAccountNumber = updated.hasAccount &&
//                                 account != null &&
//                                 account.accountNumber != null &&
//                                 account.accountNumber!.isNotEmpty;

//                             if (!context.mounted) return;

//                             if (hasAccountNumber) {
//                               Navigator.pushNamed(context, '/fundwallet');
//                             } else {
//                               // No account number for this user — send to KYC.
//                               Navigator.pushNamed(context, '/kyc');
//                             }
//                           },
//                         ),
//                         ActionButton(
//                           icon: Icons.qr_code,
//                           label: 'QR Pay',
//                           onTap: () {
//                             Navigator.pushNamed(context, '/scanqr');
//                           },
//                         ),
//                       ],
//                     ),
//                   ],
//                 ),
//               ),
//               const SizedBox(height: 24),
//               // Bonus Card with real-time data
//               Padding(
//                 padding: const EdgeInsets.symmetric(horizontal: 16),
//                 child: Container(
//                   width: double.infinity,
//                   padding: const EdgeInsets.symmetric(
//                     vertical: 12,
//                     horizontal: 16,
//                   ),
//                   decoration: BoxDecoration(
//                     color: const Color(0xFF34544A),
//                     borderRadius: BorderRadius.circular(16),
//                   ),
//                   child: Row(
//                     children: [
//                       const Icon(Icons.card_giftcard, color: Colors.white),
//                       const SizedBox(width: 8),
//                       AnimatedSwitcher(
//                         duration: const Duration(milliseconds: 300),
//                         child: Text(
//                           'Bonus: ${_isBalanceVisible ? formatBalance(user?.bonus) : '₦****'}',
//                           style: TextStyle(
//                             color: Colors.white,
//                             fontWeight: FontWeight.w600,
//                             fontSize: 15.sp,
//                           ),
//                           key: ValueKey(_isBalanceVisible),
//                         ),
//                       ),
//                     ],
//                   ),
//                 ),
//               ),
//               // Services Grid - Now uses shrinkWrap instead of Expanded
//               Container(
//                 color: const Color(0xFFF6F8F7),
//                 padding: const EdgeInsets.only(
//                   left: 16,
//                   right: 16,
//                   bottom: 24,
//                   top: 16,
//                 ),
//                 child: GridView.count(
//                   padding: EdgeInsets.zero,
//                   shrinkWrap:
//                       true, // This allows the GridView to size itself based on content
//                   physics:
//                       const NeverScrollableScrollPhysics(), // Disable GridView scrolling since parent is scrollable
//                   crossAxisCount: 3,
//                   mainAxisSpacing: 16,
//                   crossAxisSpacing: 16,
//                   children: [
//                     ServiceCard(
//                       onTap: () {
//                         Navigator.pushNamed(context, '/buydata');
//                       },
//                       icon: Icons.wifi_tethering_error_rounded_outlined,
//                       label: 'Data',
//                     ),
//                     ServiceCard(
//                       icon: Icons.phone_iphone_outlined,
//                       label: 'Airtime',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/airtime');
//                       },
//                     ),
//                     ServiceCard(
//                       icon: Icons.confirmation_number_outlined,
//                       label: 'Foreign currency',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/foreignCurrency');
//                       },
//                     ),
//                     ServiceCard(
//                       icon: Icons.atm_rounded,
//                       label: 'ATM Card',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/atmCardScreen');
//                       },
//                     ),
//                     ServiceCard(
//                       icon: Icons.point_of_sale_outlined,
//                       label: 'Order ATM Card',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/atmrequest');
//                       },
//                     ),
//                     ServiceCard(
//                       icon: Icons.atm_rounded,
//                       label: 'Recharge Card',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/rechargeCard');
//                       },
//                     ),
//                     ServiceCard(
//                       icon: Icons.tv_rounded,
//                       label: 'Cable Subscription ',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/cablesub');
//                       },
//                     ),

                      
//                     ServiceCard(
//                       icon: Icons.receipt_outlined,
//                       label: 'Bill Payment',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/billpayment');
//                       },
//                     ),
                 

                 
//                        ServiceCard(
//                       icon: Icons.point_of_sale_outlined,
//                       label: 'POS',
//                       onTap: () {
//                         Navigator.pushNamed(context, '/posrequest');
//                       },
//                     ),
                 
//                   ],
//                 ),
//               ),
//             ],
//           ),
//         ),
//       ),
//     );
//   }
// }

// // ─── Send Option Tile ─────────────────────────────────────────────────────────

// class _SendOption extends StatelessWidget {
//   final IconData icon;
//   final String title;
//   final String subtitle;
//   final VoidCallback onTap;

//   const _SendOption({
//     required this.icon,
//     required this.title,
//     required this.subtitle,
//     required this.onTap,
//   });

//   @override
//   Widget build(BuildContext context) {
//     return InkWell(
//       onTap: onTap,
//       borderRadius: BorderRadius.circular(16),
//       child: Container(
//         padding: const EdgeInsets.all(16),
//         decoration: BoxDecoration(
//           color: const Color(0xFFF8FAF9),
//           borderRadius: BorderRadius.circular(16),
//           border: Border.all(color: Colors.grey[200]!),
//         ),
//         child: Row(children: [
//           Container(
//             width: 48, height: 48,
//             decoration: BoxDecoration(
//               color: const Color(0xFF34544A).withOpacity(0.1),
//               borderRadius: BorderRadius.circular(12),
//             ),
//             child: Icon(icon, color: const Color(0xFF34544A), size: 24),
//           ),
//           const SizedBox(width: 14),
//           Expanded(
//             child: Column(
//               crossAxisAlignment: CrossAxisAlignment.start,
//               children: [
//                 Text(title,
//                     style: const TextStyle(
//                         fontWeight: FontWeight.bold, fontSize: 15)),
//                 const SizedBox(height: 2),
//                 Text(subtitle,
//                     style: TextStyle(
//                         fontSize: 13, color: Colors.grey[600])),
//               ],
//             ),
//           ),
//           Icon(Icons.chevron_right, color: Colors.grey[400]),
//         ]),
//       ),
//     );
//   }
// }


import 'dart:async';
import 'package:dupay/screen/activity/activity_screen.dart';
import 'package:dupay/screen/send_money/p2p_screen.dart';
import 'package:dupay/screen/send_money/bank_transfer_screen.dart';
import 'package:dupay/screen/card/card_screen.dart';
import 'package:dupay/screen/more/edit_profile_screen.dart';
import 'package:dupay/screen/more/more_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:hooks_riverpod/hooks_riverpod.dart';
import 'package:dupay/widgets/service_card.dart';
import 'package:dupay/providers/fund_wallet_provider.dart';
import 'package:dupay/widgets/action_button.dart';
import '../../providers/auth_provider.dart';
import '../../core/utils/formatters.dart';
import 'package:dupay/services/data_preloader.dart';

class HomeNav extends ConsumerStatefulWidget {
  const HomeNav({super.key});

  @override
  ConsumerState<HomeNav> createState() => _HomeNavState();
}

class _HomeNavState extends ConsumerState<HomeNav> {
  int _selectedIndex = 0;

  final List<Widget> _screens = [
    const HomeScreen(),
    const CardScreen(),
    ActivityScreen(),
    const MoreScreen(),
  ];

  @override
  void initState() {
    super.initState();
    _checkAuthStatus();
  }

  void _checkAuthStatus() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final isAuthenticated = ref.read(isAuthenticatedProvider);
      if (!isAuthenticated) {
        Navigator.of(context).pushReplacementNamed('/login');
        return;
      }
      // Authenticated: warm the caches in the background so Recharge Card,
      // Fund Wallet, Foreign Account, Bank List, Airtime and Data already
      // have their data ready before the user opens those pages.
      DataPreloader.run(ref);
    });
  }

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: _screens[_selectedIndex],
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(
          bottom: 20,
        ), // Position above bottom navigation
        child: Material(
          color: const Color(0xFF34544A),
          borderRadius: BorderRadius.circular(24),
          elevation: 4,
          child: InkWell(
            borderRadius: BorderRadius.circular(24),
            onTap: () {
              Navigator.pushNamed(context, '/liveChat');
            },
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.chat_bubble_outline, color: Colors.white),
                  const SizedBox(width: 8),
                  Text(
                    'Live Chat',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 15.sp,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.endFloat,
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: _onItemTapped,
        type: BottomNavigationBarType.fixed,
        selectedItemColor: const Color(0xFF34544A),
        unselectedItemColor: Colors.grey,
        backgroundColor: Colors.white,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Home'),
          BottomNavigationBarItem(icon: Icon(Icons.credit_card), label: 'Card'),
          BottomNavigationBarItem(
            icon: Icon(Icons.receipt_long),
            label: 'Activity',
          ),
          BottomNavigationBarItem(icon: Icon(Icons.menu), label: 'More'),
        ],
      ),
    );
  }
}

// HomeScreen will be implemented below
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});
  @override
  Widget build(BuildContext context) {
    // Placeholder for now, will implement UI in next step
    return HomeDashboard();
  }
}

// HomeDashboard with real-time data
class HomeDashboard extends ConsumerStatefulWidget {
  @override
  ConsumerState<HomeDashboard> createState() => _HomeDashboardState();
}

class _HomeDashboardState extends ConsumerState<HomeDashboard>
    with WidgetsBindingObserver {
  bool _isBalanceVisible = true; // Track balance visibility state

  // Money can arrive from OTHER people (incoming transfers, deposits) with no
  // action on this device, so nothing local triggers a refresh. To keep the
  // balance current we poll while the Home screen is visible, refresh whenever
  // the app returns to the foreground, and refresh once on entry.
  Timer? _balancePollTimer;
  static const Duration _pollInterval = Duration(seconds: 20);
  bool _isSilentlyRefreshing = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final authState = ref.read(authNotifierProvider);
      if (authState.user == null) {
        print('🔐 HomeScreen: No user data, refreshing...');
        ref.read(authNotifierProvider.notifier).getUserInfo();
      } else {
        // We already have the user, but it may be stale (e.g. someone sent
        // money since we last looked) — pull fresh figures on entry.
        _silentRefresh();
      }
    });
    _startPolling();
  }

  @override
  void dispose() {
    _balancePollTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      // Back in the foreground — the balance may have changed while away.
      _silentRefresh();
      _startPolling();
    } else if (state == AppLifecycleState.paused) {
      _balancePollTimer?.cancel();
    }
  }

  void _startPolling() {
    _balancePollTimer?.cancel();
    _balancePollTimer = Timer.periodic(_pollInterval, (_) => _silentRefresh());
  }

  /// Pull fresh balances in the background without spinners. Skips if a refresh
  /// is already in flight so overlapping ticks don't stack up.
  Future<void> _silentRefresh() async {
    if (_isSilentlyRefreshing || !mounted) return;
    _isSilentlyRefreshing = true;
    try {
      await ref.read(authNotifierProvider.notifier).refreshBalances();
    } catch (_) {
      // Never surface a background refresh failure to the user.
    } finally {
      _isSilentlyRefreshing = false;
    }
  }

  // Toggle balance visibility
  void _toggleBalanceVisibility() {
    HapticFeedback.lightImpact(); // Add haptic feedback
    setState(() {
      _isBalanceVisible = !_isBalanceVisible;
    });
  }

  // ── KYC gate ────────────────────────────────────────────────────────────────
  // Every service below (Data, Airtime, Foreign currency, ATM cards, Recharge
  // card, Cable, Bill payment, POS) moves or manages money, so it must not be
  // reachable until the user's wallet KYC has been approved
  // (`hasVerifyWallet == '1'`). If they aren't verified yet, we show a short
  // message and send them to the KYC page instead of opening the feature.
  void _guardVerified(BuildContext context, VoidCallback onVerified) {
    final user = ref.read(authNotifierProvider).user;
    if (user?.isWalletVerified == true) {
      onVerified();
      return;
    }

    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        const SnackBar(
          content: Text(
            'Please complete your KYC verification to use this feature.',
          ),
          backgroundColor: Color(0xFF34544A),
          behavior: SnackBarBehavior.floating,
        ),
      );
    Navigator.pushNamed(context, '/kyc');
  }

  void _showSendOptions(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => Padding(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 20),
            const Text('Send Money',
                style: TextStyle(
                    fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 4),
            Text('Choose transfer type',
                style: TextStyle(fontSize: 14, color: Colors.grey[600])),
            const SizedBox(height: 24),

            // Bank Transfer
            _SendOption(
              icon: Icons.account_balance_outlined,
              title: 'Bank Transfer',
              subtitle: 'Send to any Nigerian bank account',
              onTap: () {
                Navigator.pop(context);
                Navigator.push(context,
                    MaterialPageRoute(builder: (_) => const BankTransferScreen()));
              },
            ),
            const SizedBox(height: 16),


            // P2P Transfer
            _SendOption(
              icon: Icons.people_outline,
              title: 'Dupay to Dupay Transfer',
              subtitle: 'Send to another Dupay user instantly',
              onTap: () {
                Navigator.pop(context);
                Navigator.push(context,
                    MaterialPageRoute(builder: (_) => const P2PScreen()));
              },
            ),
            const SizedBox(height: 12),

            

          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authNotifierProvider);
    final user = authState.user;
    final isLoading = authState.isLoading;

    String formatBalance(String? balance) => formatCurrency(balance);

    // Get user display name
    String getUserDisplayName() {
      if (user?.profile?.firstName != null && user?.profile?.lastName != null) {
        return '${user!.profile!.firstName}';
      } else if (user?.profile?.username != null) {
        return user!.profile!.username!;
      } else if (user?.email != null) {
        return user!.email!.split('@')[0];
      }
      return 'User';
    }

    return RefreshIndicator(
      color: const Color(0xFF34544A),
      onRefresh: () async {
        await ref.read(authNotifierProvider.notifier).refreshUser();
        if (!context.mounted) return;
       
      },
      child: Container(
        color: Colors.white,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Column(
            children: [
              // Curved green header
              Container(
                width: double.infinity,
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [Color(0xFF34544A), Color(0xFF7CA183)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                  borderRadius: BorderRadius.only(
                    bottomLeft: Radius.circular(40),
                    bottomRight: Radius.circular(40),
                  ),
                ),
                padding: EdgeInsets.only(
                  top: 70.h,
                  left: 20,
                  right: 20,
                  bottom: 24,
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        InkWell(
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => const EditProfileScreen(),
                              ),
                            );
                          },
                          child: CircleAvatar(
                            radius: 26,
                            backgroundColor: Colors.white,
                            child:
                                user?.profile?.profileImg != null
                                    ? ClipOval(
                                      child: Image.network(
                                        user!.profile!.profileImg!,
                                        width: 52,
                                        height: 52,
                                        fit: BoxFit.cover,
                                        errorBuilder: (
                                          context,
                                          error,
                                          stackTrace,
                                        ) {
                                          return const Icon(
                                            Icons.person,
                                            size: 36,
                                            color: Color(0xFF34544A),
                                          );
                                        },
                                      ),
                                    )
                                    : const Icon(
                                      Icons.person,
                                      size: 36,
                                      color: Color(0xFF34544A),
                                    ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Hello ${getUserDisplayName()}',
                              style: TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.bold,
                                fontSize: 16.sp,
                              ),
                            ),
                            Text(
                              'Welcome Back!',
                              style: TextStyle(
                                color: const Color(0xFFB6E09C),
                                fontSize: 13.sp,
                              ),
                            ),
                          ],
                        ),
                        const Spacer(),
                        Row(
                          children: [
                            Container(
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.15),
                                shape: BoxShape.circle,
                              ),
                              child: IconButton(
                                icon: const Icon(
                                  Icons.refresh,
                                  color: Colors.white,
                                ),
                                onPressed: () async {
                                  await ref
                                      .read(authNotifierProvider.notifier)
                                      .refreshUser();
                                  if (!context.mounted) return;
                                 
                                },
                              ),
                            ),
                            const SizedBox(width: 8),
                          
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Center(
                      child: Column(
                        children: [
                          Text(
                            'Available Balance',
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.6),
                              fontSize: 24.sp,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              AnimatedSwitcher(
                                duration: const Duration(milliseconds: 300),
                                child: Text(
                                  _isBalanceVisible
                                      ? formatBalance(user?.balance)
                                      : '₦****',
                                  style: TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 24.sp,
                                  ),
                                  key: ValueKey(_isBalanceVisible),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Tooltip(
                                message:
                                    _isBalanceVisible
                                        ? 'Hide balance'
                                        : 'Show balance',
                                child: GestureDetector(
                                  onTap: _toggleBalanceVisibility,
                                  child: AnimatedSwitcher(
                                    duration: const Duration(milliseconds: 200),
                                    child: Icon(
                                      _isBalanceVisible
                                          ? Icons.visibility_outlined
                                          : Icons.visibility_off_outlined,
                                      color: const Color(0xFF1CC6B7),
                                      size: 24,
                                      key: ValueKey(_isBalanceVisible),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 10),
                    const Divider(height: 1, color: Colors.grey),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                      children: [
                        ActionButton(
                          icon: Icons.send,
                          label: 'Send',
                          onTap: () => _guardVerified(
                              context, () => _showSendOptions(context)),
                        ),
                        ActionButton(
                          icon: Icons.account_balance_wallet,
                          label: 'Fund Wallet',
                          onTap: () async {
                            final fwState = ref.read(fundWalletNotifierProvider);
                            // Load account if not yet loaded
                            if (!fwState.hasAccount && fwState.virtualAccount == null) {
                              await ref.read(fundWalletNotifierProvider.notifier).loadVirtualAccount();
                            }
                            final updated = ref.read(fundWalletNotifierProvider);

                            // Treat "no account" as either: no account at all,
                            // or an account record with no/empty account number.
                            final account = updated.virtualAccount;
                            final hasAccountNumber = updated.hasAccount &&
                                account != null &&
                                account.accountNumber != null &&
                                account.accountNumber!.isNotEmpty;

                            if (!context.mounted) return;

                            if (hasAccountNumber) {
                              Navigator.pushNamed(context, '/fundwallet');
                            } else {
                              // No account number for this user — send to KYC.
                              Navigator.pushNamed(context, '/kyc');
                            }
                          },
                        ),
                        ActionButton(
                          icon: Icons.qr_code,
                          label: 'QR Pay',
                          onTap: () {
                            Navigator.pushNamed(context, '/scanqr');
                          },
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              // Bonus Card with real-time data
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    vertical: 12,
                    horizontal: 16,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF34544A),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.card_giftcard, color: Colors.white),
                      const SizedBox(width: 8),
                      AnimatedSwitcher(
                        duration: const Duration(milliseconds: 300),
                        child: Text(
                          'Bonus: ${_isBalanceVisible ? formatBalance(user?.bonus) : '₦****'}',
                          style: TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w600,
                            fontSize: 15.sp,
                          ),
                          key: ValueKey(_isBalanceVisible),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Services Grid - Now uses shrinkWrap instead of Expanded
              Container(
                color: const Color(0xFFF6F8F7),
                padding: const EdgeInsets.only(
                  left: 16,
                  right: 16,
                  bottom: 24,
                  top: 16,
                ),
                child: GridView.count(
                  padding: EdgeInsets.zero,
                  shrinkWrap:
                      true, // This allows the GridView to size itself based on content
                  physics:
                      const NeverScrollableScrollPhysics(), // Disable GridView scrolling since parent is scrollable
                  crossAxisCount: 3,
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  children: [
                    ServiceCard(
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/buydata');
                      }),
                      icon: Icons.wifi_tethering_error_rounded_outlined,
                      label: 'Data',
                    ),
                    ServiceCard(
                      icon: Icons.phone_iphone_outlined,
                      label: 'Airtime',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/airtime');
                      }),
                    ),
                    ServiceCard(
                      icon: Icons.confirmation_number_outlined,
                      label: 'Foreign currency',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/foreignCurrency');
                      }),
                    ),
                    ServiceCard(
                      icon: Icons.atm_rounded,
                      label: 'ATM Card',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/atmCardScreen');
                      }),
                    ),
                    ServiceCard(
                      icon: Icons.point_of_sale_outlined,
                      label: 'Order ATM Card',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/atmrequest');
                      }),
                    ),
                    ServiceCard(
                      icon: Icons.atm_rounded,
                      label: 'Recharge Card',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/rechargeCard');
                      }),
                    ),
                    ServiceCard(
                      icon: Icons.tv_rounded,
                      label: 'Cable Subscription ',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/cablesub');
                      }),
                    ),


                    ServiceCard(
                      icon: Icons.receipt_outlined,
                      label: 'Bill Payment',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/billpayment');
                      }),
                    ),



                       ServiceCard(
                      icon: Icons.point_of_sale_outlined,
                      label: 'POS',
                      onTap: () => _guardVerified(context, () {
                        Navigator.pushNamed(context, '/posrequest');
                      }),
                    ),
                 
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ─── Send Option Tile ─────────────────────────────────────────────────────────

class _SendOption extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback onTap;

  const _SendOption({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAF9),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.grey[200]!),
        ),
        child: Row(children: [
          Container(
            width: 48, height: 48,
            decoration: BoxDecoration(
              color: const Color(0xFF34544A).withOpacity(0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: const Color(0xFF34544A), size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 15)),
                const SizedBox(height: 2),
                Text(subtitle,
                    style: TextStyle(
                        fontSize: 13, color: Colors.grey[600])),
              ],
            ),
          ),
          Icon(Icons.chevron_right, color: Colors.grey[400]),
        ]),
      ),
    );
  }
}