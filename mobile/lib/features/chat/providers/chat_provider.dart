import 'dart:async';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';

class ChatProvider extends ChangeNotifier {
  List<dynamic> _conversations = [];
  bool _isLoadingConversations = false;
  String? _conversationError;

  List<dynamic> _activeMessages = [];
  Map<String, dynamic>? _activeConversation;
  bool _isLoadingMessages = false;
  bool _isSendingMessage = false;
  String? _messageError;

  Timer? _pollingTimer;

  // Getters
  List<dynamic> get conversations => _conversations;
  bool get isLoadingConversations => _isLoadingConversations;
  String? get conversationError => _conversationError;

  List<dynamic> get activeMessages => _activeMessages;
  Map<String, dynamic>? get activeConversation => _activeConversation;
  bool get isLoadingMessages => _isLoadingMessages;
  bool get isSendingMessage => _isSendingMessage;
  String? get messageError => _messageError;

  /// Total unread message count across all conversations
  int get totalUnreadCount {
    int sum = 0;
    for (final c in _conversations) {
      if (c is Map && c['unread_count'] is int) {
        sum += c['unread_count'] as int;
      }
    }
    return sum;
  }

  /// Fetch all active conversations
  Future<void> fetchConversations() async {
    _isLoadingConversations = true;
    _conversationError = null;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.conversations);
      if (response.statusCode == 200 && response.data['success'] == true) {
        _conversations = response.data['data'] ?? [];
      }
    } on DioException catch (e) {
      _conversationError = e.response?.data['message'] ?? 'Failed to load conversations';
    } catch (e) {
      _conversationError = 'An unexpected error occurred';
    } finally {
      _isLoadingConversations = false;
      notifyListeners();
    }
  }

  /// Get or initialize a conversation for a specific job
  Future<Map<String, dynamic>?> getOrCreateJobConversation(int jobId) async {
    try {
      final response = await ApiClient.instance.client.get(ApiEndpoints.jobConversation(jobId));
      if (response.statusCode == 200 && response.data['success'] == true) {
        final data = response.data['data'] as Map<String, dynamic>;
        _activeConversation = data;
        notifyListeners();
        return data;
      }
    } on DioException catch (e) {
      _messageError = e.response?.data['message'] ?? 'Unable to start chat for this job';
    } catch (e) {
      _messageError = 'Failed to load job chat';
    }
    return null;
  }

  /// Set the active conversation directly
  void setActiveConversation(Map<String, dynamic> conversation) {
    _activeConversation = conversation;
    _activeMessages = [];
    notifyListeners();
  }

  /// Fetch message history for a conversation
  Future<void> fetchMessages(int conversationId, {bool isPolling = false}) async {
    if (!isPolling) {
      _isLoadingMessages = true;
      _messageError = null;
      notifyListeners();
    }

    try {
      final response = await ApiClient.instance.client.get(
        ApiEndpoints.conversationMessages(conversationId),
        queryParameters: {'per_page': 100},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final paginatedData = response.data['data'];
        final List<dynamic> messageList = paginatedData is Map ? (paginatedData['data'] ?? []) : (paginatedData ?? []);

        // If polling and length unchanged, check if any read status updated
        if (isPolling) {
          if (messageList.length != _activeMessages.length ||
              (messageList.isNotEmpty && _activeMessages.isNotEmpty &&
               messageList.last['id'] != _activeMessages.last['id'])) {
            _activeMessages = messageList;
            notifyListeners();
          } else {
            // Update read statuses silently if any
            _activeMessages = messageList;
            notifyListeners();
          }
        } else {
          _activeMessages = messageList;
        }

        // Mark unread count locally for this conversation
        final convIndex = _conversations.indexWhere((c) => (c['id'] ?? c['conversation_id']) == conversationId);
        if (convIndex != -1) {
          final updated = Map<String, dynamic>.from(_conversations[convIndex]);
          updated['unread_count'] = 0;
          _conversations[convIndex] = updated;
        }
      }
    } on DioException catch (e) {
      if (!isPolling) {
        _messageError = e.response?.data['message'] ?? 'Failed to load messages';
      }
    } catch (e) {
      if (!isPolling) {
        _messageError = 'An error occurred loading messages';
      }
    } finally {
      if (!isPolling) {
        _isLoadingMessages = false;
        notifyListeners();
      }
    }
  }

  /// Send a message in a conversation
  Future<bool> sendMessage(int conversationId, {required String message, String messageType = 'text'}) async {
    if (message.trim().isEmpty) return false;

    _isSendingMessage = true;
    notifyListeners();

    try {
      final response = await ApiClient.instance.client.post(
        ApiEndpoints.conversationMessages(conversationId),
        data: {
          'message': message.trim(),
          'message_body': message.trim(),
          'message_type': messageType,
        },
      );

      if (response.statusCode == 201 && response.data['success'] == true) {
        final newMessage = response.data['data'];
        _activeMessages.add(newMessage);

        // Update latest message in conversations list
        final convIndex = _conversations.indexWhere((c) => (c['id'] ?? c['conversation_id']) == conversationId);
        if (convIndex != -1) {
          final updated = Map<String, dynamic>.from(_conversations[convIndex]);
          updated['latest_message'] = {
            'id': newMessage['id'],
            'message': newMessage['message_body'] ?? newMessage['message'],
            'message_type': newMessage['message_type'] ?? 'text',
            'sender_id': newMessage['sender_id'],
            'created_at': newMessage['created_at'],
          };
          _conversations[convIndex] = updated;
        }

        _isSendingMessage = false;
        notifyListeners();
        return true;
      }
    } on DioException catch (e) {
      _messageError = e.response?.data['message'] ?? 'Failed to send message';
    } catch (e) {
      _messageError = 'Error sending message';
    } finally {
      _isSendingMessage = false;
      notifyListeners();
    }
    return false;
  }

  /// Mark entire conversation as read
  Future<void> markConversationRead(int conversationId) async {
    try {
      await ApiClient.instance.client.post(ApiEndpoints.conversationRead(conversationId));
      final convIndex = _conversations.indexWhere((c) => (c['id'] ?? c['conversation_id']) == conversationId);
      if (convIndex != -1) {
        final updated = Map<String, dynamic>.from(_conversations[convIndex]);
        updated['unread_count'] = 0;
        _conversations[convIndex] = updated;
        notifyListeners();
      }
    } catch (_) {}
  }

  /// Start real-time polling while active in chat
  void startPolling(int conversationId) {
    stopPolling();
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (_) {
      fetchMessages(conversationId, isPolling: true);
    });
  }

  /// Stop polling timer
  void stopPolling() {
    _pollingTimer?.cancel();
    _pollingTimer = null;
  }

  @override
  void dispose() {
    stopPolling();
    super.dispose();
  }
}
