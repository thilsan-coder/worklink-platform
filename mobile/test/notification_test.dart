import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/notifications/models/notification_model.dart';
import 'package:mobile/features/notifications/providers/notification_provider.dart';

void main() {
  group('NotificationModel Tests', () {
    test('NotificationModel.fromJson parses complete payload correctly', () {
      final json = {
        'id': 101,
        'user_id': 5,
        'type': 'JOB_REQUESTED',
        'title': 'New Service Request',
        'message': 'Customer requested electrical repair',
        'reference_id': 42,
        'related_type': 'job',
        'related_id': 42,
        'data': {
          'entity_type': 'job',
          'entity_id': 42,
          'customer_id': 1,
        },
        'is_read': false,
        'created_at': DateTime.now().subtract(const Duration(minutes: 5)).toIso8601String(),
      };

      final notif = NotificationModel.fromJson(json);

      expect(notif.id, 101);
      expect(notif.userId, 5);
      expect(notif.type, 'JOB_REQUESTED');
      expect(notif.title, 'New Service Request');
      expect(notif.message, 'Customer requested electrical repair');
      expect(notif.referenceId, 42);
      expect(notif.relatedType, 'job');
      expect(notif.relatedId, 42);
      expect(notif.isRead, isFalse);
      expect(notif.isJob, isTrue);
      expect(notif.timeAgo, '5m ago');
    });

    test('NotificationModel correctly resolves Chat and Review types', () {
      final chatNotif = NotificationModel.fromJson({
        'id': 102,
        'user_id': 5,
        'type': 'NEW_MESSAGE',
        'title': 'New Message',
        'message': 'Hello there',
        'reference_id': 7,
        'data': {
          'entity_type': 'chat',
          'entity_id': 7,
          'sender_id': 2,
        },
        'is_read': true,
      });

      expect(chatNotif.isChat, isTrue);
      expect(chatNotif.relatedType, 'chat');
      expect(chatNotif.relatedId, 7);

      final reviewNotif = NotificationModel.fromJson({
        'id': 103,
        'user_id': 5,
        'type': 'NEW_REVIEW',
        'title': 'New Review',
        'message': '5-star review received',
        'reference_id': 12,
        'data': {
          'entity_type': 'review',
          'entity_id': 12,
        },
        'is_read': false,
      });

      expect(reviewNotif.isReview, isTrue);
      expect(reviewNotif.relatedType, 'review');
      expect(reviewNotif.relatedId, 12);
    });
  });

  group('NotificationProvider Tests', () {
    test('initial state is correct', () {
      final provider = NotificationProvider();
      expect(provider.notifications, isEmpty);
      expect(provider.unreadCount, 0);
      expect(provider.isLoading, isFalse);
      expect(provider.errorMessage, isNull);
      provider.dispose();
    });
  });
}
