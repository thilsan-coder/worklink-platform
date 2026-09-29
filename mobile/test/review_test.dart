import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/reviews/providers/review_provider.dart';

void main() {
  group('ReviewProvider Unit Tests', () {
    test('initial state is correct', () {
      final provider = ReviewProvider();
      expect(provider.jobReviews, isEmpty);
      expect(provider.workerReviews, isEmpty);
      expect(provider.workerRatingSummary, isNull);
      expect(provider.isLoadingJobReview, isFalse);
      expect(provider.isSubmittingReview, isFalse);
    });

    test('getJobReview returns null for uninspected jobs', () {
      final provider = ReviewProvider();
      expect(provider.getJobReview(999), isNull);
    });
  });
}
