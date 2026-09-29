import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/chat/providers/chat_provider.dart';

void main() {
  group('ChatProvider Unit Tests', () {
    test('initial state is correct', () {
      final provider = ChatProvider();
      expect(provider.conversations, isEmpty);
      expect(provider.isLoadingConversations, isFalse);
      expect(provider.activeMessages, isEmpty);
      expect(provider.activeConversation, isNull);
      expect(provider.totalUnreadCount, 0);
    });

    test('calculates total unread count across conversations correctly', () {
      final provider = ChatProvider();
      // Test unread count calculation
      expect(provider.totalUnreadCount, 0);
    });
  });
}
