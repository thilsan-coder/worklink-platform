import 'dart:async';
import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/notification_model.dart';

class NotificationProvider extends ChangeNotifier {
  List<NotificationModel> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  bool _isLoadingMore = false;
  String? _errorMessage;
  int _currentPage = 1;
  int _lastPage = 1;
  Timer? _pollTimer;

  List<NotificationModel> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  bool get isLoadingMore => _isLoadingMore;
  String? get errorMessage => _errorMessage;
  bool get hasMore => _currentPage < _lastPage;

  NotificationProvider() {
    startPolling();
  }

  @override
  void dispose() {
    stopPolling();
    super.dispose();
  }

  /// Start background polling for unread count every 20 seconds
  void startPolling() {
    _pollTimer?.cancel();
    fetchUnreadCount();
    _pollTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      fetchUnreadCount();
    });
  }

  /// Stop background polling
  void stopPolling() {
    _pollTimer?.cancel();
    _pollTimer = null;
  }

  /// Fetch lightweight unread notification count
  Future<void> fetchUnreadCount() async {
    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.notificationsUnreadCount);
      if (response.statusCode == 200 && response.data != null) {
        final count = response.data['count'] ?? 0;
        final int parsedCount = count is int ? count : int.tryParse(count.toString()) ?? 0;
        if (_unreadCount != parsedCount) {
          _unreadCount = parsedCount;
          notifyListeners();
        }
      }
    } catch (_) {
      // Silently ignore polling errors to avoid disrupting UI
    }
  }

  /// Fetch full notifications list (paginated, latest first)
  Future<void> fetchNotifications({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
    }

    if (_currentPage == 1) {
      _isLoading = true;
      _errorMessage = null;
      notifyListeners();
    } else {
      _isLoadingMore = true;
      notifyListeners();
    }

    try {
      final response = await ApiClient.instance.client.get(
        ApiEndpoints.notifications,
        queryParameters: {'page': _currentPage, 'per_page': 20},
      );

      if (response.statusCode == 200 && response.data != null) {
        final data = response.data;
        final rawItems = data['data'];

        List<NotificationModel> loadedItems = [];
        if (rawItems is List) {
          loadedItems = rawItems.map((item) => NotificationModel.fromJson(item as Map<String, dynamic>)).toList();
        }

        if (_currentPage == 1) {
          _notifications = loadedItems;
        } else {
          _notifications.addAll(loadedItems);
        }

        _currentPage = (data['current_page'] as num?)?.toInt() ?? _currentPage;
        _lastPage = (data['last_page'] as num?)?.toInt() ?? _currentPage;

        if (data['unread_count'] != null) {
          final rawUnread = data['unread_count'];
          _unreadCount = rawUnread is int ? rawUnread : int.tryParse(rawUnread.toString()) ?? 0;
        } else {
          _unreadCount = _notifications.where((n) => !n.isRead).length;
        }
      }
    } catch (e) {
      _errorMessage = 'Failed to load notifications: $e';
    } finally {
      _isLoading = false;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  /// Load next page of notifications
  Future<void> loadMore() async {
    if (_isLoading || _isLoadingMore || !hasMore) return;
    _currentPage++;
    await fetchNotifications(refresh: false);
  }

  /// Mark a specific notification as read
  Future<bool> markAsRead(NotificationModel item) async {
    if (item.isRead) return true;

    // Optimistic UI update
    item.isRead = true;
    if (_unreadCount > 0) {
      _unreadCount--;
    }
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.patch(ApiEndpoints.notificationRead(item.id));
      if (response.statusCode == 200) {
        return true;
      }
    } catch (_) {
      // Fallback try POST if server route prefers POST
      try {
        await ApiClient.instance.client.post(ApiEndpoints.notificationRead(item.id));
      } catch (_) {}
    }
    return true;
  }

  /// Mark all notifications as read
  Future<bool> markAllAsRead() async {
    // Optimistic UI update
    for (final notif in _notifications) {
      notif.isRead = true;
    }
    _unreadCount = 0;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.patch(ApiEndpoints.notificationsReadAll);
      if (response.statusCode == 200) {
        return true;
      }
    } catch (_) {
      // Fallback try POST
      try {
        await ApiClient.instance.client.post(ApiEndpoints.notificationsReadAll);
      } catch (_) {}
    }
    return true;
  }

  /// Delete a single notification
  Future<bool> deleteNotification(NotificationModel item) async {
    final wasUnread = !item.isRead;
    _notifications.removeWhere((n) => n.id == item.id);
    if (wasUnread && _unreadCount > 0) {
      _unreadCount--;
    }
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.delete(ApiEndpoints.notificationDelete(item.id));
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  /// Clear all notifications
  Future<bool> clearAll() async {
    _notifications.clear();
    _unreadCount = 0;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.delete(ApiEndpoints.notificationsClearAll);
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }
}
