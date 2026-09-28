import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';

class StatusTimelineWidget extends StatelessWidget {
  final List<Map<String, dynamic>> history;
  final String currentStatus;

  const StatusTimelineWidget({
    super.key,
    required this.history,
    required this.currentStatus,
  });

  @override
  Widget build(BuildContext context) {
    if (history.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: AppColors.border),
        ),
        child: Row(
          children: [
            _getStatusIcon(currentStatus),
            const SizedBox(width: 12),
            Text(
              'Current Status: ${formatStatus(currentStatus)}',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
          ],
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Status History Timeline',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            ListView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: history.length,
              itemBuilder: (context, index) {
                final item = history[index];
                final isLast = index == history.length - 1;
                final status = item['to_status'] ?? item['status'] ?? 'REQUESTED';
                final dateStr = item['created_at'] != null
                    ? item['created_at'].toString().substring(0, 10)
                    : '';
                final changedBy = item['changed_by']?['name'] ?? item['notes'] ?? '';

                return IntrinsicHeight(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: getStatusColor(status).withAlpha(30),
                              shape: BoxShape.circle,
                              border: Border.all(color: getStatusColor(status), width: 2),
                            ),
                            child: Icon(_getStatusIconData(status), size: 16, color: getStatusColor(status)),
                          ),
                          if (!isLast)
                            Expanded(
                              child: Container(
                                width: 2,
                                color: AppColors.border,
                                margin: const EdgeInsets.symmetric(vertical: 4),
                              ),
                            ),
                        ],
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Padding(
                          padding: const EdgeInsets.only(bottom: 16.0),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    formatStatus(status),
                                    style: TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: getStatusColor(status),
                                    ),
                                  ),
                                  Text(
                                    dateStr,
                                    style: const TextStyle(fontSize: 11, color: AppColors.textMuted),
                                  ),
                                ],
                              ),
                              if (changedBy.isNotEmpty) ...[
                                const SizedBox(height: 2),
                                Text(
                                  changedBy,
                                  style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  static String formatStatus(String status) {
    return status.replaceAll('_', ' ').toUpperCase();
  }

  static Color getStatusColor(String status) {
    switch (status.toUpperCase()) {
      case 'REQUESTED':
        return Colors.orange;
      case 'ACCEPTED':
        return Colors.blue;
      case 'SCHEDULED':
        return Colors.purple;
      case 'IN_PROGRESS':
      case 'WORK_STARTED':
      case 'ON_THE_WAY':
      case 'ARRIVED':
        return Colors.teal;
      case 'COMPLETED':
      case 'WORK_COMPLETED':
      case 'CUSTOMER_CONFIRMED':
        return Colors.green;
      case 'REJECTED':
      case 'CANCELLED':
        return Colors.red;
      default:
        return AppColors.primary;
    }
  }

  static IconData _getStatusIconData(String status) {
    switch (status.toUpperCase()) {
      case 'REQUESTED':
        return Icons.pending_actions_rounded;
      case 'ACCEPTED':
        return Icons.check_circle_outline_rounded;
      case 'SCHEDULED':
        return Icons.event_rounded;
      case 'IN_PROGRESS':
      case 'WORK_STARTED':
        return Icons.build_circle_rounded;
      case 'COMPLETED':
      case 'WORK_COMPLETED':
        return Icons.verified_rounded;
      case 'REJECTED':
      case 'CANCELLED':
        return Icons.cancel_rounded;
      default:
        return Icons.info_outline_rounded;
    }
  }

  static Widget _getStatusIcon(String status) {
    return Icon(_getStatusIconData(status), color: getStatusColor(status), size: 24);
  }
}
