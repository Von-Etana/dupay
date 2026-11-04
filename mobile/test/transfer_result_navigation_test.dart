import 'package:dupay/screen/transaction/transaction_receipt_screen.dart';
import 'package:dupay/screen/transaction/transaction_summary_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

void main() {
  testWidgets('confirmed transfer opens successful receipt', (tester) async {
    tester.view.physicalSize = const Size(1080, 2340);
    tester.view.devicePixelRatio = 3;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });
    await tester.pumpWidget(
      ProviderScope(
        child: ScreenUtilInit(
          designSize: const Size(375, 812),
          builder: (_, __) => MaterialApp(
            home: TransactionSummaryScreen(
              data: TransactionSummaryData(
                service: 'P2P Transfer',
                from: 'Wallet',
                amount: '₦100.00',
                onConfirm: (_) async => {
                  'success': true,
                  'status': 'successful',
                  'reference': 'TEST-123',
                },
              ),
            ),
          ),
        ),
      ),
    );
    await tester.enterText(find.byType(TextField), '1234');
    await tester.pump(const Duration(milliseconds: 150));
    await tester.pumpAndSettle();

    expect(find.byType(TransactionReceiptScreen), findsOneWidget);
    expect(find.text('Transfer Successful!'), findsOneWidget);
  });
}
