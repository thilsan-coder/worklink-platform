import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class ReviewProvider extends ChangeNotifier {
  final Map<int, Map<String, dynamic>?> _jobReviews = {};
  bool _isLoadingJobReview = false;
  String? _jobReviewError;

  List<dynamic> _workerReviews = [];
  Map<String, dynamic>? _workerRatingSummary;
  bool _isLoadingWorkerReviews = false;
  bool _isSubmittingReview = false;
  String? _reviewError;

  // Getters
  Map<int, Map<String, dynamic>?> get jobReviews => _jobReviews;
  bool get isLoadingJobReview => _isLoadingJobReview;
  String? get jobReviewError => _jobReviewError;

  List<dynamic> get workerReviews => _workerReviews;
  Map<String, dynamic>? get workerRatingSummary => _workerRatingSummary;
  bool get isLoadingWorkerReviews => _isLoadingWorkerReviews;
  bool get isSubmittingReview => _isSubmittingReview;
  String? get reviewError => _reviewError;

  /// Get cached review for a job if available
  Map<String, dynamic>? getJobReview(int jobId) => _jobReviews[jobId];

  /// Fetch existing review for a specific job
  Future<Map<String, dynamic>?> fetchJobReview(int jobId) async {
    _isLoadingJobReview = true;
    _jobReviewError = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.jobReview(jobId));
      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data'] as Map<String, dynamic>?;
        _jobReviews[jobId] = data;
        _isLoadingJobReview = false;
        notifyListeners();
        return data;
      }
    } on DioException catch (e) {
      _jobReviewError = e.response?.data['message'];
    } catch (e) {
      _jobReviewError = 'Failed to load review';
    } finally {
      _isLoadingJobReview = false;
      notifyListeners();
    }
    return null;
  }

  /// Submit a new review for a completed job
  Future<bool> submitReview(int jobId, {required int rating, String? comment}) async {
    _isSubmittingReview = true;
    _reviewError = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.post(
        ApiEndpoints.jobReview(jobId),
        data: {
          'rating': rating,
          'overall_rating': rating,
          'comment': comment?.trim() ?? '',
          'review': comment?.trim() ?? '',
        },
      );

      if (response.statusCode == 201 && response.data['success'] == true) {
        final reviewData = response.data['data'] as Map<String, dynamic>;
        _jobReviews[jobId] = reviewData;
        _isSubmittingReview = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _reviewError = e.response?.data['message'] ?? 'Failed to submit review.';
    } catch (e) {
      _reviewError = 'An unexpected error occurred.';
    } finally {
      _isSubmittingReview = false;
      notifyListeners();
    }
    return false;
  }

  /// Fetch reviews list for a worker
  Future<void> fetchWorkerReviews(int workerId) async {
    _isLoadingWorkerReviews = true;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.workerReviews(workerId));
      if (response.statusCode == 200 && response.data['success'] == true) {
        final resData = response.data['data'];
        _workerReviews = resData is Map ? (resData['data'] ?? []) : (resData ?? []);
      }
    } catch (_) {}

    try {
      final ratingRes = await ApiClient.instance.client.get(ApiEndpoints.workerRating(workerId));
      if (ratingRes.statusCode == 200 && ratingRes.data['success'] == true) {
        _workerRatingSummary = ratingRes.data['data'];
      }
    } catch (_) {}

    _isLoadingWorkerReviews = false;
    notifyListeners();
  }
}
