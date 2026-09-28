import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/main.dart';

void main() {
  testWidgets('WorkLink app smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const WorkLinkApp());
    expect(find.byType(WorkLinkApp), findsOneWidget);
  });
}
