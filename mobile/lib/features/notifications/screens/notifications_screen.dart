import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../chat/screens/chat_screen.dart';
import '../../jobs/screens/job_details_screen.dart';
import '../../profile/screens/public_worker_profile_screen.dart';
import '../models/notification_model.dart';
import '../providers/notification_provider.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    final provider = context.read<NotificationProvider>();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      provider.fetchNotifications(refresh: true);
    });

    _scrollController.addListener(() {
      if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
        provider.loadMore();
      }
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _handleNotificationTap(NotificationModel notif) {
    final provider = context.read<NotificationProvider>();
    if (!notif.isRead) {
      provider.markAsRead(notif);
    }

    final entityType = notif.relatedType?.toLowerCase();
    final entityId = notif.relatedId;

    if (entityType == 'job' && entityId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => JobDetailsScreen(jobId: entityId),
        ),
      );
    } else if (entityType == 'chat' && entityId != null) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ChatScreen(conversationId: entityId),
        ),
      );
    } else if (entityType == 'review') {
      final jobId = notif.data?['job_id'];
      final workerId = notif.data?['worker_id'];
      if (jobId != null) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => JobDetailsScreen(jobId: int.tryParse(jobId.toString()) ?? 0),
          ),
        );
      } else if (workerId != null) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => PublicWorkerProfileScreen(workerProfileId: int.tryParse(workerId.toString()) ?? 0),
          ),
        );
      }
    } else if (notif.referenceId != null) {
      // Fallback
      if (notif.isJob) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => JobDetailsScreen(jobId: notif.referenceId!),
          ),
        );
      } else if (notif.isChat) {
        Navigator.of(context).push(
          MaterialPageRoute(
            builder: (_) => ChatScreen(conversationId: notif.referenceId!),
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<NotificationProvider>();
    final notifications = provider.notifications;
    final unreadCount = provider.unreadCount;

    return Scaffold(
      appBar: AppBar(
        title: Row(
          children: [
            const Text('Notifications'),
            if (unreadCount > 0) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  unreadCount > 99 ? '99+' : unreadCount.toString(),
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ],
        ),
        actions: [
          if (notifications.isNotEmpty)
            PopupMenuButton<String>(
              icon: const Icon(Icons.more_vert_rounded),
              onSelected: (value) async {
                if (value == 'mark_all_read') {
                  await provider.markAllAsRead();
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('All notifications marked as read')),
                    );
                  }
                } else if (value == 'clear_all') {
                  final confirm = await showDialog<bool>(
                    context: context,
                    builder: (ctx) => AlertDialog(
                      title: const Text('Clear All Notifications?'),
                      content: const Text('This will delete all your notifications. This action cannot be undone.'),
                      actions: [
                        TextButton(
                          onPressed: () => Navigator.of(ctx).pop(false),
                          child: const Text('Cancel'),
                        ),
                        FilledButton(
                          style: FilledButton.styleFrom(backgroundColor: AppColors.error),
                          onPressed: () => Navigator.of(ctx).pop(true),
                          child: const Text('Clear All'),
                        ),
                      ],
                    ),
                  );
                  if (confirm == true) {
                    await provider.clearAll();
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('All notifications cleared')),
                      );
                    }
                  }
                }
              },
              itemBuilder: (context) => [
                if (unreadCount > 0)
                  const PopupMenuItem(
                    value: 'mark_all_read',
                    child: Row(
                      children: [
                        Icon(Icons.done_all_rounded, size: 18, color: AppColors.primary),
                        SizedBox(width: 8),
                        Text('Mark all as read'),
                      ],
                    ),
                  ),
                const PopupMenuItem(
                  value: 'clear_all',
                  child: Row(
                    children: [
                      Icon(Icons.delete_sweep_rounded, size: 18, color: AppColors.error),
                      SizedBox(width: 8),
                      Text('Clear all notifications'),
                    ],
                  ),
                ),
              ],
            ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () => provider.fetchNotifications(refresh: true),
        child: _buildBody(provider, notifications),
      ),
    );
  }

  Widget _buildBody(NotificationProvider provider, List<NotificationModel> notifications) {
    if (provider.isLoading && notifications.isEmpty) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (provider.errorMessage != null && notifications.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
              const SizedBox(height: 12),
              Text(
                provider.errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: () => provider.fetchNotifications(refresh: true),
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (notifications.isEmpty) {
      return ListView(
        children: [
          SizedBox(height: MediaQuery.of(context).size.height * 0.25),
          Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(20),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.notifications_none_rounded,
                    size: 56,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'No Notifications Yet',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 6),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 40),
                  child: Text(
                    'You will receive updates about jobs, messages, and reviews here.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: AppColors.textSecondary, fontSize: 14),
                  ),
                ),
              ],
            ),
          ),
        ],
      );
    }

    return ListView.separated(
      controller: _scrollController,
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(vertical: 8),
      itemCount: notifications.length + (provider.isLoadingMore ? 1 : 0),
      separatorBuilder: (context, index) => const Divider(height: 1, indent: 72),
      itemBuilder: (context, index) {
        if (index == notifications.length) {
          return const Padding(
            padding: EdgeInsets.all(16),
            child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
          );
        }

        final notif = notifications[index];
        return Dismissible(
          key: ValueKey('notif_${notif.id}'),
          direction: DismissDirection.endToStart,
          background: Container(
            color: AppColors.error,
            alignment: Alignment.centerRight,
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: const Icon(Icons.delete_rounded, color: Colors.white),
          ),
          onDismissed: (_) {
            provider.deleteNotification(notif);
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: const Text('Notification deleted'),
                action: SnackBarAction(
                  label: 'Dismiss',
                  onPressed: () {},
                ),
              ),
            );
          },
          child: Container(
            color: notif.isRead ? Colors.transparent : AppColors.primary.withAlpha(12),
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              leading: Stack(
                children: [
                  CircleAvatar(
                    radius: 22,
                    backgroundColor: notif.iconColor.withAlpha(30),
                    child: Icon(
                      notif.icon,
                      color: notif.iconColor,
                      size: 22,
                    ),
                  ),
                  if (!notif.isRead)
                    Positioned(
                      top: 0,
                      right: 0,
                      child: Container(
                        width: 10,
                        height: 10,
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 1.5),
                        ),
                      ),
                    ),
                ],
              ),
              title: Row(
                children: [
                  Expanded(
                    child: Text(
                      notif.title,
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: notif.isRead ? FontWeight.w500 : FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    notif.timeAgo,
                    style: TextStyle(
                      fontSize: 12,
                      color: notif.isRead ? AppColors.textMuted : AppColors.primary,
                      fontWeight: notif.isRead ? FontWeight.normal : FontWeight.w600,
                    ),
                  ),
                ],
              ),
              subtitle: Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  notif.message,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: 13,
                    color: notif.isRead ? AppColors.textSecondary : AppColors.textPrimary,
                  ),
                ),
              ),
              trailing: PopupMenuButton<String>(
                icon: const Icon(Icons.more_horiz_rounded, size: 20, color: AppColors.textMuted),
                padding: EdgeInsets.zero,
                onSelected: (action) {
                  if (action == 'toggle_read') {
                    provider.markAsRead(notif);
                  } else if (action == 'delete') {
                    provider.deleteNotification(notif);
                  }
                },
                itemBuilder: (context) => [
                  if (!notif.isRead)
                    const PopupMenuItem(
                      value: 'toggle_read',
                      child: Text('Mark as read'),
                    ),
                  const PopupMenuItem(
                    value: 'delete',
                    child: Text('Delete', style: TextStyle(color: AppColors.error)),
                  ),
                ],
              ),
              onTap: () => _handleNotificationTap(notif),
            ),
          ),
        );
      },
    );
  }
}
