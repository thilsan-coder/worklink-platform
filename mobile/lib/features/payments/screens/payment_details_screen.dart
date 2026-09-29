import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../../jobs/screens/job_details_screen.dart';
import '../models/payment_model.dart';
import '../providers/payment_provider.dart';
import 'customer_payment_screen.dart';

class PaymentDetailsScreen extends StatefulWidget {
  final int paymentId;
  final PaymentModel? initialPayment;

  const PaymentDetailsScreen({
    super.key,
    required this.paymentId,
    this.initialPayment,
  });

  @override
  State<PaymentDetailsScreen> createState() => _PaymentDetailsScreenState();
}

class _PaymentDetailsScreenState extends State<PaymentDetailsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PaymentProvider>().fetchPaymentDetails(widget.paymentId);
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PaymentProvider>();
    final payment = provider.selectedPayment ?? widget.initialPayment;
    final isLoading = provider.isLoadingPaymentDetails;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Payment Receipt'),
      ),
      body: isLoading && payment == null
          ? const Center(child: CircularProgressIndicator())
          : payment == null
              ? const Center(child: Text('Payment details not found.'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Status Banner
                      Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: payment.statusColor.withAlpha(25),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: payment.statusColor.withAlpha(80)),
                        ),
                        child: Column(
                          children: [
                            Icon(payment.statusIcon, size: 52, color: payment.statusColor),
                            const SizedBox(height: 10),
                            Text(
                              payment.status.toUpperCase(),
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: payment.statusColor,
                                letterSpacing: 1,
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              payment.formattedAmount,
                              style: const TextStyle(
                                fontSize: 26,
                                fontWeight: FontWeight.bold,
                                color: AppColors.textPrimary,
                              ),
                            ),
                            if (payment.paidAt != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                'Paid on ${payment.paidAt!.year}-${payment.paidAt!.month.toString().padLeft(2, '0')}-${payment.paidAt!.day.toString().padLeft(2, '0')} at ${payment.paidAt!.hour.toString().padLeft(2, '0')}:${payment.paidAt!.minute.toString().padLeft(2, '0')}',
                                style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                              ),
                            ],
                            if (payment.failureReason != null) ...[
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: AppColors.error.withAlpha(20),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  payment.failureReason!,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(fontSize: 12, color: AppColors.error, fontWeight: FontWeight.w500),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Payment Info Card
                      Card(
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'TRANSACTION DETAILS',
                                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textSecondary),
                              ),
                              const SizedBox(height: 12),
                              if (payment.transactionReference != null)
                                _buildDetailRow('Transaction Ref', payment.transactionReference!),
                              _buildDetailRow('Payment Method', payment.paymentMethod),
                              if (payment.gatewayTransactionId != null)
                                _buildDetailRow('Gateway Txn ID', payment.gatewayTransactionId!),
                              _buildDetailRow('Currency', payment.currency),
                              _buildDetailRow('Created Date', payment.createdAt.toString().substring(0, 16)),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Job & Participants Info Card
                      Card(
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'SERVICE & PARTICIPANTS',
                                style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textSecondary),
                              ),
                              const SizedBox(height: 12),
                              _buildDetailRow('Job Title', payment.job?['title'] ?? 'Job #${payment.jobId}'),
                              _buildDetailRow('Customer', payment.customer?['name'] ?? 'Customer #${payment.customerId}'),
                              _buildDetailRow('Worker', payment.worker?['name'] ?? 'Worker #${payment.workerId}'),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Actions
                      if (payment.isFailed)
                        FilledButton.icon(
                          style: FilledButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                          ),
                          onPressed: () {
                            Navigator.of(context).pushReplacement(
                              MaterialPageRoute(
                                builder: (_) => CustomerPaymentScreen(jobId: payment.jobId),
                              ),
                            );
                          },
                          icon: const Icon(Icons.replay_rounded),
                          label: const Text('Retry Payment', style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      if (payment.isFailed) const SizedBox(height: 12),

                      OutlinedButton.icon(
                        style: OutlinedButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        onPressed: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => JobDetailsScreen(jobId: payment.jobId),
                            ),
                          );
                        },
                        icon: const Icon(Icons.assignment_outlined),
                        label: const Text('View Job Details'),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 14, color: AppColors.textSecondary)),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}
