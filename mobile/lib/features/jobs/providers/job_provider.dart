import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class JobProvider extends ChangeNotifier {
  List<Map<String, dynamic>> _jobs = [];
  Map<String, dynamic>? _selectedJob;
  List<Map<String, dynamic>> _jobHistory = [];
  List<Map<String, dynamic>> _notifications = [];

  bool _isLoading = false;
  bool _isActionLoading = false;
  String? _error;
  String? _statusFilter;

  List<Map<String, dynamic>> get jobs => _jobs;
  Map<String, dynamic>? get selectedJob => _selectedJob;
  List<Map<String, dynamic>> get jobHistory => _jobHistory;
  List<Map<String, dynamic>> get notifications => _notifications;

  bool get isLoading => _isLoading;
  bool get isActionLoading => _isActionLoading;
  String? get error => _error;
  String? get statusFilter => _statusFilter;

  Future<void> fetchJobs({String? status}) async {
    _isLoading = true;
    _error = null;
    _statusFilter = status;
    notifyListeners();

    try {
      final Map<String, dynamic> params = {};
      if (status != null && status.isNotEmpty && status != 'ALL') {
        params['status'] = status;
      }

      final res = await ApiClient.instance.client.get(
        ApiEndpoints.jobs,
        queryParameters: params,
      );

      if (res.statusCode == 200 && res.data['success'] == true) {
        final List items = res.data['data']['data'] ?? [];
        _jobs = List<Map<String, dynamic>>.from(items);
        _error = null;
      } else {
        _error = res.data['message'] ?? 'Failed to load jobs';
      }
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? 'Network error fetching jobs';
    } catch (e) {
      _error = 'Unexpected error occurred';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchJobDetails(int jobId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final res = await ApiClient.instance.client.get('${ApiEndpoints.jobs}/$jobId');
      if (res.statusCode == 200 && res.data['success'] == true) {
        _selectedJob = Map<String, dynamic>.from(res.data['data']);
        await fetchJobHistory(jobId);
      } else {
        _error = res.data['message'] ?? 'Failed to load job details';
      }
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? 'Failed to load job details';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchJobHistory(int jobId) async {
    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.jobHistory(jobId));
      if (res.statusCode == 200 && res.data['success'] == true) {
        final List items = res.data['data'] ?? [];
        _jobHistory = List<Map<String, dynamic>>.from(items);
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> acceptJob(int jobId) async {
    return _performJobAction(ApiEndpoints.jobAccept(jobId), {});
  }

  Future<bool> rejectJob(int jobId, String reason) async {
    return _performJobAction(ApiEndpoints.jobReject(jobId), {'reason': reason});
  }

  Future<bool> scheduleJob(int jobId, DateTime scheduledAt, {String? notes}) async {
    final Map<String, dynamic> body = {'scheduled_at': scheduledAt.toIso8601String()};
    if (notes != null) body['notes'] = notes;
    return _performJobAction(ApiEndpoints.jobSchedule(jobId), body);
  }

  Future<bool> startJob(int jobId, {String? notes}) async {
    final Map<String, dynamic> body = {};
    if (notes != null) body['notes'] = notes;
    return _performJobAction(ApiEndpoints.jobStart(jobId), body);
  }

  Future<bool> completeJob(int jobId, {String? notes}) async {
    final Map<String, dynamic> body = {};
    if (notes != null) body['notes'] = notes;
    return _performJobAction(ApiEndpoints.jobComplete(jobId), body);
  }

  Future<bool> cancelJob(int jobId, String reason) async {
    return _performJobAction(ApiEndpoints.jobCancel(jobId), {'reason': reason});
  }

  Future<bool> _performJobAction(String endpoint, Map<String, dynamic> data) async {
    _isActionLoading = true;
    _error = null;
    notifyListeners();

    try {
      final res = await ApiClient.instance.client.post(endpoint, data: data);
      if (res.statusCode == 200 && res.data['success'] == true) {
        if (_selectedJob != null) {
          final int jobId = _selectedJob!['id'];
          await fetchJobDetails(jobId);
        }
        await fetchJobs(status: _statusFilter);
        return true;
      }
      _error = res.data['message'] ?? 'Action failed';
      return false;
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? 'Action failed';
      return false;
    } finally {
      _isActionLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchNotifications() async {
    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.notifications);
      if (res.statusCode == 200 && res.data['success'] == true) {
        final List items = res.data['data']['data'] ?? res.data['data'] ?? [];
        _notifications = List<Map<String, dynamic>>.from(items);
        notifyListeners();
      }
    } catch (_) {}
  }
}
