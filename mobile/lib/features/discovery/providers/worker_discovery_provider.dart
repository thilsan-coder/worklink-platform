import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class WorkerDiscoveryProvider extends ChangeNotifier {
  List<Map<String, dynamic>> _workers = [];
  List<Map<String, dynamic>> _categories = [];
  List<Map<String, dynamic>> _skills = [];

  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _error;

  int _currentPage = 1;
  int _lastPage = 1;
  int _totalWorkers = 0;

  // Filter State
  String _searchQuery = '';
  int? _selectedCategoryId;
  int? _selectedSkillId;
  String? _locationQuery;
  double? _minRate;
  double? _maxRate;
  double? _minRating;
  bool _verifiedOnly = false;

  List<Map<String, dynamic>> get workers => _workers;
  List<Map<String, dynamic>> get categories => _categories;
  List<Map<String, dynamic>> get skills => _skills;

  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get error => _error;

  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get totalWorkers => _totalWorkers;

  String get searchQuery => _searchQuery;
  int? get selectedCategoryId => _selectedCategoryId;
  int? get selectedSkillId => _selectedSkillId;
  String? get locationQuery => _locationQuery;
  double? get minRate => _minRate;
  double? get maxRate => _maxRate;
  double? get minRating => _minRating;
  bool get verifiedOnly => _verifiedOnly;

  bool get hasActiveFilters =>
      _searchQuery.isNotEmpty ||
      _selectedCategoryId != null ||
      _selectedSkillId != null ||
      (_locationQuery != null && _locationQuery!.isNotEmpty) ||
      _minRate != null ||
      _maxRate != null ||
      _minRating != null ||
      _verifiedOnly;

  Future<void> fetchCategories() async {
    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.categories);
      if (res.statusCode == 200 && res.data['success'] == true) {
        _categories = List<Map<String, dynamic>>.from(res.data['data'] ?? []);
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> fetchSkills() async {
    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.skills);
      if (res.statusCode == 200 && res.data['success'] == true) {
        _skills = List<Map<String, dynamic>>.from(res.data['data'] ?? []);
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<void> fetchWorkers({bool refresh = true}) async {
    if (refresh) {
      _isLoading = true;
      _currentPage = 1;
      _workers = [];
      _error = null;
      notifyListeners();
    } else {
      if (_isLoadingMore || _currentPage >= _lastPage) return;
      _isLoadingMore = true;
      notifyListeners();
    }

    try {
      final Map<String, dynamic> queryParams = {
        'page': _currentPage,
        'per_page': 10,
      };

      if (_searchQuery.trim().isNotEmpty) queryParams['search'] = _searchQuery.trim();
      if (_selectedCategoryId != null) queryParams['category_id'] = _selectedCategoryId;
      if (_selectedSkillId != null) queryParams['skill_id'] = _selectedSkillId;
      if (_locationQuery != null && _locationQuery!.trim().isNotEmpty) {
        queryParams['location'] = _locationQuery!.trim();
      }
      if (_minRate != null) queryParams['min_rate'] = _minRate;
      if (_maxRate != null) queryParams['max_rate'] = _maxRate;
      if (_minRating != null) queryParams['min_rating'] = _minRating;
      if (_verifiedOnly) queryParams['verified'] = 1;

      final res = await ApiClient.instance.client.get(
        ApiEndpoints.workers,
        queryParameters: queryParams,
      );

      if (res.statusCode == 200 && res.data['success'] == true) {
        final data = res.data['data'];
        final List<Map<String, dynamic>> items = List<Map<String, dynamic>>.from(data['data'] ?? []);
        final meta = data['meta'] ?? {};

        if (refresh) {
          _workers = items;
        } else {
          _workers.addAll(items);
        }

        _currentPage = meta['current_page'] ?? _currentPage;
        _lastPage = meta['last_page'] ?? _lastPage;
        _totalWorkers = meta['total'] ?? _workers.length;
        _error = null;
      } else {
        _error = res.data['message'] ?? 'Failed to load workers';
      }
    } on DioException catch (e) {
      _error = e.response?.data['message'] ?? 'Network error while fetching workers';
    } catch (e) {
      _error = 'Unexpected error occurred';
    } finally {
      _isLoading = false;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  Future<void> loadMoreWorkers() async {
    if (_currentPage < _lastPage) {
      _currentPage++;
      await fetchWorkers(refresh: false);
    }
  }

  void setSearchQuery(String query) {
    _searchQuery = query;
    fetchWorkers(refresh: true);
  }

  void selectCategory(int? categoryId) {
    if (_selectedCategoryId == categoryId) {
      _selectedCategoryId = null;
    } else {
      _selectedCategoryId = categoryId;
    }
    fetchWorkers(refresh: true);
  }

  void applyFilters({
    int? categoryId,
    int? skillId,
    String? location,
    double? minRate,
    double? maxRate,
    double? minRating,
    bool? verifiedOnly,
  }) {
    _selectedCategoryId = categoryId;
    _selectedSkillId = skillId;
    _locationQuery = location;
    _minRate = minRate;
    _maxRate = maxRate;
    _minRating = minRating;
    _verifiedOnly = verifiedOnly ?? false;

    fetchWorkers(refresh: true);
  }

  void clearFilters() {
    _searchQuery = '';
    _selectedCategoryId = null;
    _selectedSkillId = null;
    _locationQuery = null;
    _minRate = null;
    _maxRate = null;
    _minRating = null;
    _verifiedOnly = false;

    fetchWorkers(refresh: true);
  }
}
