import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';

class NotificationModel {
  final int id;
  final int userId;
  final String type;
  final String title;
  final String message;
  final String? body;
  final int? referenceId;
  final String? relatedType;
  final int? relatedId;
  final Map<String, dynamic>? data;
  bool isRead;
  final DateTime? readAt;
  final DateTime createdAt;
  final DateTime? updatedAt;

  NotificationModel({
    required this.id,
    required this.userId,
    required this.type,
    required this.title,
    required this.message,
    this.body,
    this.referenceId,
    this.relatedType,
    this.relatedId,
    this.data,
    required this.isRead,
    this.readAt,
    required this.createdAt,
    this.updatedAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    DateTime parsedCreatedAt;
    try {
      parsedCreatedAt = json['created_at'] != null
          ? DateTime.parse(json['created_at'].toString())
          : DateTime.now();
    } catch (_) {
      parsedCreatedAt = DateTime.now();
    }

    DateTime? parsedReadAt;
    if (json['read_at'] != null) {
      try {
        parsedReadAt = DateTime.parse(json['read_at'].toString());
      } catch (_) {
        parsedReadAt = null;
      }
    }

    // Resolve related type and id
    final dataMap = json['data'] is Map<String, dynamic> ? json['data'] as Map<String, dynamic> : null;
    String? relType = json['related_type']?.toString() ?? dataMap?['entity_type']?.toString();
    int? relId;

    if (json['related_id'] != null) {
      relId = int.tryParse(json['related_id'].toString());
    } else if (dataMap?['entity_id'] != null) {
      relId = int.tryParse(dataMap!['entity_id'].toString());
    } else if (json['reference_id'] != null) {
      relId = int.tryParse(json['reference_id'].toString());
    }

    final rawType = json['type']?.toString() ?? 'JOB_STATUS';

    return NotificationModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      userId: json['user_id'] is int ? json['user_id'] : int.tryParse(json['user_id']?.toString() ?? '0') ?? 0,
      type: rawType,
      title: json['title']?.toString() ?? 'Notification',
      message: json['message']?.toString() ?? json['body']?.toString() ?? '',
      body: json['body']?.toString(),
      referenceId: json['reference_id'] != null ? int.tryParse(json['reference_id'].toString()) : null,
      relatedType: relType,
      relatedId: relId,
      data: dataMap,
      isRead: json['is_read'] == true || json['is_read'] == 1 || json['is_read'] == '1',
      readAt: parsedReadAt,
      createdAt: parsedCreatedAt,
      updatedAt: json['updated_at'] != null ? DateTime.tryParse(json['updated_at'].toString()) : null,
    );
  }

  String get timeAgo {
    final now = DateTime.now();
    final difference = now.difference(createdAt);

    if (difference.inMinutes < 1) {
      return 'Just now';
    } else if (difference.inMinutes < 60) {
      return '${difference.inMinutes}m ago';
    } else if (difference.inHours < 24) {
      return '${difference.inHours}h ago';
    } else if (difference.inDays < 7) {
      return '${difference.inDays}d ago';
    } else {
      return '${createdAt.year}-${createdAt.month.toString().padLeft(2, '0')}-${createdAt.day.toString().padLeft(2, '0')}';
    }
  }

  IconData get icon {
    final upper = type.toUpperCase();
    if (upper.contains('REQUEST') || upper == 'JOB_REQUESTED' || upper == 'JOB_REQUEST') {
      return Icons.assignment_late_rounded;
    } else if (upper.contains('ACCEPT') || upper == 'JOB_ACCEPTED') {
      return Icons.check_circle_rounded;
    } else if (upper.contains('REJECT') || upper == 'JOB_REJECTED') {
      return Icons.cancel_rounded;
    } else if (upper.contains('SCHEDULE') || upper == 'JOB_SCHEDULED') {
      return Icons.calendar_month_rounded;
    } else if (upper.contains('START') || upper == 'JOB_STARTED') {
      return Icons.play_circle_fill_rounded;
    } else if (upper.contains('COMPLETE') || upper == 'JOB_COMPLETED') {
      return Icons.task_alt_rounded;
    } else if (upper.contains('CANCEL') || upper == 'JOB_CANCELLED') {
      return Icons.event_busy_rounded;
    } else if (upper.contains('MESSAGE') || upper == 'NEW_MESSAGE' || upper == 'CHAT_MESSAGE') {
      return Icons.chat_bubble_rounded;
    } else if (upper.contains('REVIEW') || upper == 'NEW_REVIEW' || upper == 'REVIEW_RECEIVED') {
      return Icons.star_rounded;
    }
    return Icons.notifications_rounded;
  }

  Color get iconColor {
    final upper = type.toUpperCase();
    if (upper.contains('REQUEST') || upper == 'JOB_REQUESTED') {
      return const Color(0xFF2563EB); // Blue
    } else if (upper.contains('ACCEPT') || upper == 'JOB_ACCEPTED' || upper.contains('COMPLETE') || upper == 'JOB_COMPLETED') {
      return const Color(0xFF10B981); // Emerald Green
    } else if (upper.contains('REJECT') || upper == 'JOB_REJECTED' || upper.contains('CANCEL') || upper == 'JOB_CANCELLED') {
      return const Color(0xFFEF4444); // Red
    } else if (upper.contains('SCHEDULE') || upper == 'JOB_SCHEDULED') {
      return const Color(0xFF8B5CF6); // Purple
    } else if (upper.contains('START') || upper == 'JOB_STARTED') {
      return const Color(0xFFF59E0B); // Amber
    } else if (upper.contains('MESSAGE') || upper == 'NEW_MESSAGE') {
      return AppColors.primary; // Teal/Brand
    } else if (upper.contains('REVIEW') || upper == 'NEW_REVIEW') {
      return const Color(0xFFF59E0B); // Gold
    }
    return AppColors.primary;
  }

  bool get isJob => (relatedType == 'job') || type.toUpperCase().startsWith('JOB_');
  bool get isChat => (relatedType == 'chat') || type.toUpperCase().contains('MESSAGE');
  bool get isReview => (relatedType == 'review') || type.toUpperCase().contains('REVIEW');
}
