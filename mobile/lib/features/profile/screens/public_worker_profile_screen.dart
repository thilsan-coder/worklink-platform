import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/theme/app_colors.dart';
import '../../reviews/providers/review_provider.dart';

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
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ReviewProvider>().fetchWorkerReviews(widget.workerProfileId);
    });
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

  void _showServiceRequestDialog(Map<String, dynamic> worker) {
    final titleController = TextEditingController();
    final descriptionController = TextEditingController();
    final addressController = TextEditingController(text: worker['address'] ?? '');
    final cityController = TextEditingController(text: 'Colombo');
    final formKey = GlobalKey<FormState>();
    bool isSubmitting = false;

    final List skills = worker['skills'] ?? [];
    final int selectedCategoryId = (skills.isNotEmpty && skills.first['category_id'] != null)
        ? skills.first['category_id']
        : 1;

    showDialog(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: Text('Request Service from ${worker['name']}'),
              content: SingleChildScrollView(
                child: Form(
                  key: formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      TextFormField(
                        controller: titleController,
                        decoration: const InputDecoration(
                          labelText: 'Job Title',
                          hintText: 'e.g. Repair Leaking Pipe',
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Enter job title' : null,
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: descriptionController,
                        maxLines: 3,
                        decoration: const InputDecoration(
                          labelText: 'Description',
                          hintText: 'Describe the work needed in detail...',
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Enter description' : null,
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: addressController,
                        decoration: const InputDecoration(
                          labelText: 'Street Address',
                          hintText: '123 Main Street',
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Enter address' : null,
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: cityController,
                        decoration: const InputDecoration(
                          labelText: 'City / District',
                          hintText: 'Colombo',
                        ),
                        validator: (v) => (v == null || v.trim().isEmpty) ? 'Enter city' : null,
                      ),
                    ],
                  ),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: isSubmitting ? null : () => Navigator.of(dialogContext).pop(),
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  onPressed: isSubmitting
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) return;

                          final messenger = ScaffoldMessenger.of(context);
                          final nav = Navigator.of(dialogContext);

                          try {
                            final res = await ApiClient.instance.client.post(
                              ApiEndpoints.jobs,
                              data: {
                                'worker_id': worker['user_id'],
                                'category_id': selectedCategoryId,
                                'title': titleController.text.trim(),
                                'description': descriptionController.text.trim(),
                                'address_line1': addressController.text.trim(),
                                'city': cityController.text.trim(),
                                'latitude': 6.9271,
                                'longitude': 79.8612,
                              },
                            );

                            nav.pop();
                            if (res.statusCode == 201 && res.data['success'] == true) {
                              messenger.showSnackBar(
                                const SnackBar(
                                  content: Text('Service request submitted successfully! Worker notified.'),
                                  backgroundColor: AppColors.secondary,
                                ),
                              );
                            }
                          } on DioException catch (e) {
                            setDialogState(() => isSubmitting = false);
                            final msg = e.response?.data['message'] ?? 'Failed to submit request. Please log in as Customer.';
                            messenger.showSnackBar(
                              SnackBar(content: Text(msg), backgroundColor: AppColors.error),
                            );
                          }
                        },
                  child: isSubmitting
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : const Text('Submit Request'),
                ),
              ],
            );
          },
        );
      },
    );
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
            const SizedBox(height: 28),

            // Rating Summary & Reviews Section
            Consumer<ReviewProvider>(
              builder: (context, reviewProv, _) {
                final reviews = reviewProv.workerReviews;
                final ratingSummary = reviewProv.workerRatingSummary;
                final avgRating = ratingSummary != null
                    ? (ratingSummary['average_rating'] as num?)?.toDouble() ?? 0.0
                    : (worker['average_rating'] as num?)?.toDouble() ?? 0.0;
                final totalReviews = ratingSummary != null
                    ? (ratingSummary['total_reviews'] as int?) ?? 0
                    : (worker['total_reviews'] as int?) ?? 0;

                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Reviews & Ratings', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                        Text(
                          '$totalReviews reviews',
                          style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Rating Summary Card
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Row(
                          children: [
                            Column(
                              children: [
                                Text(
                                  avgRating.toStringAsFixed(1),
                                  style: const TextStyle(fontSize: 36, fontWeight: FontWeight.bold),
                                ),
                                Row(
                                  children: List.generate(5, (i) {
                                    return Icon(
                                      i < avgRating.round() ? Icons.star_rounded : Icons.star_outline_rounded,
                                      size: 16,
                                      color: i < avgRating.round() ? AppColors.accent : AppColors.textMuted,
                                    );
                                  }),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '$totalReviews ratings',
                                  style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                                ),
                              ],
                            ),
                            const SizedBox(width: 20),
                            const VerticalDivider(width: 1),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  _buildRatingBar(5, totalReviews > 0 ? (reviews.where((r) => ((r['rating'] ?? r['overall_rating'] ?? 0) as num) == 5).length / totalReviews) : 0),
                                  _buildRatingBar(4, totalReviews > 0 ? (reviews.where((r) => ((r['rating'] ?? r['overall_rating'] ?? 0) as num) == 4).length / totalReviews) : 0),
                                  _buildRatingBar(3, totalReviews > 0 ? (reviews.where((r) => ((r['rating'] ?? r['overall_rating'] ?? 0) as num) == 3).length / totalReviews) : 0),
                                  _buildRatingBar(2, totalReviews > 0 ? (reviews.where((r) => ((r['rating'] ?? r['overall_rating'] ?? 0) as num) == 2).length / totalReviews) : 0),
                                  _buildRatingBar(1, totalReviews > 0 ? (reviews.where((r) => ((r['rating'] ?? r['overall_rating'] ?? 0) as num) == 1).length / totalReviews) : 0),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Reviews List
                    if (reviewProv.isLoadingWorkerReviews && reviews.isEmpty)
                      const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator()))
                    else if (reviews.isEmpty)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border.withAlpha(80)),
                        ),
                        child: Column(
                          children: const [
                            Icon(Icons.rate_review_outlined, size: 40, color: AppColors.textMuted),
                            SizedBox(height: 8),
                            Text(
                              'No reviews yet',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                            ),
                            SizedBox(height: 4),
                            Text(
                              'Be the first customer to hire and review this worker!',
                              style: TextStyle(color: AppColors.textMuted, fontSize: 12),
                              textAlign: TextAlign.center,
                            ),
                          ],
                        ),
                      )
                    else
                      ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: reviews.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (context, index) {
                          final rev = reviews[index];
                          final custName = rev['customer_name'] ?? rev['customer']?['name'] ?? 'Customer';
                          final custAvatar = rev['customer_avatar'] ?? rev['customer']?['avatar'];
                          final rating = (rev['rating'] ?? rev['overall_rating'] ?? 5) as num;
                          final comment = rev['comment'] ?? rev['review'] ?? '';
                          final createdAt = rev['created_at'] != null
                              ? rev['created_at'].toString().substring(0, 10)
                              : '';

                          return Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: AppColors.surface,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: AppColors.border.withAlpha(80)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    CircleAvatar(
                                      radius: 16,
                                      backgroundColor: AppColors.primary.withAlpha(20),
                                      backgroundImage: (custAvatar != null && custAvatar.toString().isNotEmpty)
                                          ? NetworkImage(custAvatar)
                                          : null,
                                      child: (custAvatar == null || custAvatar.toString().isEmpty)
                                          ? Text(
                                              custName.isNotEmpty ? custName[0].toUpperCase() : 'C',
                                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                                            )
                                          : null,
                                    ),
                                    const SizedBox(width: 10),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(custName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                          Text(createdAt, style: const TextStyle(fontSize: 10.5, color: AppColors.textMuted)),
                                        ],
                                      ),
                                    ),
                                    Row(
                                      children: List.generate(5, (i) {
                                        return Icon(
                                          i < rating ? Icons.star_rounded : Icons.star_outline_rounded,
                                          size: 15,
                                          color: i < rating ? AppColors.accent : AppColors.textMuted,
                                        );
                                      }),
                                    ),
                                  ],
                                ),
                                if (comment.toString().isNotEmpty) ...[
                                  const SizedBox(height: 8),
                                  Text(
                                    comment.toString(),
                                    style: const TextStyle(fontSize: 13, color: AppColors.textPrimary, height: 1.3),
                                  ),
                                ],
                              ],
                            ),
                          );
                        },
                      ),
                  ],
                );
              },
            ),
            const SizedBox(height: 32),

            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () => _showServiceRequestDialog(worker),
                icon: const Icon(Icons.send_rounded),
                label: Text('Request Service (${worker['name']})'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRatingBar(int stars, double ratio) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        children: [
          Text('$stars★', style: const TextStyle(fontSize: 10, color: AppColors.textMuted)),
          const SizedBox(width: 6),
          Expanded(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(4),
              child: LinearProgressIndicator(
                value: ratio.clamp(0.0, 1.0),
                minHeight: 5,
                backgroundColor: AppColors.border,
                valueColor: const AlwaysStoppedAnimation<Color>(AppColors.accent),
              ),
            ),
          ),
        ],
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
