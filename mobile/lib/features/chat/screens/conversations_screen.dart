import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../providers/chat_provider.dart';
import 'chat_screen.dart';

class ConversationsScreen extends StatefulWidget {
  const ConversationsScreen({super.key});

  @override
  State<ConversationsScreen> createState() => _ConversationsScreenState();
}

class _ConversationsScreenState extends State<ConversationsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ChatProvider>().fetchConversations();
    });
  }

  String _formatDate(String? isoString) {
    if (isoString == null) return '';
    try {
      final date = DateTime.parse(isoString).toLocal();
      final now = DateTime.now();
      if (date.year == now.year && date.month == now.month && date.day == now.day) {
        final hour = date.hour > 12 ? date.hour - 12 : (date.hour == 0 ? 12 : date.hour);
        final minute = date.minute.toString().padLeft(2, '0');
        final period = date.hour >= 12 ? 'PM' : 'AM';
        return '$hour:$minute $period';
      }
      return '${date.day}/${date.month}';
    } catch (_) {
      return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    final chatProvider = context.watch<ChatProvider>();
    final conversations = chatProvider.conversations;

    if (chatProvider.isLoadingConversations && conversations.isEmpty) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (chatProvider.conversationError != null && conversations.isEmpty) {
      return Scaffold(
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
              const SizedBox(height: 12),
              Text(chatProvider.conversationError!),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () => chatProvider.fetchConversations(),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (conversations.isEmpty) {
      return Scaffold(
        body: RefreshIndicator(
          onRefresh: () => chatProvider.fetchConversations(),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              SizedBox(height: MediaQuery.of(context).size.height * 0.25),
              Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.forum_outlined, size: 64, color: AppColors.textMuted.withAlpha(120)),
                    const SizedBox(height: 16),
                    const Text(
                      'No Active Conversations',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'Chats will automatically appear here once you request or receive jobs.',
                      style: TextStyle(color: AppColors.textMuted, fontSize: 13),
                      textAlign: TextAlign.center,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () => chatProvider.fetchConversations(),
        child: ListView.separated(
          padding: const EdgeInsets.symmetric(vertical: 8),
          itemCount: conversations.length,
          separatorBuilder: (_, __) => const Divider(height: 1, indent: 72),
          itemBuilder: (context, index) {
            final conv = conversations[index] as Map<String, dynamic>;
            final conversationId = conv['id'] ?? conv['conversation_id'];
            final other = conv['other_participant'] as Map<String, dynamic>? ?? {};
            final otherName = other['name'] ?? 'User';
            final latestMsg = conv['latest_message'] as Map<String, dynamic>?;
            final unreadCount = conv['unread_count'] as int? ?? 0;
            final jobTitle = conv['job_title'] ?? 'Job';
            final jobStatus = conv['job_status'] ?? '';

            return ListTile(
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              leading: Stack(
                children: [
                  CircleAvatar(
                    radius: 26,
                    backgroundColor: AppColors.primary.withAlpha(30),
                    backgroundImage: (other['avatar'] != null && other['avatar'].toString().isNotEmpty)
                        ? NetworkImage(other['avatar'])
                        : null,
                    child: (other['avatar'] == null || other['avatar'].toString().isEmpty)
                        ? Text(
                            otherName.isNotEmpty ? otherName[0].toUpperCase() : 'U',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: AppColors.primary),
                          )
                        : null,
                  ),
                  if (unreadCount > 0)
                    Positioned(
                      right: 0,
                      top: 0,
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: const BoxDecoration(
                          color: AppColors.primary,
                          shape: BoxShape.circle,
                        ),
                        constraints: const BoxConstraints(minWidth: 16, minHeight: 16),
                        child: Text(
                          '$unreadCount',
                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold),
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ),
                ],
              ),
              title: Row(
                children: [
                  Expanded(
                    child: Text(
                      otherName,
                      style: TextStyle(
                        fontWeight: unreadCount > 0 ? FontWeight.bold : FontWeight.w600,
                        fontSize: 15,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (latestMsg != null)
                    Text(
                      _formatDate(latestMsg['created_at']),
                      style: TextStyle(
                        fontSize: 12,
                        color: unreadCount > 0 ? AppColors.primary : AppColors.textMuted,
                        fontWeight: unreadCount > 0 ? FontWeight.bold : FontWeight.normal,
                      ),
                    ),
                ],
              ),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(4),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Text(
                          jobTitle,
                          style: const TextStyle(fontSize: 10.5, color: AppColors.textSecondary, fontWeight: FontWeight.w500),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (jobStatus.isNotEmpty) ...[
                        const SizedBox(width: 4),
                        Text('•', style: const TextStyle(color: AppColors.textMuted, fontSize: 11)),
                        const SizedBox(width: 4),
                        Text(
                          jobStatus,
                          style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 3),
                  Text(
                    latestMsg != null
                        ? (latestMsg['message'] ?? latestMsg['message_body'] ?? 'Attachment')
                        : 'No messages yet',
                    style: TextStyle(
                      fontSize: 13,
                      color: unreadCount > 0 ? AppColors.textPrimary : AppColors.textMuted,
                      fontWeight: unreadCount > 0 ? FontWeight.w600 : FontWeight.normal,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
              onTap: () {
                Navigator.of(context).push(
                  MaterialPageRoute(
                    builder: (_) => ChatScreen(
                      conversationId: conversationId,
                      initialConversation: conv,
                    ),
                  ),
                ).then((_) {
                  chatProvider.fetchConversations();
                });
              },
            );
          },
        ),
      ),
    );
  }
}
