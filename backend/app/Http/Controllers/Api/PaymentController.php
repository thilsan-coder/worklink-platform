<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TransactionResource;
use App\Models\Job;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get payment details and eligibility for a job.
     */
    public function jobPayment(int $jobId, Request $request): JsonResponse
    {
        $job = Job::with(['customer', 'worker', 'payment.transactions'])->findOrFail($jobId);

        try {
            $details = $this->paymentService->getJobPaymentDetails($job, $request->user());
            return response()->json([
                'success' => true,
                'data' => $details,
            ]);
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * Process payment for a completed job.
     */
    public function processPayment(int $jobId, Request $request): JsonResponse
    {
        $job = Job::with(['customer', 'worker'])->findOrFail($jobId);
        $user = $request->user();

        $validated = $request->validate([
            'payment_method' => 'nullable|string|max:50',
            'simulate_failure' => 'nullable|boolean',
            'result' => 'nullable|string|in:success,failed',
        ]);

        try {
            $result = $this->paymentService->processJobPayment($job, $user, $validated);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => new PaymentResource($result['payment']),
                    'transaction' => $result['transaction'] ? new TransactionResource($result['transaction']) : null,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => new PaymentResource($result['payment']),
                ], 422);
            }
        } catch (Exception $e) {
            $status = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * List paginated payments for authenticated user (as customer or worker).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->integer('per_page', 15);

        $query = Payment::with(['job', 'customer', 'worker', 'transactions']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === 'worker') {
            $query->where('worker_id', $user->id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)->orWhere('worker_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments->items()),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
        ]);
    }

    /**
     * Show a single payment by ID.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $payment = Payment::with(['job', 'customer', 'worker', 'transactions'])->findOrFail($id);
        $user = $request->user();

        if ($payment->customer_id !== $user->id && $payment->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to payment record.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($payment),
        ]);
    }

    /**
     * List paginated transactions for authenticated user.
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->integer('per_page', 15);

        $query = Transaction::with(['job', 'customer', 'worker', 'payment']);

        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        } elseif ($user->role === 'worker') {
            $query->where('worker_id', $user->id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)->orWhere('worker_id', $user->id);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($transactions->items()),
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
        ]);
    }

    /**
     * Show a single transaction by ID.
     */
    public function showTransaction(int $id, Request $request): JsonResponse
    {
        $transaction = Transaction::with(['job', 'customer', 'worker', 'payment'])->findOrFail($id);
        $user = $request->user();

        if ($transaction->customer_id !== $user->id && $transaction->worker_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to transaction record.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new TransactionResource($transaction),
        ]);
    }

    /**
     * Get earnings summary and paid jobs history for worker.
     */
    public function workerEarnings(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'worker' && $user->role !== 'both') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only workers can access worker earnings.',
            ], 403);
        }

        try {
            $summary = $this->paymentService->getWorkerEarnings($user);

            // Fetch recent paid payments
            $recentPayments = Payment::where('worker_id', $user->id)
                ->where('status', 'PAID')
                ->with(['job', 'customer', 'transactions'])
                ->latest('paid_at')
                ->paginate($request->integer('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => $summary,
                    'payments' => PaymentResource::collection($recentPayments->items()),
                    'current_page' => $recentPayments->currentPage(),
                    'last_page' => $recentPayments->lastPage(),
                    'total' => $recentPayments->total(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
