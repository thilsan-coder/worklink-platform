import 'payment_model.dart';

class WorkerEarningsModel {
  final int workerId;
  final String workerName;
  final double totalEarnings;
  final String currency;
  final String formattedTotal;
  final int paidJobsCount;
  final List<PaymentModel> payments;

  WorkerEarningsModel({
    required this.workerId,
    required this.workerName,
    required this.totalEarnings,
    required this.currency,
    required this.formattedTotal,
    required this.paidJobsCount,
    this.payments = const [],
  });

  factory WorkerEarningsModel.fromJson(Map<String, dynamic> json) {
    final summary = json['summary'] is Map<String, dynamic> ? json['summary'] as Map<String, dynamic> : json;

    final double total = (summary['total_earnings'] as num?)?.toDouble() ?? 0.0;
    final String curr = summary['currency']?.toString() ?? 'LKR';
    final int count = summary['paid_jobs_count'] is int
        ? summary['paid_jobs_count']
        : int.tryParse(summary['paid_jobs_count']?.toString() ?? '0') ?? 0;

    List<PaymentModel> list = [];
    if (json['payments'] is List) {
      list = (json['payments'] as List)
          .map((item) => PaymentModel.fromJson(item as Map<String, dynamic>))
          .toList();
    }

    return WorkerEarningsModel(
      workerId: summary['worker_id'] is int ? summary['worker_id'] : int.tryParse(summary['worker_id']?.toString() ?? '0') ?? 0,
      workerName: summary['worker_name']?.toString() ?? 'Worker',
      totalEarnings: total,
      currency: curr,
      formattedTotal: summary['formatted_total']?.toString() ?? '$curr ${total.toStringAsFixed(2)}',
      paidJobsCount: count,
      payments: list,
    );
  }
}
