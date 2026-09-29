import 'payment_model.dart';

class JobPaymentDetailsModel {
  final int jobId;
  final String jobNumber;
  final String jobTitle;
  final String jobStatus;
  final Map<String, dynamic> customer;
  final Map<String, dynamic> worker;
  final double amount;
  final String currency;
  final String formattedAmount;
  final bool isEligibleForPayment;
  final bool isPaid;
  final PaymentModel? payment;
  final bool testMode;

  JobPaymentDetailsModel({
    required this.jobId,
    required this.jobNumber,
    required this.jobTitle,
    required this.jobStatus,
    required this.customer,
    required this.worker,
    required this.amount,
    required this.currency,
    required this.formattedAmount,
    required this.isEligibleForPayment,
    required this.isPaid,
    this.payment,
    required this.testMode,
  });

  factory JobPaymentDetailsModel.fromJson(Map<String, dynamic> json) {
    final double parsedAmount = (json['amount'] as num?)?.toDouble() ?? 0.0;
    final String parsedCurrency = json['currency']?.toString() ?? 'LKR';

    PaymentModel? parsedPayment;
    if (json['payment'] is Map<String, dynamic>) {
      parsedPayment = PaymentModel.fromJson(json['payment'] as Map<String, dynamic>);
    }

    return JobPaymentDetailsModel(
      jobId: json['job_id'] is int ? json['job_id'] : int.tryParse(json['job_id'].toString()) ?? 0,
      jobNumber: json['job_number']?.toString() ?? '',
      jobTitle: json['job_title']?.toString() ?? '',
      jobStatus: json['job_status']?.toString() ?? '',
      customer: json['customer'] is Map<String, dynamic> ? json['customer'] as Map<String, dynamic> : {},
      worker: json['worker'] is Map<String, dynamic> ? json['worker'] as Map<String, dynamic> : {},
      amount: parsedAmount,
      currency: parsedCurrency,
      formattedAmount: json['formatted_amount']?.toString() ?? '$parsedCurrency ${parsedAmount.toStringAsFixed(2)}',
      isEligibleForPayment: json['is_eligible_for_payment'] == true,
      isPaid: json['is_paid'] == true,
      payment: parsedPayment,
      testMode: json['test_mode'] == true,
    );
  }
}
