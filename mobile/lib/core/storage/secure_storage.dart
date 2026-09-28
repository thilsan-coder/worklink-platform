import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

class StorageService {
  static final StorageService instance = StorageService._internal();
  factory StorageService() => instance;
  StorageService._internal();

  final _secureStorage = const FlutterSecureStorage(
    aOptions: AndroidOptions(resetOnError: true),
  );

  static const String _keyAuthToken = 'auth_token';
  static const String _keyActiveRole = 'active_role';
  static const String _keyUserId = 'user_id';
  static const String _keyUserName = 'user_name';
  static const String _keyUserPhone = 'user_phone';

  Future<void> saveAuthToken(String token) async {
    await _secureStorage.write(key: _keyAuthToken, value: token);
  }

  Future<String?> getAuthToken() async {
    return await _secureStorage.read(key: _keyAuthToken);
  }

  Future<void> clearAuthToken() async {
    await _secureStorage.delete(key: _keyAuthToken);
  }

  Future<void> saveUserRole(String role) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_keyActiveRole, role);
  }

  Future<String> getUserRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_keyActiveRole) ?? 'customer';
  }

  Future<void> saveUserData({
    required int id,
    required String name,
    required String phone,
    required String role,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setInt(_keyUserId, id);
    await prefs.setString(_keyUserName, name);
    await prefs.setString(_keyUserPhone, phone);
    await prefs.setString(_keyActiveRole, role);
  }

  Future<Map<String, dynamic>> getUserData() async {
    final prefs = await SharedPreferences.getInstance();
    return {
      'id': prefs.getInt(_keyUserId),
      'name': prefs.getString(_keyUserName),
      'phone': prefs.getString(_keyUserPhone),
      'role': prefs.getString(_keyActiveRole) ?? 'customer',
    };
  }

  Future<void> clearAll() async {
    await _secureStorage.deleteAll();
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}
