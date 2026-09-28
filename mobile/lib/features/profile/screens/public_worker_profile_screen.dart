import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/theme/app_colors.dart';

class PublicWorkerProfileScreen extends StatefulWidget {
  final int workerProfileId;

  const PublicWorkerProfileScreen({
    super.key,
    required this.workerProfileId,
  });

  @override
  State<PublicWorkerProfileScreen> createState() => _PublicWorkerProfileScreenState();
}

class _PublicWorkerProfileScreenState extends State<PublicWorkerProfileScreen> {
  bool _isLoading = true;
  String? _error;
  Map<String, dynamic>? _workerData;

  @override
  void initState() {
    super.initState();
    _fetchPublicWorkerProfile();
  }

  Future<void> _fetchPublicWorkerProfile() async {
    try {
      final response = await ApiClient.instance.client.get('${ApiEndpoints.workers}/${widget.workerProfileId}');
      if (response.statusCode == 200 && response.data['success'] == true) {
        setState(() {
          _workerData = response.data['data'];
          _isLoading = false;
        });
      }
    } on DioException catch (e) {
      setState(() {
        _error = e.response?.data['message'] ?? 'Failed to load worker profile.';
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Worker Profile')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_error != null || _workerData == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Worker Profile')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
              const SizedBox(height: 12),
              Text(_error ?? 'Worker profile not found', style: const TextStyle(color: AppColors.textSecondary)),
            ],
          ),
        ),
      );
    }

    final worker = _workerData!;
    final bool isVerified = worker['is_verified'] ?? false;
    final List skills = worker['skills'] ?? [];
    final List portfolio = worker['portfolio'] ?? [];

    return Scaffold(
      appBar: AppBar(title: Text(worker['name'] ?? 'Worker Profile')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 40,
                      backgroundColor: AppColors.primary.withAlpha(20),
                      backgroundImage: (worker['avatar'] != null && worker['avatar'].toString().isNotEmpty)
                          ? NetworkImage(worker['avatar'])
                          : null,
                      child: (worker['avatar'] == null || worker['avatar'].toString().isEmpty)
                          ? const Icon(Icons.person_rounded, size: 40, color: AppColors.primary)
                          : null,
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Flexible(
                                child: Text(
                                  worker['name'] ?? 'Worker Name',
                                  style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              if (isVerified) ...[
                                const SizedBox(width: 6),
                                const Icon(Icons.verified_rounded, size: 18, color: AppColors.secondary),
                              ],
                            ],
                          ),
                          const SizedBox(height: 4),
                          Text(
                            worker['address'] ?? 'Location Available',
                            style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const Icon(Icons.star_rounded, color: AppColors.accent, size: 18),
                              const SizedBox(width: 4),
                              Text(
                                '${worker['average_rating']} (${worker['total_reviews']} reviews)',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            Row(
              children: [
                _buildMetricTile('Experience', '${worker['experience_years']} Yrs', Icons.history_edu_rounded),
                const SizedBox(width: 12),
                _buildMetricTile('Rate', 'LKR ${worker['hourly_rate']}/hr', Icons.payments_outlined),
                const SizedBox(width: 12),
                _buildMetricTile('Completed', '${worker['completed_jobs_count']} Jobs', Icons.task_alt_rounded),
              ],
            ),
            const SizedBox(height: 24),

            const Text('About Worker', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text(
              worker['bio'] ?? 'No bio provided yet.',
              style: const TextStyle(color: AppColors.textSecondary, height: 1.4),
            ),
            const SizedBox(height: 24),

            const Text('Skills & Expertise', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: skills
                  .map((s) => Chip(
                        label: Text(s['name'] ?? ''),
                        backgroundColor: AppColors.primary.withAlpha(15),
                        side: const BorderSide(color: AppColors.primary),
                      ))
                  .toList(),
            ),
            const SizedBox(height: 24),

            const Text('Work Showcase Portfolio', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),

            if (portfolio.isEmpty)
              const Text('No portfolio images uploaded by worker.', style: TextStyle(color: AppColors.textMuted))
            else
              SizedBox(
                height: 160,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: portfolio.length,
                  itemBuilder: (context, index) {
                    final item = portfolio[index];
                    return Container(
                      width: 160,
                      margin: const EdgeInsets.only(right: 12),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: Stack(
                          children: [
                            Image.network(
                              item['image_url'],
                              width: 160,
                              height: 160,
                              fit: BoxFit.cover,
                              errorBuilder: (context, error, stackTrace) => const Center(child: Icon(Icons.image_not_supported_rounded)),
                            ),
                            Positioned(
                              bottom: 0,
                              left: 0,
                              right: 0,
                              child: Container(
                                padding: const EdgeInsets.all(6),
                                color: Colors.black54,
                                child: Text(
                                  item['title'] ?? '',
                                  style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
            const SizedBox(height: 32),

            ElevatedButton.icon(
              onPressed: () {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Job Request workflow ready for Phase 6')),
                );
              },
              icon: const Icon(Icons.send_rounded),
              label: Text('Request Service (${worker['name']})'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMetricTile(String label, String value, IconData icon) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          children: [
            Icon(icon, size: 22, color: AppColors.primary),
            const SizedBox(height: 6),
            Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13), textAlign: TextAlign.center),
            const SizedBox(height: 2),
            Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textSecondary), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}
