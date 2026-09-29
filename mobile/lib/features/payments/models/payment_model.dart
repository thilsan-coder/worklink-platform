import 'package:flutter/material.dart';
import '../../../core/theme/app_colors.dart';
import 'transaction_model.dart';

class PaymentModel {
  final int id;
  final int jobId;
  final int customerId;
  final int workerId;
  final double amount;
  final String currency;
  final String formattedAmount;
  final String status;
  final String paymentMethod;
  final String? gateway;
  final String? gatewayTransactionId;
  final String? transactionReference;
  final DateTime? paidAt;
  final String? failureReason;
  final DateTime? refundedAt;
  final String? refundReason;
  final Map<String, dynamic>? job;
  final Map<String, dynamic>? customer;
  final Map<String, dynamic>? worker;
  final List<TransactionModel> transactions;
  final DateTime createdAt;

  PaymentModel({
    required this.id,
    required this.jobId,
    required this.customerId,
    required this.workerId,
    required this.amount,
    required this.currency,
    required this.formattedAmount,
    required this.status,
    required this.paymentMethod,
    this.gateway,
    this.gatewayTransactionId,
    this.transactionReference,
    this.paidAt,
    this.failureReason,
    this.refundedAt,
    this.refundReason,
    this.job,
    this.customer,
    this.worker,
    this.transactions = const [],
    required this.createdAt,
  });

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    DateTime parsedCreatedAt;
    try {
      parsedCreatedAt = json['created_at'] != null
          ? DateTime.parse(json['created_at'].toString())
          : DateTime.now();
    } catch (_) {
      parsedCreatedAt = DateTime.now();
    }

    DateTime? parsedPaidAt;
    if (json['paid_at'] != null) {
      try {
        parsedPaidAt = DateTime.parse(json['paid_at'].toString());
      } catch (_) {
        parsedPaidAt = null;
      }
    }

    DateTime? parsedRefundedAt;
    if (json['refunded_at'] != null) {
      try {
        parsedRefundedAt = DateTime.parse(json['refunded_at'].toString());
      } catch (_) {
        parsedRefundedAt = null;
      }
    }

    List<TransactionModel> parsedTransactions = [];
    if (json['transactions'] is List) {
      parsedTransactions = (json['transactions'] as List)
          .map((t) => TransactionModel.fromJson(t as Map<String, dynamic>))
          .toList();
    }

    final double parsedAmount = (json['amount'] as num?)?.toDouble() ?? 0.0;
    final String parsedCurrency = json['currency']?.toString() ?? 'LKR';

    return PaymentModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      jobId: json['job_id'] is int ? json['job_id'] : int.tryParse(json['job_id'].toString()) ?? 0,
      customerId: json['customer_id'] is int ? json['customer_id'] : int.tryParse(json['customer_id'].toString()) ?? 0,
      workerId: json['worker_id'] is int ? json['worker_id'] : int.tryParse(json['worker_id'].toString()) ?? 0,
      amount: parsedAmount,
      currency: parsedCurrency,
      formattedAmount: json['formatted_amount']?.toString() ?? '$parsedCurrency ${parsedAmount.toStringAsFixed(2)}',
      status: json['status']?.toString() ?? 'PENDING',
      paymentMethod: json['payment_method']?.toString() ?? 'TEST_PAYMENT',
      gateway: json['gateway']?.toString(),
      gatewayTransactionId: json['gateway_transaction_id']?.toString(),
      transactionReference: json['transaction_reference']?.toString(),
      paidAt: parsedPaidAt,
      failureReason: json['failure_reason']?.toString(),
      refundedAt: parsedRefundedAt,
      refundReason: json['refund_reason']?.toString(),
      job: json['job'] is Map<String, dynamic> ? json['job'] as Map<String, dynamic> : null,
      customer: json['customer'] is Map<String, dynamic> ? json['customer'] as Map<String, dynamic> : null,
      worker: json['worker'] is Map<String, dynamic> ? json['worker'] as Map<String, dynamic> : null,
      transactions: parsedTransactions,
      createdAt: parsedCreatedAt,
    );
  }

  bool get isPaid => status.toUpperCase() == 'PAID';
  bool get isFailed => status.toUpperCase() == 'FAILED';
  bool get isPending => status.toUpperCase() == 'PENDING' || status.toUpperCase() == 'PROCESSING';
  bool get isRefunded => status.toUpperCase() == 'REFUNDED' || status.toUpperCase() == 'REFUND_PENDING';

  Color get statusColor {
    switch (status.toUpperCase()) {
      case 'PAID':
        return const Color(0xFF10B981); // Emerald
      case 'FAILED':
        return const Color(0xFFEF4444); // Red
      case 'PROCESSING':
      case 'PENDING':
        return const Color(0xFFF59E0B); // Amber
      case 'REFUNDED':
        return const Color(0xFF8B5CF6); // Purple
      default:
        return AppColors.textSecondary;
    }
  }

  IconData get statusIcon {
    switch (status.toUpperCase()) {
      case 'PAID':
        return Icons.check_circle_rounded;
      case 'FAILED':
        return Icons.error_rounded;
      case 'PROCESSING':
      case 'PENDING':
        return Icons.hourglass_top_rounded;
      case 'REFUNDED':
        return Icons.replay_rounded;
      default:
        return Icons.payment_rounded;
    }
  }
}
