import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/job_provider.dart';
import '../widgets/status_timeline_widget.dart';
import 'job_details_screen.dart';

class WorkerJobsScreen extends StatefulWidget {
  const WorkerJobsScreen({super.key});

  @override
  State<WorkerJobsScreen> createState() => _WorkerJobsScreenState();
}

class _WorkerJobsScreenState extends State<WorkerJobsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  final List<Map<String, String>> _statusTabs = [
    {'label': 'Incoming Requests', 'value': 'REQUESTED'},
    {'label': 'Active Jobs', 'value': 'IN_PROGRESS'},
    {'label': 'Scheduled', 'value': 'SCHEDULED'},
    {'label': 'Completed', 'value': 'COMPLETED'},
    {'label': 'Cancelled', 'value': 'CANCELLED'},
  ];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: _statusTabs.length, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        final status = _statusTabs[_tabController.index]['value'];
        context.read<JobProvider>().fetchJobs(status: status);
      }
    });

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<JobProvider>().fetchJobs(status: 'REQUESTED');
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _showRejectDialog(int jobId, JobProvider provider) {
    final reasonController = TextEditingController();
    final formKey = GlobalKey<FormState>();

    showDialog(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Decline Request'),
          content: Form(
            key: formKey,
            child: TextFormField(
              controller: reasonController,
              decoration: const InputDecoration(
                labelText: 'Reason for Rejection',
                hintText: 'e.g. Schedule fully booked',
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

                final success = await provider.rejectJob(jobId, reasonController.text.trim());
                nav.pop();
                if (success) {
                  messenger.showSnackBar(
                    const SnackBar(content: Text('Job request declined.')),
                  );
                }
              },
              child: const Text('Decline'),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<JobProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Worker Job Dashboard'),
        backgroundColor: AppColors.secondary,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          indicatorColor: Colors.white,
          tabs: _statusTabs.map((t) => Tab(text: t['label'])).toList(),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => provider.fetchJobs(status: provider.statusFilter),
        child: _buildBody(provider),
      ),
    );
  }

  Widget _buildBody(JobProvider provider) {
    if (provider.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (provider.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
            const SizedBox(height: 12),
            Text(provider.error!, style: const TextStyle(color: AppColors.textSecondary)),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => provider.fetchJobs(status: provider.statusFilter),
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    if (provider.jobs.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.task_rounded, size: 56, color: AppColors.textMuted),
            const SizedBox(height: 12),
            const Text(
              'No Jobs in this Section',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            const Text(
              'When customers request jobs, they will appear here.',
              style: TextStyle(color: AppColors.textSecondary),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16.0),
      itemCount: provider.jobs.length,
      itemBuilder: (context, index) {
        final job = provider.jobs[index];
        final status = (job['status'] ?? 'REQUESTED').toString();
        final customer = job['customer'] ?? {};
        final category = job['category'] ?? {};
        final location = job['location'] ?? {};

        return Card(
          margin: const EdgeInsets.only(bottom: 14),
          elevation: 2,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () {
              Navigator.of(context).push(
                MaterialPageRoute(
                  builder: (_) => JobDetailsScreen(jobId: job['id']),
                ),
              );
            },
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        job['job_number'] ?? '',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textMuted),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: StatusTimelineWidget.getStatusColor(status).withAlpha(20),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: StatusTimelineWidget.getStatusColor(status)),
                        ),
                        child: Text(
                          StatusTimelineWidget.formatStatus(status),
                          style: TextStyle(
                            color: StatusTimelineWidget.getStatusColor(status),
                            fontWeight: FontWeight.bold,
                            fontSize: 11,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  Row(
                    children: [
                      CircleAvatar(
                        radius: 20,
                        backgroundColor: AppColors.secondary.withAlpha(20),
                        child: const Icon(Icons.person_rounded, size: 20, color: AppColors.secondary),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              job['title'] ?? 'Job Request',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            Text(
                              'Customer: ${customer['name'] ?? 'Customer'}',
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    job['description'] ?? '',
                    style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 10),

                  Row(
                    children: [
                      const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textMuted),
                      const SizedBox(width: 4),
                      Expanded(
                        child: Text(
                          '${location['address_line1'] ?? ''}, ${location['city'] ?? ''}',
                          style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      Text(
                        category['name'] ?? '',
                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.secondary),
                      ),
                    ],
                  ),

                  // Actions row directly on card
                  if (status == 'REQUESTED') ...[
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: ElevatedButton.icon(
                            style: ElevatedButton.styleFrom(
                              backgroundColor: AppColors.secondary,
                              padding: const EdgeInsets.symmetric(vertical: 8),
                            ),
                            onPressed: () => provider.acceptJob(job['id']),
                            icon: const Icon(Icons.check_rounded, size: 18),
                            label: const Text('Accept'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        Expanded(
                          child: OutlinedButton.icon(
                            style: OutlinedButton.styleFrom(
                              foregroundColor: AppColors.error,
                              padding: const EdgeInsets.symmetric(vertical: 8),
                            ),
                            onPressed: () => _showRejectDialog(job['id'], provider),
                            icon: const Icon(Icons.close_rounded, size: 18),
                            label: const Text('Decline'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
