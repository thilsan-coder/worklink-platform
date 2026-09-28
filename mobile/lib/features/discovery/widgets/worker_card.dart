import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import '../../profile/screens/public_worker_profile_screen.dart';

class WorkerCard extends StatelessWidget {
  final Map<String, dynamic> worker;

  const WorkerCard({
    super.key,
    required this.worker,
  });

  @override
  Widget build(BuildContext context) {
    final bool isVerified = worker['is_verified'] ?? false;
    final List skills = worker['skills'] ?? [];
    final String? avatarUrl = worker['avatar'];
    final double rating = (worker['average_rating'] as num?)?.toDouble() ?? 0.0;
    final int reviewsCount = (worker['total_reviews'] as num?)?.toInt() ?? 0;
    final double hourlyRate = (worker['hourly_rate'] as num?)?.toDouble() ?? 0.0;
    final int experience = (worker['experience_years'] as num?)?.toInt() ?? 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () {
          Navigator.of(context).push(
            MaterialPageRoute(
              builder: (_) => PublicWorkerProfileScreen(
                workerProfileId: worker['id'],
              ),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 32,
                    backgroundColor: AppColors.primary.withAlpha(20),
                    backgroundImage: (avatarUrl != null && avatarUrl.isNotEmpty)
                        ? NetworkImage(avatarUrl)
                        : null,
                    child: (avatarUrl == null || avatarUrl.isEmpty)
                        ? const Icon(Icons.person_rounded, size: 32, color: AppColors.primary)
                        : null,
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                worker['name'] ?? 'Skilled Worker',
                                style: const TextStyle(
                                  fontSize: 17,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textPrimary,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (isVerified) ...[
                              const SizedBox(width: 6),
                              const Icon(
                                Icons.verified_rounded,
                                size: 18,
                                color: AppColors.secondary,
                              ),
                            ],
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          worker['address'] != null && worker['address'].toString().isNotEmpty
                              ? worker['address']
                              : 'Location Available',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            const Icon(Icons.star_rounded, size: 16, color: AppColors.accent),
                            const SizedBox(width: 4),
                            Text(
                              rating > 0 ? rating.toStringAsFixed(1) : 'New',
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                            if (reviewsCount > 0)
                              Text(
                                ' ($reviewsCount reviews)',
                                style: const TextStyle(fontSize: 12, color: AppColors.textMuted),
                              ),
                            const Spacer(),
                            Text(
                              'LKR ${hourlyRate.toStringAsFixed(0)}/hr',
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                                color: AppColors.primary,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              if (worker['bio'] != null && worker['bio'].toString().isNotEmpty) ...[
                const SizedBox(height: 10),
                Text(
                  worker['bio'],
                  style: const TextStyle(fontSize: 13, color: AppColors.textSecondary, height: 1.3),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
              const SizedBox(height: 12),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Wrap(
                      spacing: 6,
                      runSpacing: 4,
                      children: skills.take(3).map((s) {
                        return Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withAlpha(15),
                            borderRadius: BorderRadius.circular(6),
                            border: Border.all(color: AppColors.primary.withAlpha(40)),
                          ),
                          child: Text(
                            s['name'] ?? '',
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.primary,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                  ),
                  Text(
                    '$experience yrs exp',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w500,
                      color: AppColors.textMuted,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
