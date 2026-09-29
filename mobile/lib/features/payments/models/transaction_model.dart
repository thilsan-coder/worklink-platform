class TransactionModel {
  final int id;
  final int paymentId;
  final int jobId;
  final int customerId;
  final int workerId;
  final String type;
  final double amount;
  final String currency;
  final String formattedAmount;
  final String status;
  final String reference;
  final Map<String, dynamic>? job;
  final Map<String, dynamic>? customer;
  final Map<String, dynamic>? worker;
  final DateTime createdAt;

  TransactionModel({
    required this.id,
    required this.paymentId,
    required this.jobId,
    required this.customerId,
    required this.workerId,
    required this.type,
    required this.amount,
    required this.currency,
    required this.formattedAmount,
    required this.status,
    required this.reference,
    this.job,
    this.customer,
    this.worker,
    required this.createdAt,
  });

  factory TransactionModel.fromJson(Map<String, dynamic> json) {
    DateTime parsedCreatedAt;
    try {
      parsedCreatedAt = json['created_at'] != null
          ? DateTime.parse(json['created_at'].toString())
          : DateTime.now();
    } catch (_) {
      parsedCreatedAt = DateTime.now();
    }

    final double parsedAmount = (json['amount'] as num?)?.toDouble() ?? 0.0;
    final String parsedCurrency = json['currency']?.toString() ?? 'LKR';

    return TransactionModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      paymentId: json['payment_id'] is int ? json['payment_id'] : int.tryParse(json['payment_id'].toString()) ?? 0,
      jobId: json['job_id'] is int ? json['job_id'] : int.tryParse(json['job_id'].toString()) ?? 0,
      customerId: json['customer_id'] is int ? json['customer_id'] : int.tryParse(json['customer_id'].toString()) ?? 0,
      workerId: json['worker_id'] is int ? json['worker_id'] : int.tryParse(json['worker_id'].toString()) ?? 0,
      type: json['type']?.toString() ?? 'PAYMENT',
      amount: parsedAmount,
      currency: parsedCurrency,
      formattedAmount: json['formatted_amount']?.toString() ?? '$parsedCurrency ${parsedAmount.toStringAsFixed(2)}',
      status: json['status']?.toString() ?? 'COMPLETED',
      reference: json['reference']?.toString() ?? '',
      job: json['job'] is Map<String, dynamic> ? json['job'] as Map<String, dynamic> : null,
      customer: json['customer'] is Map<String, dynamic> ? json['customer'] as Map<String, dynamic> : null,
      worker: json['worker'] is Map<String, dynamic> ? json['worker'] as Map<String, dynamic> : null,
      createdAt: parsedCreatedAt,
    );
  }
}
