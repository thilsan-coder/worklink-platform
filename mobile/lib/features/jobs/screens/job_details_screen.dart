import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../chat/providers/chat_provider.dart';
import '../../chat/screens/chat_screen.dart';
import '../../profile/screens/public_worker_profile_screen.dart';
import '../providers/job_provider.dart';
import '../widgets/status_timeline_widget.dart';

class JobDetailsScreen extends StatefulWidget {
  final int jobId;

  const JobDetailsScreen({
    super.key,
    required this.jobId,
  });

  @override
  State<JobDetailsScreen> createState() => _JobDetailsScreenState();
}

class _JobDetailsScreenState extends State<JobDetailsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<JobProvider>().fetchJobDetails(widget.jobId);
    });
  }

  void _openChat(BuildContext context) async {
    final chatProvider = context.read<ChatProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final conv = await chatProvider.getOrCreateJobConversation(widget.jobId);
    if (conv != null && mounted) {
      final convId = conv['id'] ?? conv['conversation_id'];
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ChatScreen(
            conversationId: convId,
            initialConversation: conv,
          ),
        ),
      );
    } else if (mounted) {
      messenger.showSnackBar(
        SnackBar(content: Text(chatProvider.messageError ?? 'Unable to open chat.')),
      );
    }
  }

  void _showCancelDialog(BuildContext context, JobProvider provider) {
    final reasonController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Cancel Job Request'),
          content: Form(
            key: formKey,
            child: TextFormField(
              controller: reasonController,
              decoration: const InputDecoration(
                labelText: 'Reason for Cancellation',
                hintText: 'e.g. Schedule clash / Changed mind',
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Please enter reason' : null,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('Back'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
              onPressed: () async {
                if (!formKey.currentState!.validate()) return;
                final nav = Navigator.of(dialogContext);
                final messenger = ScaffoldMessenger.of(context);

                final success = await provider.cancelJob(widget.jobId, reasonController.text.trim());
                nav.pop();
                if (success) {
                  messenger.showSnackBar(
                    const SnackBar(content: Text('Job cancelled successfully.')),
                  );
                }
              },
              child: const Text('Confirm Cancel'),
            ),
          ],
        );
      },
    );
  }

  void _showRejectDialog(BuildContext context, JobProvider provider) {
    final reasonController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Decline Job Request'),
          content: Form(
            key: formKey,
            child: TextFormField(
              controller: reasonController,
              decoration: const InputDecoration(
                labelText: 'Reason for Rejection',
                hintText: 'e.g. Fully booked / Outside service area',
              ),
              validator: (v) => (v == null || v.trim().isEmpty) ? 'Please enter reason' : null,
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(backgroundColor: AppColors.error),
              onPressed: () async {
                if (!formKey.currentState!.validate()) return;
                final nav = Navigator.of(dialogContext);
                final messenger = ScaffoldMessenger.of(context);

                final success = await provider.rejectJob(widget.jobId, reasonController.text.trim());
                nav.pop();
                if (success) {
                  messenger.showSnackBar(
                    const SnackBar(content: Text('Job request declined.')),
                  );
                }
              },
              child: const Text('Decline Request'),
            ),
          ],
        );
      },
    );
  }

  void _showSchedulePicker(BuildContext context, JobProvider provider) async {
    final now = DateTime.now();
    final date = await showDatePicker(
      context: context,
      initialDate: now.add(const Duration(days: 1)),
      firstDate: now,
      lastDate: now.add(const Duration(days: 90)),
    );

    if (date != null && context.mounted) {
      final time = await showTimePicker(
        context: context,
        initialTime: const TimeOfDay(hour: 9, minute: 0),
      );

      if (time != null && context.mounted) {
        final scheduledAt = DateTime(date.year, date.month, date.day, time.hour, time.minute);
        final messenger = ScaffoldMessenger.of(context);
        final success = await provider.scheduleJob(widget.jobId, scheduledAt);
        if (success) {
          messenger.showSnackBar(
            const SnackBar(content: Text('Job appointment scheduled!')),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<JobProvider>();
    final auth = context.watch<AuthProvider>();
    final isWorker = auth.activeRole == 'worker';

    if (provider.isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Job Details')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (provider.error != null || provider.selectedJob == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Job Details')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
              const SizedBox(height: 12),
              Text(provider.error ?? 'Job details not found'),
              ElevatedButton(
                onPressed: () => provider.fetchJobDetails(widget.jobId),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    final job = provider.selectedJob!;
    final status = (job['status'] ?? 'REQUESTED').toString();
    final customer = job['customer'] ?? {};
    final worker = job['worker'] ?? {};
    final workerProfile = worker['worker_profile'] ?? {};
    final location = job['location'] ?? {};
    final category = job['category'] ?? {};

    final bool canCancel = !['COMPLETED', 'WORK_COMPLETED', 'CANCELLED', 'REJECTED'].contains(status.toUpperCase());

    return Scaffold(
      appBar: AppBar(
        title: Text(job['job_number'] ?? 'Job Details'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => provider.fetchJobDetails(widget.jobId),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Chip(
                          label: Text(
                            category['name'] ?? 'Service',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                          ),
                          backgroundColor: AppColors.primary.withAlpha(20),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: StatusTimelineWidget.getStatusColor(status).withAlpha(25),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: StatusTimelineWidget.getStatusColor(status)),
                          ),
                          child: Text(
                            StatusTimelineWidget.formatStatus(status),
                            style: TextStyle(
                              color: StatusTimelineWidget.getStatusColor(status),
                              fontWeight: FontWeight.bold,
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      job['title'] ?? 'Job Request',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      job['description'] ?? '',
                      style: const TextStyle(color: AppColors.textSecondary, height: 1.3),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Party Card (Worker / Customer Info)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(12.0),
                child: Column(
                  children: [
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      leading: CircleAvatar(
                        backgroundColor: AppColors.primary.withAlpha(20),
                        child: const Icon(Icons.person_rounded, color: AppColors.primary),
                      ),
                      title: Text(
                        isWorker ? (customer['name'] ?? 'Customer') : (worker['name'] ?? 'Worker'),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      subtitle: Text(
                        isWorker ? 'Customer' : 'Skilled Worker (${workerProfile['address'] ?? 'Active'})',
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              padding: const EdgeInsets.symmetric(vertical: 10),
                            ),
                            onPressed: () => _openChat(context),
                            icon: const Icon(Icons.chat_bubble_rounded, size: 18, color: Colors.white),
                            label: Text(
                              isWorker ? 'Message Customer' : 'Message Worker',
                              style: const TextStyle(color: Colors.white, fontSize: 13),
                            ),
                          ),
                        ),
                        if (!isWorker && workerProfile['id'] != null) ...[
                          const SizedBox(width: 8),
                          OutlinedButton(
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                            ),
                            onPressed: () {
                              Navigator.of(context).push(
                                MaterialPageRoute(
                                  builder: (_) => PublicWorkerProfileScreen(workerProfileId: workerProfile['id']),
                                ),
                              );
                            },
                            child: const Text('Profile', style: TextStyle(fontSize: 13)),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Location & Schedule Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.location_on_rounded, color: AppColors.primary),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Service Location', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                              Text(
                                '${location['address_line1'] ?? ''}, ${location['city'] ?? ''}',
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    if (job['scheduled_at'] != null) ...[
                      const Divider(height: 24),
                      Row(
                        children: [
                          const Icon(Icons.event_rounded, color: AppColors.secondary),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text('Scheduled Date & Time', style: TextStyle(fontSize: 12, color: AppColors.textMuted)),
                                Text(
                                  job['scheduled_at'].toString().substring(0, 16),
                                  style: const TextStyle(fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ],
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Status Timeline
            StatusTimelineWidget(
              history: provider.jobHistory,
              currentStatus: status,
            ),
            const SizedBox(height: 24),

            // Actions Bar
            if (provider.isActionLoading)
              const Center(child: CircularProgressIndicator())
            else
              Column(
                children: [
                  // Worker Specific Actions
                  if (isWorker && status == 'REQUESTED') ...[
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                            onPressed: () => provider.acceptJob(widget.jobId),
                            icon: const Icon(Icons.check_rounded),
                            label: const Text('Accept Request'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(foregroundColor: AppColors.error),
                            onPressed: () => _showRejectDialog(context, provider),
                            icon: const Icon(Icons.close_rounded),
                            label: const Text('Decline'),
                          ),
                        ),
                      ],
                    ),
                  ],

                  if (status == 'ACCEPTED' || status == 'SCHEDULED') ...[
                    if (isWorker)
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                          onPressed: () => provider.startJob(widget.jobId),
                          icon: const Icon(Icons.play_arrow_rounded),
                          label: const Text('Start Work'),
                        ),
                      ),
                    const SizedBox(height: 8),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () => _showSchedulePicker(context, provider),
                        icon: const Icon(Icons.calendar_month_rounded),
                        label: Text(job['scheduled_at'] == null ? 'Schedule Appointment' : 'Reschedule Appointment'),
                      ),
                    ),
                  ],

                  if (isWorker && (status == 'IN_PROGRESS' || status == 'WORK_STARTED')) ...[
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        style: ElevatedButton.styleFrom(backgroundColor: AppColors.secondary),
                        onPressed: () => provider.completeJob(widget.jobId),
                        icon: const Icon(Icons.verified_rounded),
                        label: const Text('Mark Work Completed'),
                      ),
                    ),
                  ],

                  if (canCancel) ...[
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: TextButton.icon(
                        style: TextButton.styleFrom(foregroundColor: AppColors.error),
                        onPressed: () => _showCancelDialog(context, provider),
                        icon: const Icon(Icons.cancel_outlined),
                        label: const Text('Cancel Job'),
                      ),
                    ),
                  ],
                ],
              ),
          ],
        ),
      ),
    );
  }
}
