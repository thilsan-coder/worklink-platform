import 'package:flutter/foundation.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../models/job_payment_details_model.dart';
import '../models/payment_model.dart';
import '../models/transaction_model.dart';
import '../models/worker_earnings_model.dart';

class PaymentProvider extends ChangeNotifier {
  // Job payment details state
  JobPaymentDetailsModel? _jobPaymentDetails;
  bool _isLoadingJobPayment = false;
  bool _isProcessingPayment = false;
  String? _paymentError;

  // History & lists
  List<PaymentModel> _paymentHistory = [];
  bool _isLoadingHistory = false;
  int _historyCurrentPage = 1;
  int _historyLastPage = 1;

  List<TransactionModel> _transactionHistory = [];
  bool _isLoadingTransactions = false;

  // Worker earnings
  WorkerEarningsModel? _workerEarnings;
  bool _isLoadingEarnings = false;

  // Selected payment details
  PaymentModel? _selectedPayment;
  bool _isLoadingPaymentDetails = false;

  // Getters
  JobPaymentDetailsModel? get jobPaymentDetails => _jobPaymentDetails;
  bool get isLoadingJobPayment => _isLoadingJobPayment;
  bool get isProcessingPayment => _isProcessingPayment;
  String? get paymentError => _paymentError;

  List<PaymentModel> get paymentHistory => _paymentHistory;
  bool get isLoadingHistory => _isLoadingHistory;
  bool get hasMoreHistory => _historyCurrentPage < _historyLastPage;

  List<TransactionModel> get transactionHistory => _transactionHistory;
  bool get isLoadingTransactions => _isLoadingTransactions;

  WorkerEarningsModel? get workerEarnings => _workerEarnings;
  bool get isLoadingEarnings => _isLoadingEarnings;

  PaymentModel? get selectedPayment => _selectedPayment;
  bool get isLoadingPaymentDetails => _isLoadingPaymentDetails;

  /// Fetch payment calculation, eligibility and details for a job
  Future<JobPaymentDetailsModel?> fetchJobPaymentDetails(int jobId) async {
    _isLoadingJobPayment = true;
    _paymentError = null;
    notifyListeners();

    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.jobPayment(jobId));
      if (res.statusCode == 200 && res.data != null && res.data['success'] == true) {
        _jobPaymentDetails = JobPaymentDetailsModel.fromJson(res.data['data']);
        return _jobPaymentDetails;
      } else {
        _paymentError = res.data?['message'] ?? 'Unable to fetch payment details';
      }
    } catch (e) {
      _paymentError = 'Failed to fetch payment details: $e';
    } finally {
      _isLoadingJobPayment = false;
      notifyListeners();
    }
    return null;
  }

  /// Process payment for a completed job
  Future<PaymentModel?> processPayment(
    int jobId, {
    String paymentMethod = 'TEST_PAYMENT',
    bool simulateFailure = false,
  }) async {
    _isProcessingPayment = true;
    _paymentError = null;
    notifyListeners();

    try {
      final res = await ApiClient.instance.client.post(
        ApiEndpoints.jobPayment(jobId),
        data: {
          'payment_method': paymentMethod,
          'simulate_failure': simulateFailure,
        },
      );

      if (res.statusCode == 200 && res.data != null && res.data['success'] == true) {
        final payment = PaymentModel.fromJson(res.data['data']);
        _selectedPayment = payment;

        // Refresh job payment details locally
        if (_jobPaymentDetails != null && _jobPaymentDetails!.jobId == jobId) {
          _jobPaymentDetails = JobPaymentDetailsModel(
            jobId: _jobPaymentDetails!.jobId,
            jobNumber: _jobPaymentDetails!.jobNumber,
            jobTitle: _jobPaymentDetails!.jobTitle,
            jobStatus: _jobPaymentDetails!.jobStatus,
            customer: _jobPaymentDetails!.customer,
            worker: _jobPaymentDetails!.worker,
            amount: _jobPaymentDetails!.amount,
            currency: _jobPaymentDetails!.currency,
            formattedAmount: _jobPaymentDetails!.formattedAmount,
            isEligibleForPayment: false,
            isPaid: true,
            payment: payment,
            testMode: _jobPaymentDetails!.testMode,
          );
        }

        // Add to history
        _paymentHistory.insert(0, payment);
        return payment;
      } else {
        _paymentError = res.data?['message'] ?? 'Payment failed';
        if (res.data?['data'] != null) {
          _selectedPayment = PaymentModel.fromJson(res.data['data']);
        }
      }
    } catch (e) {
      _paymentError = 'Payment processing error: $e';
    } finally {
      _isProcessingPayment = false;
      notifyListeners();
    }
    return null;
  }

  /// Fetch user payment history
  Future<void> fetchPaymentHistory({bool refresh = false, String? status}) async {
    if (refresh) {
      _historyCurrentPage = 1;
    }

    _isLoadingHistory = true;
    notifyListeners();

    try {
      final Map<String, dynamic> query = {
        'page': _historyCurrentPage,
        'per_page': 15,
      };
      if (status != null && status.isNotEmpty && status != 'ALL') {
        query['status'] = status;
      }

      final res = await ApiClient.instance.client.get(
        ApiEndpoints.payments,
        queryParameters: query,
      );

      if (res.statusCode == 200 && res.data != null) {
        final raw = res.data['data'];
        List<PaymentModel> loaded = [];
        if (raw is List) {
          loaded = raw.map((item) => PaymentModel.fromJson(item as Map<String, dynamic>)).toList();
        }

        if (_historyCurrentPage == 1) {
          _paymentHistory = loaded;
        } else {
          _paymentHistory.addAll(loaded);
        }

        _historyCurrentPage = (res.data['current_page'] as num?)?.toInt() ?? _historyCurrentPage;
        _historyLastPage = (res.data['last_page'] as num?)?.toInt() ?? _historyCurrentPage;
      }
    } catch (e) {
      if (kDebugMode) print('Payment history error: $e');
    } finally {
      _isLoadingHistory = false;
      notifyListeners();
    }
  }

  /// Fetch transactions history
  Future<void> fetchTransactionHistory({bool refresh = false, String? type}) async {
    _isLoadingTransactions = true;
    notifyListeners();

    try {
      final Map<String, dynamic> query = {'per_page': 20};
      if (type != null && type.isNotEmpty && type != 'ALL') {
        query['type'] = type;
      }

      final res = await ApiClient.instance.client.get(
        ApiEndpoints.transactions,
        queryParameters: query,
      );

      if (res.statusCode == 200 && res.data != null) {
        final raw = res.data['data'];
        if (raw is List) {
          _transactionHistory = raw.map((item) => TransactionModel.fromJson(item as Map<String, dynamic>)).toList();
        }
      }
    } catch (e) {
      if (kDebugMode) print('Transactions history error: $e');
    } finally {
      _isLoadingTransactions = false;
      notifyListeners();
    }
  }

  /// Fetch worker earnings overview and recent paid transactions
  Future<WorkerEarningsModel?> fetchWorkerEarnings() async {
    _isLoadingEarnings = true;
    notifyListeners();

    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.workerEarnings);
      if (res.statusCode == 200 && res.data != null && res.data['success'] == true) {
        _workerEarnings = WorkerEarningsModel.fromJson(res.data['data']);
        return _workerEarnings;
      }
    } catch (e) {
      if (kDebugMode) print('Worker earnings error: $e');
    } finally {
      _isLoadingEarnings = false;
      notifyListeners();
    }
    return null;
  }

  /// Fetch single payment details by ID
  Future<PaymentModel?> fetchPaymentDetails(int paymentId) async {
    _isLoadingPaymentDetails = true;
    notifyListeners();

    try {
      final res = await ApiClient.instance.client.get(ApiEndpoints.paymentDetails(paymentId));
      if (res.statusCode == 200 && res.data != null && res.data['success'] == true) {
        _selectedPayment = PaymentModel.fromJson(res.data['data']);
        return _selectedPayment;
      }
    } catch (e) {
      if (kDebugMode) print('Payment details fetch error: $e');
    } finally {
      _isLoadingPaymentDetails = false;
      notifyListeners();
    }
    return null;
  }
}
