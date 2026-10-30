import 'package:dupay/providers/physical_card_provider.dart';
import 'package:dupay/screen/atm_request/widgets/card_widget.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:flutter_test/flutter_test.dart';

Widget _wrap(PhysicalCardModel card) => ScreenUtilInit(
      designSize: const Size(428, 926),
      builder: (_, __) => MaterialApp(
        home: Scaffold(body: SingleChildScrollView(child: CardWidget(card: card))),
      ),
    );

void main() {
  testWidgets('an unassigned card never reads "Active" and hides the limit',
      (tester) async {
    // What the backend returns straight after a card is ordered: a record with
    // status "active" but no PAN. AtmCardScreen now routes this state to
    // _AwaitingCardView so the card is never drawn at all, but the widget must
    // still be correct wherever else it is used.
    await tester.pumpWidget(_wrap(const PhysicalCardModel()));
    await tester.pumpAndSettle();

    expect(find.text('Not Assigned'), findsOneWidget);
    expect(find.text('Active'), findsNothing);
    expect(find.text('Card Daily Limit'), findsNothing);
    expect(find.text('₦500,000.00'), findsNothing);
  });

  testWidgets('an assigned card reads "Active" and shows the limit',
      (tester) async {
    await tester.pumpWidget(_wrap(const PhysicalCardModel(
      maskedPan: '506136******1531',
      cardPan: '121531',
    )));
    await tester.pumpAndSettle();

    expect(find.text('Active'), findsOneWidget);
    expect(find.text('Not Assigned'), findsNothing);
    expect(find.text('Card Daily Limit'), findsOneWidget);
    expect(find.text('₦500,000.00'), findsOneWidget);
    expect(find.text('506136******1531'), findsOneWidget);
  });

  testWidgets('a frozen assigned card reads "Inactive"', (tester) async {
    await tester.pumpWidget(_wrap(const PhysicalCardModel(
      maskedPan: '506136******1531',
      status: 'inactive',
    )));
    await tester.pumpAndSettle();

    expect(find.text('Inactive'), findsOneWidget);
  });

  testWidgets('a blocked card reads "Blocked"', (tester) async {
    await tester.pumpWidget(_wrap(const PhysicalCardModel(
      maskedPan: '506136******1531',
      blocked: true,
    )));
    await tester.pumpAndSettle();

    expect(find.text('Blocked'), findsOneWidget);
  });
}
