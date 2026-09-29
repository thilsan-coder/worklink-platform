import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../providers/chat_provider.dart';

class ChatScreen extends StatefulWidget {
  final int conversationId;
  final Map<String, dynamic>? initialConversation;

  const ChatScreen({
    super.key,
    required this.conversationId,
    this.initialConversation,
  });

  @override
  State<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  bool _hasScrolled = false;

  @override
  void initState() {
    super.initState();
    final provider = context.read<ChatProvider>();
    if (widget.initialConversation != null) {
      provider.setActiveConversation(widget.initialConversation!);
    }
    WidgetsBinding.instance.addPostFrameCallback((_) {
      provider.fetchMessages(widget.conversationId).then((_) {
        _scrollToBottom();
      });
      provider.startPolling(widget.conversationId);
      provider.markConversationRead(widget.conversationId);
    });
  }

  @override
  void dispose() {
    context.read<ChatProvider>().stopPolling();
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent + 60,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    }
  }

  Future<void> _handleSendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    _messageController.clear();
    final provider = context.read<ChatProvider>();
    final success = await provider.sendMessage(widget.conversationId, message: text);

    if (success) {
      _scrollToBottom();
    } else if (mounted && provider.messageError != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(provider.messageError!)),
      );
    }
  }

  String _formatTimestamp(String? isoString) {
    if (isoString == null) return '';
    try {
      final date = DateTime.parse(isoString).toLocal();
      final hour = date.hour > 12 ? date.hour - 12 : (date.hour == 0 ? 12 : date.hour);
      final minute = date.minute.toString().padLeft(2, '0');
      final period = date.hour >= 12 ? 'PM' : 'AM';
      return '$hour:$minute $period';
    } catch (_) {
      return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final chatProvider = context.watch<ChatProvider>();
    final authProvider = context.watch<AuthProvider>();
    final currentUserId = authProvider.user?['id'];

    final conv = chatProvider.activeConversation ?? widget.initialConversation ?? {};
    final other = conv['other_participant'] as Map<String, dynamic>? ?? {};
    final otherName = other['name'] ?? 'Chat';
    final jobTitle = conv['job_title'] ?? 'Job';
    final jobStatus = conv['job_status'] ?? '';

    final messages = chatProvider.activeMessages;

    // Auto-scroll when new messages appear if already near bottom
    if (messages.isNotEmpty && !_hasScrolled) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToBottom());
      _hasScrolled = true;
    }

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: Row(
          children: [
            CircleAvatar(
              radius: 18,
              backgroundColor: AppColors.primary.withAlpha(30),
              backgroundImage: (other['avatar'] != null && other['avatar'].toString().isNotEmpty)
                  ? NetworkImage(other['avatar'])
                  : null,
              child: (other['avatar'] == null || other['avatar'].toString().isEmpty)
                  ? Text(
                      otherName.isNotEmpty ? otherName[0].toUpperCase() : 'U',
                      style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary),
                    )
                  : null,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    otherName,
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                    overflow: TextOverflow.ellipsis,
                  ),
                  Row(
                    children: [
                      Container(
                        width: 7,
                        height: 7,
                        decoration: const BoxDecoration(
                          color: Colors.green,
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'Job: $jobTitle',
                        style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
        actions: [
          if (jobStatus.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(right: 12),
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withAlpha(20),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: AppColors.primary.withAlpha(80)),
                  ),
                  child: Text(
                    jobStatus,
                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: AppColors.primary),
                  ),
                ),
              ),
            ),
        ],
      ),
      body: Column(
        children: [
          // Messages list
          Expanded(
            child: chatProvider.isLoadingMessages && messages.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : messages.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.chat_bubble_outline_rounded, size: 56, color: AppColors.textMuted.withAlpha(100)),
                            const SizedBox(height: 12),
                            const Text(
                              'No messages yet',
                              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                            ),
                            const SizedBox(height: 4),
                            const Text(
                              'Send a message to start communicating about this job.',
                              style: TextStyle(color: AppColors.textMuted, fontSize: 13),
                              textAlign: TextAlign.center,
                            ),
                          ],
                        ),
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                        itemCount: messages.length,
                        itemBuilder: (context, index) {
                          final msg = messages[index];
                          final isMe = currentUserId != null && msg['sender_id'] == currentUserId;
                          final isRead = msg['is_read'] == true || msg['read_at'] != null;

                          return _buildMessageBubble(
                            message: msg['message_body'] ?? msg['message'] ?? '',
                            time: _formatTimestamp(msg['created_at']),
                            isMe: isMe,
                            isRead: isRead,
                          );
                        },
                      ),
          ),

          // Bottom message input
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Theme.of(context).scaffoldBackgroundColor,
              border: const Border(top: BorderSide(color: AppColors.border, width: 0.5)),
            ),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(24),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: TextField(
                        controller: _messageController,
                        textCapitalization: TextCapitalization.sentences,
                        decoration: const InputDecoration(
                          hintText: 'Type a message...',
                          hintStyle: TextStyle(fontSize: 14, color: AppColors.textMuted),
                          border: InputBorder.none,
                          isDense: true,
                          contentPadding: EdgeInsets.symmetric(vertical: 10),
                        ),
                        onSubmitted: (_) => _handleSendMessage(),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    decoration: const BoxDecoration(
                      color: AppColors.primary,
                      shape: BoxShape.circle,
                    ),
                    child: IconButton(
                      icon: chatProvider.isSendingMessage
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.send_rounded, color: Colors.white, size: 20),
                      onPressed: chatProvider.isSendingMessage ? null : _handleSendMessage,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMessageBubble({
    required String message,
    required String time,
    required bool isMe,
    required bool isRead,
  }) {
    return Align(
      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
        constraints: BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.76),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isMe ? AppColors.primary : AppColors.surface,
          borderRadius: BorderRadius.only(
            topLeft: const Radius.circular(16),
            topRight: const Radius.circular(16),
            bottomLeft: Radius.circular(isMe ? 16 : 4),
            bottomRight: Radius.circular(isMe ? 4 : 16),
          ),
          border: isMe ? null : Border.all(color: AppColors.border.withAlpha(80)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withAlpha(8),
              blurRadius: 3,
              offset: const Offset(0, 1),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
          children: [
            Text(
              message,
              style: TextStyle(
                color: isMe ? Colors.white : AppColors.textPrimary,
                fontSize: 14.5,
                height: 1.3,
              ),
            ),
            const SizedBox(height: 4),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  time,
                  style: TextStyle(
                    color: isMe ? Colors.white70 : AppColors.textMuted,
                    fontSize: 10.5,
                  ),
                ),
                if (isMe) ...[
                  const SizedBox(width: 4),
                  Icon(
                    isRead ? Icons.done_all_rounded : Icons.done_rounded,
                    size: 14,
                    color: isRead ? Colors.lightBlueAccent : Colors.white70,
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }
}
