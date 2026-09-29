import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/features/payments/models/job_payment_details_model.dart';
import 'package:mobile/features/payments/models/payment_model.dart';
import 'package:mobile/features/payments/models/transaction_model.dart';
import 'package:mobile/features/payments/models/worker_earnings_model.dart';
import 'package:mobile/features/payments/providers/payment_provider.dart';

void main() {
  group('Payment Models Tests', () {
    test('PaymentModel.fromJson parses paid payment payload correctly', () {
      final json = {
        'id': 1,
        'job_id': 10,
        'customer_id': 5,
        'worker_id': 8,
        'amount': 5500.00,
        'currency': 'LKR',
        'formatted_amount': 'LKR 5,500.00',
        'status': 'PAID',
        'payment_method': 'TEST_PAYMENT',
        'gateway_transaction_id': 'SANDBOX-TXN-12345',
        'transaction_reference': 'TXN-WL-20260928-001',
        'created_at': DateTime.now().toIso8601String(),
      };

      final payment = PaymentModel.fromJson(json);

      expect(payment.id, 1);
      expect(payment.jobId, 10);
      expect(payment.amount, 5500.0);
      expect(payment.currency, 'LKR');
      expect(payment.status, 'PAID');
      expect(payment.isPaid, isTrue);
      expect(payment.isFailed, isFalse);
      expect(payment.gatewayTransactionId, 'SANDBOX-TXN-12345');
    });

    test('PaymentModel.fromJson parses failed payment payload correctly', () {
      final json = {
        'id': 2,
        'job_id': 11,
        'customer_id': 5,
        'worker_id': 8,
        'amount': 3000.00,
        'status': 'FAILED',
        'payment_method': 'TEST_PAYMENT',
        'failure_reason': 'Card declined',
        'created_at': DateTime.now().toIso8601String(),
      };

      final payment = PaymentModel.fromJson(json);

      expect(payment.id, 2);
      expect(payment.status, 'FAILED');
      expect(payment.isPaid, isFalse);
      expect(payment.isFailed, isTrue);
      expect(payment.failureReason, 'Card declined');
    });

    test('TransactionModel.fromJson parses transaction payload correctly', () {
      final json = {
        'id': 101,
        'payment_id': 1,
        'job_id': 10,
        'customer_id': 5,
        'worker_id': 8,
        'type': 'PAYMENT',
        'amount': 5500.00,
        'currency': 'LKR',
        'status': 'COMPLETED',
        'reference': 'TXN-WL-20260928-001',
        'created_at': DateTime.now().toIso8601String(),
      };

      final txn = TransactionModel.fromJson(json);

      expect(txn.id, 101);
      expect(txn.paymentId, 1);
      expect(txn.amount, 5500.0);
      expect(txn.type, 'PAYMENT');
      expect(txn.status, 'COMPLETED');
      expect(txn.reference, 'TXN-WL-20260928-001');
    });

    test('JobPaymentDetailsModel.fromJson parses eligibility and amounts correctly', () {
      final json = {
        'job_id': 10,
        'job_number': 'WLJ-2026-001',
        'job_title': 'Electrical Wiring Fix',
        'job_status': 'COMPLETED',
        'customer': {'id': 5, 'name': 'John'},
        'worker': {'id': 8, 'name': 'Bob'},
        'amount': 7500.00,
        'currency': 'LKR',
        'formatted_amount': 'LKR 7,500.00',
        'is_eligible_for_payment': true,
        'is_paid': false,
        'test_mode': true,
      };

      final details = JobPaymentDetailsModel.fromJson(json);

      expect(details.jobId, 10);
      expect(details.amount, 7500.0);
      expect(details.isEligibleForPayment, isTrue);
      expect(details.isPaid, isFalse);
      expect(details.testMode, isTrue);
    });

    test('WorkerEarningsModel.fromJson parses earnings summary correctly', () {
      final json = {
        'summary': {
          'worker_id': 8,
          'worker_name': 'Ruwan Silva',
          'total_earnings': 14000.00,
          'currency': 'LKR',
          'formatted_total': 'LKR 14,000.00',
          'paid_jobs_count': 2,
        },
        'payments': [],
      };

      final earnings = WorkerEarningsModel.fromJson(json);

      expect(earnings.workerId, 8);
      expect(earnings.totalEarnings, 14000.0);
      expect(earnings.paidJobsCount, 2);
      expect(earnings.formattedTotal, 'LKR 14,000.00');
    });
  });

  group('PaymentProvider Tests', () {
    test('initial state is correct', () {
      final provider = PaymentProvider();
      expect(provider.jobPaymentDetails, isNull);
      expect(provider.isLoadingJobPayment, isFalse);
      expect(provider.isProcessingPayment, isFalse);
      expect(provider.paymentError, isNull);
      expect(provider.paymentHistory, isEmpty);
      expect(provider.isLoadingHistory, isFalse);
      expect(provider.workerEarnings, isNull);
      expect(provider.isLoadingEarnings, isFalse);
    });
  });
}
