import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../jobs/providers/job_provider.dart';
import '../../jobs/screens/job_details_screen.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<JobProvider>().fetchNotifications();
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<JobProvider>();
    final notifications = provider.notifications;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
      ),
      body: RefreshIndicator(
        onRefresh: () => provider.fetchNotifications(),
        child: notifications.isEmpty
            ? Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: const [
                    Icon(Icons.notifications_off_outlined, size: 56, color: AppColors.textMuted),
                    SizedBox(height: 12),
                    Text(
                      'No Notifications Yet',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'Job status updates will appear here.',
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                  ],
                ),
              )
            : ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: notifications.length,
                itemBuilder: (context, index) {
                  final notif = notifications[index];
                  final bool isRead = notif['is_read'] ?? false;
                  final int? referenceId = notif['reference_id'];

                  return Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: ListTile(
                      leading: CircleAvatar(
                        backgroundColor: isRead ? AppColors.surface : AppColors.primary.withAlpha(20),
                        child: Icon(
                          Icons.notifications_active_rounded,
                          color: isRead ? AppColors.textMuted : AppColors.primary,
                        ),
                      ),
                      title: Text(
                        notif['title'] ?? 'Notification',
                        style: TextStyle(
                          fontWeight: isRead ? FontWeight.normal : FontWeight.bold,
                        ),
                      ),
                      subtitle: Text(notif['body'] ?? ''),
                      trailing: notif['created_at'] != null
                          ? Text(
                              notif['created_at'].toString().substring(0, 10),
                              style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                            )
                          : null,
                      onTap: () {
                        if (referenceId != null) {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => JobDetailsScreen(jobId: referenceId),
                            ),
                          );
                        }
                      },
                    ),
                  );
                },
              ),
      ),
    );
  }
}
