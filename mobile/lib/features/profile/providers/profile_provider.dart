import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class ProfileProvider extends ChangeNotifier {
  bool _isLoading = false;
  String? _errorMessage;

  Map<String, dynamic>? _customerData;
  Map<String, dynamic>? _workerData;
  List<dynamic> _categories = [];
  List<dynamic> _skills = [];
  List<dynamic> _portfolioItems = [];
  Map<String, dynamic>? _verificationData;

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  Map<String, dynamic>? get customerData => _customerData;
  Map<String, dynamic>? get workerData => _workerData;
  List<dynamic> get categories => _categories;
  List<dynamic> get skills => _skills;
  List<dynamic> get portfolioItems => _portfolioItems;
  Map<String, dynamic>? get verificationData => _verificationData;

  int get customerCompletion => _customerData?['completion_percentage'] ?? 0;
  int get workerCompletion => _workerData?['completion_percentage'] ?? 0;

  Future<void> fetchCustomerProfile() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.customerProfile);
      if (response.statusCode == 200 && response.data['success'] == true) {
        _customerData = response.data['data'];
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to load customer profile.';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> updateCustomerProfile({
    required String name,
    required String email,
    required String address,
    required String emergencyContact,
    required String paymentMethod,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.put(
        ApiEndpoints.customerProfile,
        data: {
          'name': name,
          'email': email,
          'default_address': address,
          'emergency_contact': emergencyContact,
          'preferred_payment_method': paymentMethod,
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        _customerData = response.data['data'];
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to update customer profile.';
    }
    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> fetchWorkerProfile() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.workerProfile);
      if (response.statusCode == 200 && response.data['success'] == true) {
        _workerData = response.data['data'];
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to load worker profile.';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> updateWorkerProfile({
    required String name,
    required String email,
    required String bio,
    required int experienceYears,
    required double hourlyRate,
    required int serviceAreaRadius,
    required String address,
    required List<int> skillIds,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.put(
        ApiEndpoints.workerProfile,
        data: {
          'name': name,
          'email': email,
          'bio': bio,
          'experience_years': experienceYears,
          'hourly_rate': hourlyRate,
          'service_area_radius_km': serviceAreaRadius,
          'address': address,
          'skill_ids': skillIds,
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        _workerData = response.data['data'];
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to update worker profile.';
    }
    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> fetchCategoriesAndSkills() async {
    try {
      final catRes = await ApiClient.instance.client.get(ApiEndpoints.categories);
      if (catRes.statusCode == 200 && catRes.data['success'] == true) {
        _categories = catRes.data['data'];
      }

      final skillRes = await ApiClient.instance.client.get(ApiEndpoints.skills);
      if (skillRes.statusCode == 200 && skillRes.data['success'] == true) {
        _skills = skillRes.data['data'];
      }
      notifyListeners();
    } catch (_) {}
  }

  // Portfolio Management
  Future<void> fetchPortfolio() async {
    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.workerPortfolio);
      if (response.statusCode == 200 && response.data['success'] == true) {
        _portfolioItems = response.data['data'];
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> addPortfolioItem({
    required String title,
    required String description,
    required File imageFile,
  }) async {
    _isLoading = true;
    notifyListeners();

    try {
      final formData = FormData.fromMap({
        'title': title,
        'description': description,
        'image': await MultipartFile.fromFile(imageFile.path, filename: 'portfolio.jpg'),
      });

      final response = await ApiClient.instance.client.post(
        ApiEndpoints.workerPortfolio,
        data: formData,
      );

      if (response.statusCode == 201 && response.data['success'] == true) {
        await fetchPortfolio();
        await fetchWorkerProfile();
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to upload portfolio item.';
    }
    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<bool> deletePortfolioItem(int itemId) async {
    try {
      final response = await ApiClient.instance.client.delete(
        '${ApiEndpoints.workerPortfolio}/$itemId',
      );
      if (response.statusCode == 200 && response.data['success'] == true) {
        await fetchPortfolio();
        await fetchWorkerProfile();
        return true;
      }
    } catch (_) {}
    return false;
  }

  // Verification Management
  Future<void> fetchVerificationStatus() async {
    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.workerVerification);
      if (response.statusCode == 200 && response.data['success'] == true) {
        _verificationData = response.data['data'];
        notifyListeners();
      }
    } catch (_) {}
  }

  Future<bool> uploadVerificationDocument({
    required String documentType,
    required String documentNumber,
    required File documentFile,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final formData = FormData.fromMap({
        'document_type': documentType,
        'document_number': documentNumber,
        'file': await MultipartFile.fromFile(documentFile.path),
      });

      final response = await ApiClient.instance.client.post(
        ApiEndpoints.workerDocuments,
        data: formData,
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        await fetchVerificationStatus();
        await fetchWorkerProfile();
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to submit document.';
    }
    _isLoading = false;
    notifyListeners();
    return false;
  }

  // Profile Photo Upload
  Future<bool> uploadProfilePhoto(File imageFile) async {
    _isLoading = true;
    notifyListeners();

    try {
      final formData = FormData.fromMap({
        'photo': await MultipartFile.fromFile(imageFile.path, filename: 'avatar.jpg'),
      });

      final response = await ApiClient.instance.client.post(
        ApiEndpoints.profilePhoto,
        data: formData,
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        await fetchCustomerProfile();
        await fetchWorkerProfile();
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _errorMessage = e.response?.data['message'] ?? 'Failed to upload photo.';
    }
    _isLoading = false;
    notifyListeners();
    return false;
  }
}
