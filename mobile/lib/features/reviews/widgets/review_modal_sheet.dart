import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/review_provider.dart';

class ReviewModalSheet extends StatefulWidget {
  final int jobId;
  final String jobTitle;
  final String workerName;
  final VoidCallback? onReviewSubmitted;

  const ReviewModalSheet({
    super.key,
    required this.jobId,
    required this.jobTitle,
    required this.workerName,
    this.onReviewSubmitted,
  });

  static Future<bool?> show(
    BuildContext context, {
    required int jobId,
    required String jobTitle,
    required String workerName,
    VoidCallback? onReviewSubmitted,
  }) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => ReviewModalSheet(
        jobId: jobId,
        jobTitle: jobTitle,
        workerName: workerName,
        onReviewSubmitted: onReviewSubmitted,
      ),
    );
  }

  @override
  State<ReviewModalSheet> createState() => _ReviewModalSheetState();
}

class _ReviewModalSheetState extends State<ReviewModalSheet> {
  int _selectedRating = 5;
  final TextEditingController _commentController = TextEditingController();

  String _getRatingLabel(int rating) {
    switch (rating) {
      case 1:
        return '1/5 - Poor';
      case 2:
        return '2/5 - Fair';
      case 3:
        return '3/5 - Good';
      case 4:
        return '4/5 - Very Good';
      case 5:
        return '5/5 - Outstanding!';
      default:
        return '';
    }
  }

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  Future<void> _handleSubmit() async {
    final reviewProvider = context.read<ReviewProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final nav = Navigator.of(context);

    final success = await reviewProvider.submitReview(
      widget.jobId,
      rating: _selectedRating,
      comment: _commentController.text.trim(),
    );

    if (success) {
      widget.onReviewSubmitted?.call();
      nav.pop(true);
      messenger.showSnackBar(
        const SnackBar(
          content: Text('Thank you! Your review has been submitted.'),
          backgroundColor: AppColors.secondary,
        ),
      );
    } else if (mounted && reviewProvider.reviewError != null) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(reviewProvider.reviewError!),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final reviewProvider = context.watch<ReviewProvider>();
    final isSubmitting = reviewProvider.isSubmittingReview;

    return Container(
      decoration: const BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Handle bar
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: AppColors.border,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 16),

            Text(
              'Rate & Review ${widget.workerName}',
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 4),
            Text(
              'Job: ${widget.jobTitle}',
              style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 20),

            // Star Selector
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(5, (index) {
                final starValue = index + 1;
                return InkWell(
                  onTap: isSubmitting ? null : () => setState(() => _selectedRating = starValue),
                  borderRadius: BorderRadius.circular(24),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
                    child: Icon(
                      starValue <= _selectedRating ? Icons.star_rounded : Icons.star_outline_rounded,
                      size: 40,
                      color: starValue <= _selectedRating ? AppColors.accent : AppColors.textMuted,
                    ),
                  ),
                );
              }),
            ),
            const SizedBox(height: 8),

            // Rating text feedback
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.accent.withAlpha(25),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                _getRatingLabel(_selectedRating),
                style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.orange, fontSize: 13),
              ),
            ),
            const SizedBox(height: 20),

            // Review text area
            TextField(
              controller: _commentController,
              maxLines: 4,
              maxLength: 1000,
              enabled: !isSubmitting,
              decoration: InputDecoration(
                hintText: 'Share your feedback on punctuality, work quality, and communication...',
                hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 13),
                filled: true,
                fillColor: Theme.of(context).scaffoldBackgroundColor,
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Submit Button
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: isSubmitting ? null : _handleSubmit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: isSubmitting
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                    : const Text(
                        'Submit Review',
                        style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
