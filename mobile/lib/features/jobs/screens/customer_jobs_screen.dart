import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/job_provider.dart';
import '../widgets/status_timeline_widget.dart';
import 'job_details_screen.dart';

class CustomerJobsScreen extends StatefulWidget {
  const CustomerJobsScreen({super.key});

  @override
  State<CustomerJobsScreen> createState() => _CustomerJobsScreenState();
}

class _CustomerJobsScreenState extends State<CustomerJobsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;

  final List<Map<String, String>> _statusTabs = [
    {'label': 'All', 'value': 'ALL'},
    {'label': 'Requested', 'value': 'REQUESTED'},
    {'label': 'Accepted', 'value': 'ACCEPTED'},
    {'label': 'Scheduled', 'value': 'SCHEDULED'},
    {'label': 'In Progress', 'value': 'IN_PROGRESS'},
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
      context.read<JobProvider>().fetchJobs(status: 'ALL');
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<JobProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text('My Service Jobs'),
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          labelColor: AppColors.primary,
          unselectedLabelColor: AppColors.textMuted,
          indicatorColor: AppColors.primary,
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
            const Icon(Icons.assignment_late_outlined, size: 56, color: AppColors.textMuted),
            const SizedBox(height: 12),
            const Text(
              'No Jobs Found',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            const Text(
              'There are no service requests matching this filter.',
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
        final worker = job['worker'] ?? {};
        final category = job['category'] ?? {};
        final location = job['location'] ?? {};

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
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
                        backgroundColor: AppColors.primary.withAlpha(20),
                        backgroundImage: (worker['avatar'] != null && worker['avatar'].toString().isNotEmpty)
                            ? NetworkImage(worker['avatar'])
                            : null,
                        child: (worker['avatar'] == null || worker['avatar'].toString().isEmpty)
                            ? const Icon(Icons.person_rounded, size: 20, color: AppColors.primary)
                            : null,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              job['title'] ?? 'Service Request',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            Text(
                              'Worker: ${worker['name'] ?? 'Assigned Worker'}',
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  const Divider(height: 1),
                  const SizedBox(height: 10),

                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.location_on_outlined, size: 14, color: AppColors.textMuted),
                          const SizedBox(width: 4),
                          Text(
                            location['city'] ?? 'Location',
                            style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                          ),
                        ],
                      ),
                      Row(
                        children: [
                          const Icon(Icons.category_outlined, size: 14, color: AppColors.primary),
                          const SizedBox(width: 4),
                          Text(
                            category['name'] ?? 'Service',
                            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        );
      },
    );
  }
}
