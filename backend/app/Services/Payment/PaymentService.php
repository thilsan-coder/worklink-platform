<?php

namespace App\Services\Payment;

use App\Models\Job;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    private PaymentGatewayInterface $gateway;

    public function __construct(?PaymentGatewayInterface $gateway = null)
    {
        $this->gateway = $gateway ?? new TestPaymentGateway();
    }

    /**
     * Calculate or retrieve the authorized payable amount for a job.
     */
    public function getPayableAmount(Job $job): float
    {
        if ($job->final_cost !== null && (float) $job->final_cost > 0) {
            return (float) $job->final_cost;
        }

        if ($job->estimated_cost !== null && (float) $job->estimated_cost > 0) {
            return (float) $job->estimated_cost;
        }

        return 1000.00; // Default minimum service amount
    }

    /**
     * Get payment details summary for a job.
     */
    public function getJobPaymentDetails(Job $job, User $user): array
    {
        if ($job->customer_id !== $user->id && $job->worker_id !== $user->id && $user->role !== 'admin') {
            throw new Exception('Unauthorized to access payment details for this job.', 403);
        }

        $amount = $this->getPayableAmount($job);
        $currency = config('payment.currency', 'LKR');
        $existingPayment = Payment::where('job_id', $job->id)
            ->with(['transactions'])
            ->latest()
            ->first();

        $isEligibleForPayment = in_array($job->status, ['COMPLETED', 'WORK_COMPLETED', 'CUSTOMER_CONFIRMED'])
            && (! $existingPayment || $existingPayment->status === 'FAILED');

        $isPaid = $existingPayment && $existingPayment->status === 'PAID';

        return [
            'job_id' => $job->id,
            'job_number' => $job->job_number,
            'job_title' => $job->title,
            'job_status' => $job->status,
            'customer' => [
                'id' => $job->customer->id,
                'name' => $job->customer->name,
            ],
            'worker' => [
                'id' => $job->worker->id,
                'name' => $job->worker->name,
            ],
            'amount' => $amount,
            'currency' => $currency,
            'formatted_amount' => $currency . ' ' . number_format($amount, 2),
            'is_eligible_for_payment' => $isEligibleForPayment,
            'is_paid' => $isPaid,
            'payment' => $existingPayment,
            'test_mode' => app()->environment('local', 'testing') && config('payment.test_mode', true),
        ];
    }

    /**
     * Process payment for a completed job.
     */
    public function processJobPayment(Job $job, User $customer, array $options = []): array
    {
        // 1. Ownership validation
        if ($job->customer_id !== $customer->id) {
            throw new Exception('Unauthorized. Only the job customer can initiate payment.', 403);
        }

        // 2. Job status eligibility
        if (! in_array($job->status, ['COMPLETED', 'WORK_COMPLETED', 'CUSTOMER_CONFIRMED'])) {
            throw new Exception('Payment cannot be processed. Job status must be COMPLETED (current status: ' . $job->status . ').', 422);
        }

        return DB::transaction(function () use ($job, $customer, $options) {
            // Lock job row for payment to avoid race conditions
            $lockedJob = Job::where('id', $job->id)->lockForUpdate()->first();

            // 3. Duplicate payment protection
            $alreadyPaid = Payment::where('job_id', $lockedJob->id)
                ->where('status', 'PAID')
                ->lockForUpdate()
                ->first();

            if ($alreadyPaid) {
                throw new Exception('This job has already been paid successfully.', 422);
            }

            // 4. Determine payable amount strictly server-side
            $amount = $this->getPayableAmount($lockedJob);
            $currency = config('payment.currency', 'LKR');
            $paymentMethod = $options['payment_method'] ?? 'TEST_PAYMENT';

            // 5. Create Payment record in PENDING state
            $payment = Payment::create([
                'job_id' => $lockedJob->id,
                'customer_id' => $lockedJob->customer_id,
                'worker_id' => $lockedJob->worker_id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'PENDING',
                'payment_method' => $paymentMethod,
                'gateway' => 'test',
                'metadata' => [
                    'job_number' => $lockedJob->job_number,
                    'job_title' => $lockedJob->title,
                    'client_ip' => request()->ip(),
                ],
            ]);

            // Set to PROCESSING
            $payment->update(['status' => 'PROCESSING']);

            // 6. Invoke Gateway
            $gatewayResult = $this->gateway->process($payment, $options);

            if ($gatewayResult['success']) {
                // 7. Success state update
                $payment->update([
                    'status' => 'PAID',
                    'gateway_transaction_id' => $gatewayResult['gateway_transaction_id'],
                    'paid_at' => now(),
                    'metadata' => array_merge($payment->metadata ?? [], ['gateway_response' => $gatewayResult['raw'] ?? []]),
                ]);

                // 8. Create Transaction ledger record
                $reference = 'TXN-WL-' . date('Ymd') . '-' . Str::upper(Str::random(6));
                $transaction = Transaction::create([
                    'payment_id' => $payment->id,
                    'job_id' => $lockedJob->id,
                    'customer_id' => $lockedJob->customer_id,
                    'worker_id' => $lockedJob->worker_id,
                    'type' => 'PAYMENT',
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'COMPLETED',
                    'reference' => $reference,
                    'metadata' => [
                        'gateway_transaction_id' => $gatewayResult['gateway_transaction_id'],
                        'payment_method' => $paymentMethod,
                    ],
                ]);

                // 9. Create Notifications
                // Customer notification
                Notification::create([
                    'user_id' => $lockedJob->customer_id,
                    'title' => 'Payment Successful',
                    'body' => 'Your payment of ' . $currency . ' ' . number_format($amount, 2) . ' for "' . $lockedJob->title . '" was successful.',
                    'type' => 'PAYMENT_SUCCESSFUL',
                    'reference_id' => $payment->id,
                    'data' => [
                        'entity_type' => 'payment',
                        'entity_id' => $payment->id,
                        'payment_id' => $payment->id,
                        'job_id' => $lockedJob->id,
                        'transaction_reference' => $reference,
                    ],
                ]);

                // Worker notification
                Notification::create([
                    'user_id' => $lockedJob->worker_id,
                    'title' => 'Payment Received',
                    'body' => 'You received ' . $currency . ' ' . number_format($amount, 2) . ' from ' . $customer->name . ' for "' . $lockedJob->title . '".',
                    'type' => 'PAYMENT_RECEIVED',
                    'reference_id' => $payment->id,
                    'data' => [
                        'entity_type' => 'payment',
                        'entity_id' => $payment->id,
                        'payment_id' => $payment->id,
                        'job_id' => $lockedJob->id,
                        'transaction_reference' => $reference,
                    ],
                ]);

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully.',
                    'payment' => $payment->fresh(['transactions', 'job', 'customer', 'worker']),
                    'transaction' => $transaction,
                ];
            } else {
                // Failure state update
                $failureReason = $gatewayResult['message'] ?? 'Payment failed.';
                $payment->update([
                    'status' => 'FAILED',
                    'failure_reason' => $failureReason,
                ]);

                // Create failure notification for customer
                Notification::create([
                    'user_id' => $lockedJob->customer_id,
                    'title' => 'Payment Failed',
                    'body' => 'Your payment of ' . $currency . ' ' . number_format($amount, 2) . ' for "' . $lockedJob->title . '" failed: ' . $failureReason,
                    'type' => 'PAYMENT_FAILED',
                    'reference_id' => $payment->id,
                    'data' => [
                        'entity_type' => 'payment',
                        'entity_id' => $payment->id,
                        'payment_id' => $payment->id,
                        'job_id' => $lockedJob->id,
                    ],
                ]);

                return [
                    'success' => false,
                    'message' => $failureReason,
                    'payment' => $payment->fresh(['job', 'customer', 'worker']),
                    'transaction' => null,
                ];
            }
        });
    }

    /**
     * Process refund for a paid job (authorized backend action).
     */
    public function processRefund(Payment $payment, User $initiator, string $reason): array
    {
        if ($payment->status !== 'PAID') {
            throw new Exception('Only PAID payments can be refunded (current status: ' . $payment->status . ').', 422);
        }

        return DB::transaction(function () use ($payment, $initiator, $reason) {
            $payment->update([
                'status' => 'REFUND_PENDING',
            ]);

            $refundResult = $this->gateway->refund($payment, $reason);

            if ($refundResult['success']) {
                $payment->update([
                    'status' => 'REFUNDED',
                    'refunded_at' => now(),
                    'refund_reason' => $reason,
                ]);

                $refReference = 'REF-WL-' . date('Ymd') . '-' . Str::upper(Str::random(6));
                $transaction = Transaction::create([
                    'payment_id' => $payment->id,
                    'job_id' => $payment->job_id,
                    'customer_id' => $payment->customer_id,
                    'worker_id' => $payment->worker_id,
                    'type' => 'REFUND',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => 'COMPLETED',
                    'reference' => $refReference,
                    'metadata' => [
                        'gateway_refund_id' => $refundResult['gateway_refund_id'],
                        'refund_reason' => $reason,
                        'refunded_by' => $initiator->id,
                    ],
                ]);

                // Notifications for refund
                Notification::create([
                    'user_id' => $payment->customer_id,
                    'title' => 'Refund Processed',
                    'body' => 'A refund of ' . $payment->currency . ' ' . number_format($payment->amount, 2) . ' for "' . $payment->job->title . '" has been issued.',
                    'type' => 'PAYMENT_REFUNDED',
                    'reference_id' => $payment->id,
                    'data' => [
                        'entity_type' => 'payment',
                        'entity_id' => $payment->id,
                        'transaction_reference' => $refReference,
                    ],
                ]);

                return [
                    'success' => true,
                    'message' => 'Refund processed successfully.',
                    'payment' => $payment->fresh(),
                    'transaction' => $transaction,
                ];
            } else {
                $payment->update(['status' => 'PAID']); // revert
                throw new Exception($refundResult['message'] ?? 'Refund failed.', 422);
            }
        });
    }

    /**
     * Get worker total earnings summary.
     */
    public function getWorkerEarnings(User $worker): array
    {
        if ($worker->role !== 'worker' && $worker->role !== 'both') {
            throw new Exception('User is not registered as a worker.', 403);
        }

        $paidPayments = Payment::where('worker_id', $worker->id)
            ->where('status', 'PAID');

        $totalEarnings = (float) $paidPayments->sum('amount');
        $paidCount = $paidPayments->count();

        $currency = config('payment.currency', 'LKR');

        return [
            'worker_id' => $worker->id,
            'worker_name' => $worker->name,
            'total_earnings' => $totalEarnings,
            'currency' => $currency,
            'formatted_total' => $currency . ' ' . number_format($totalEarnings, 2),
            'paid_jobs_count' => $paidCount,
        ];
    }
}
