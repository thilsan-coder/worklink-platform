import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/theme/app_colors.dart';
import '../models/job_payment_details_model.dart';
import '../providers/payment_provider.dart';
import 'payment_details_screen.dart';

class CustomerPaymentScreen extends StatefulWidget {
  final int jobId;
  final JobPaymentDetailsModel? initialDetails;

  const CustomerPaymentScreen({
    super.key,
    required this.jobId,
    this.initialDetails,
  });

  @override
  State<CustomerPaymentScreen> createState() => _CustomerPaymentScreenState();
}

class _CustomerPaymentScreenState extends State<CustomerPaymentScreen> {
  String _selectedMethod = 'TEST_PAYMENT';
  bool _simulateFailure = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PaymentProvider>().fetchJobPaymentDetails(widget.jobId);
    });
  }

  void _onPayPressed(JobPaymentDetailsModel details) async {
    final provider = context.read<PaymentProvider>();
    final nav = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);

    final payment = await provider.processPayment(
      widget.jobId,
      paymentMethod: _selectedMethod,
      simulateFailure: _simulateFailure,
    );

    if (!mounted) return;

    if (payment != null && payment.isPaid) {
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          icon: const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 56),
          title: const Text('Payment Successful!'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                details.formattedAmount,
                style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: AppColors.primary),
              ),
              const SizedBox(height: 8),
              Text(
                'Payment for "${details.jobTitle}" was completed successfully.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              if (payment.transactionReference != null) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Text(
                    'Ref: ${payment.transactionReference}',
                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                ),
              ],
            ],
          ),
          actions: [
            FilledButton(
              onPressed: () {
                Navigator.of(ctx).pop();
                nav.pushReplacement(
                  MaterialPageRoute(
                    builder: (_) => PaymentDetailsScreen(paymentId: payment.id, initialPayment: payment),
                  ),
                );
              },
              child: const Text('View Receipt'),
            ),
          ],
        ),
      );
    } else {
      final errorMsg = provider.paymentError ?? 'Payment was declined or failed.';
      messenger.showSnackBar(
        SnackBar(
          content: Text(errorMsg),
          backgroundColor: AppColors.error,
          duration: const Duration(seconds: 4),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PaymentProvider>();
    final details = provider.jobPaymentDetails ?? widget.initialDetails;
    final isLoading = provider.isLoadingJobPayment;
    final isProcessing = provider.isProcessingPayment;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Payment'),
      ),
      body: isLoading && details == null
          ? const Center(child: CircularProgressIndicator())
          : details == null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline_rounded, size: 48, color: AppColors.error),
                      const SizedBox(height: 12),
                      Text(provider.paymentError ?? 'Unable to load payment details'),
                      const SizedBox(height: 12),
                      ElevatedButton(
                        onPressed: () => provider.fetchJobPaymentDetails(widget.jobId),
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Test Mode Warning Banner
                      if (details.testMode)
                        Container(
                          margin: const EdgeInsets.only(bottom: 16),
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF3C7),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: const Color(0xFFF59E0B)),
                          ),
                          child: Row(
                            children: const [
                              Icon(Icons.science_rounded, color: Color(0xFFD97706), size: 24),
                              SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  'TEST PAYMENT MODE\nNo real money will be charged. This transaction is safely simulated in sandbox.',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF92400E),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),

                      // Service Details Card
                      Card(
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'SERVICE SUMMARY',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textSecondary,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 12),
                              Text(
                                details.jobTitle,
                                style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 6),
                              Row(
                                children: [
                                  const Icon(Icons.person_outline_rounded, size: 16, color: AppColors.textSecondary),
                                  const SizedBox(width: 6),
                                  Text(
                                    'Worker: ${details.worker['name'] ?? 'Assigned Worker'}',
                                    style: const TextStyle(color: AppColors.textSecondary, fontSize: 14),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Row(
                                children: [
                                  const Icon(Icons.tag_rounded, size: 16, color: AppColors.textSecondary),
                                  const SizedBox(width: 6),
                                  Text(
                                    'Job: ${details.jobNumber}',
                                    style: const TextStyle(color: AppColors.textMuted, fontSize: 12),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Amount Card
                      Card(
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text('Service Amount', style: TextStyle(fontSize: 15, color: AppColors.textSecondary)),
                                  Text(details.formattedAmount, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
                                ],
                              ),
                              const Divider(height: 24),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  const Text('Total Payable', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                                  Text(
                                    details.formattedAmount,
                                    style: const TextStyle(
                                      fontSize: 22,
                                      fontWeight: FontWeight.bold,
                                      color: AppColors.primary,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),

                      // Payment Method Selector
                      Card(
                        elevation: 1,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'PAYMENT METHOD',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.bold,
                                  color: AppColors.textSecondary,
                                  letterSpacing: 0.5,
                                ),
                              ),
                              const SizedBox(height: 10),
                              ListTile(
                                contentPadding: EdgeInsets.zero,
                                leading: const Icon(Icons.payment_rounded, color: AppColors.primary),
                                title: const Text('WorkLink Sandbox Payment (Instant)', style: TextStyle(fontWeight: FontWeight.w600)),
                                subtitle: const Text('Safe development test payment mode'),
                                trailing: const Icon(Icons.check_circle_rounded, color: AppColors.primary),
                                onTap: () => setState(() => _selectedMethod = 'TEST_PAYMENT'),
                              ),
                              if (details.testMode) ...[
                                const Divider(),
                                SwitchListTile(
                                  title: const Text('Simulate Failed Payment', style: TextStyle(fontSize: 14)),
                                  subtitle: const Text('Use to test error handling & retry states', style: TextStyle(fontSize: 12)),
                                  value: _simulateFailure,
                                  contentPadding: EdgeInsets.zero,
                                  onChanged: (val) => setState(() => _simulateFailure = val),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Submit Pay Button
                      FilledButton(
                        style: FilledButton.styleFrom(
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          backgroundColor: _simulateFailure ? AppColors.error : AppColors.primary,
                        ),
                        onPressed: isProcessing ? null : () => _onPayPressed(details),
                        child: isProcessing
                            ? const SizedBox(
                                height: 20,
                                width: 20,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                              )
                            : Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.lock_rounded, size: 18),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Pay ${details.formattedAmount}',
                                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                                  ),
                                ],
                              ),
                      ),
                    ],
                  ),
                ),
    );
  }
}
