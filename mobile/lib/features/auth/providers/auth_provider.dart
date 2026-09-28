import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/storage/secure_storage.dart';

enum AuthStatus { uninitialized, authenticated, unauthenticated, loading }

class AuthProvider extends ChangeNotifier {
  AuthStatus _status = AuthStatus.uninitialized;
  Map<String, dynamic>? _user;
  String _activeRole = 'customer';
  String? _errorMessage;
  Map<String, String>? _fieldErrors;
  bool _isNewUser = false;

  AuthStatus get status => _status;
  Map<String, dynamic>? get user => _user;
  String get activeRole => _activeRole;
  String? get errorMessage => _errorMessage;
  Map<String, String>? get fieldErrors => _fieldErrors;
  bool get isNewUser => _isNewUser;

  bool get isAuthenticated => _status == AuthStatus.authenticated;

  Future<void> restoreSession() async {
    _status = AuthStatus.loading;
    notifyListeners();

    try {
      final token = await StorageService.instance.getAuthToken();
      if (token == null || token.isEmpty) {
        _status = AuthStatus.unauthenticated;
        notifyListeners();
        return;
      }

      final response = await ApiClient.instance.client.get(ApiEndpoints.userProfile);

      if (response.statusCode == 200 && response.data['success'] == true) {
        _user = response.data['data'];
        _activeRole = await StorageService.instance.getUserRole();
        _status = AuthStatus.authenticated;
      } else {
        await logout();
      }
    } catch (e) {
      await logout();
    }
    notifyListeners();
  }

  Future<bool> sendOtp(String phone) async {
    _clearErrors();
    try {
      final response = await ApiClient.instance.client.post(
        ApiEndpoints.sendOtp,
        data: {'phone': phone},
      );
      return response.data['success'] == true;
    } on DioException catch (e) {
      _handleDioError(e);
      notifyListeners();
      return false;
    }
  }

  Future<bool> verifyOtp({
    required String phone,
    required String otpCode,
    String? role,
    String? name,
  }) async {
    _clearErrors();
    _status = AuthStatus.loading;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.post(
        ApiEndpoints.verifyOtp,
        data: {
          'phone': phone,
          'otp_code': otpCode,
          'role': role ?? 'customer',
          'name': name,
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data'];
        final token = data['token'];
        _user = data['user'];
        _isNewUser = data['is_new_user'] ?? false;
        _activeRole = role ?? _user?['role'] ?? 'customer';

        await StorageService.instance.saveAuthToken(token);
        await StorageService.instance.saveUserData(
          id: _user!['id'],
          name: _user!['name'] ?? '',
          phone: _user!['phone'] ?? '',
          role: _activeRole,
        );

        _status = AuthStatus.authenticated;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _handleDioError(e);
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return false;
    }
    _status = AuthStatus.unauthenticated;
    notifyListeners();
    return false;
  }

  Future<bool> googleLogin({
    required String googleId,
    required String email,
    String? name,
    String? avatar,
    String? role,
  }) async {
    _clearErrors();
    _status = AuthStatus.loading;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.post(
        ApiEndpoints.googleLogin,
        data: {
          'google_id': googleId,
          'email': email,
          'name': name,
          'avatar': avatar,
          'role': role ?? 'customer',
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data'];
        final token = data['token'];
        _user = data['user'];
        _isNewUser = data['is_new_user'] ?? false;
        _activeRole = role ?? _user?['role'] ?? 'customer';

        await StorageService.instance.saveAuthToken(token);
        await StorageService.instance.saveUserData(
          id: _user!['id'],
          name: _user!['name'] ?? '',
          phone: _user!['phone'] ?? '',
          role: _activeRole,
        );

        _status = AuthStatus.authenticated;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _handleDioError(e);
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return false;
    }
    _status = AuthStatus.unauthenticated;
    notifyListeners();
    return false;
  }

  Future<bool> facebookLogin({
    required String facebookId,
    String? email,
    String? name,
    String? avatar,
    String? role,
  }) async {
    _clearErrors();
    _status = AuthStatus.loading;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.post(
        ApiEndpoints.facebookLogin,
        data: {
          'facebook_id': facebookId,
          'email': email,
          'name': name,
          'avatar': avatar,
          'role': role ?? 'customer',
        },
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data'];
        final token = data['token'];
        _user = data['user'];
        _isNewUser = data['is_new_user'] ?? false;
        _activeRole = role ?? _user?['role'] ?? 'customer';

        await StorageService.instance.saveAuthToken(token);
        await StorageService.instance.saveUserData(
          id: _user!['id'],
          name: _user!['name'] ?? '',
          phone: _user!['phone'] ?? '',
          role: _activeRole,
        );

        _status = AuthStatus.authenticated;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _handleDioError(e);
      _status = AuthStatus.unauthenticated;
      notifyListeners();
      return false;
    }
    _status = AuthStatus.unauthenticated;
    notifyListeners();
    return false;
  }

  Future<bool> updateCustomerProfile({
    required String address,
    required String emergencyContact,
    required String paymentMethod,
  }) async {
    _clearErrors();
    try {
      final response = await ApiClient.instance.client.put(
        '${ApiEndpoints.baseUrl}/customer/profile',
        data: {
          'default_address': address,
          'emergency_contact': emergencyContact,
          'preferred_payment_method': paymentMethod,
        },
      );
      if (response.statusCode == 200 && response.data['success'] == true) {
        await restoreSession();
        return true;
      }
    } on DioException catch (e) {
      _handleDioError(e);
      notifyListeners();
      return false;
    }
    return false;
  }

  Future<bool> updateWorkerProfile({
    required String bio,
    required int experienceYears,
    required double hourlyRate,
    required int serviceAreaRadius,
    required String address,
    required List<int> skillIds,
  }) async {
    _clearErrors();
    try {
      final response = await ApiClient.instance.client.put(
        ApiEndpoints.workerProfile,
        data: {
          'bio': bio,
          'experience_years': experienceYears,
          'hourly_rate': hourlyRate,
          'service_area_radius_km': serviceAreaRadius,
          'address': address,
          'skill_ids': skillIds,
        },
      );
      if (response.statusCode == 200 && response.data['success'] == true) {
        await restoreSession();
        return true;
      }
    } on DioException catch (e) {
      _handleDioError(e);
      notifyListeners();
      return false;
    }
    return false;
  }

  Future<void> switchRole(String newRole) async {
    _activeRole = newRole;
    await StorageService.instance.saveUserRole(newRole);
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await ApiClient.instance.client.post(ApiEndpoints.logout);
    } catch (_) {}

    await StorageService.instance.clearAll();
    _user = null;
    _status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  void _clearErrors() {
    _errorMessage = null;
    _fieldErrors = null;
  }

  void _handleDioError(DioException e) {
    if (e.response != null) {
      final data = e.response?.data;
      if (data is Map<String, dynamic>) {
        _errorMessage = data['message'] ?? 'Request failed.';
        if (data.containsKey('errors') && data['errors'] is Map) {
          _fieldErrors = {};
          (data['errors'] as Map).forEach((key, value) {
            if (value is List && value.isNotEmpty) {
              _fieldErrors![key.toString()] = value.first.toString();
            }
          });
        }
      } else {
        _errorMessage = 'Server error (${e.response?.statusCode}).';
      }
    } else if (e.type == DioExceptionType.connectionTimeout || e.type == DioExceptionType.receiveTimeout) {
      _errorMessage = 'Network timeout. Please check your connection.';
    } else {
      _errorMessage = 'Network connection failed. Verify internet connection.';
    }
  }
}
