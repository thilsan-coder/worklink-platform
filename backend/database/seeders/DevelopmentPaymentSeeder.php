<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DevelopmentPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'customer')->get();
        $workers = User::where('role', 'worker')->get();

        if ($customers->isEmpty() || $workers->isEmpty()) {
            return;
        }

        $c1 = $customers->first();
        $c2 = $customers->count() > 1 ? $customers->get(1) : $c1;
        $w1 = $workers->first();
        $w2 = $workers->count() > 1 ? $workers->get(1) : $w1;

        $jobs = Job::all();
        if ($jobs->isEmpty()) {
            return;
        }

        $completedJobs = Job::whereIn('status', ['COMPLETED', 'WORK_COMPLETED', 'CUSTOMER_CONFIRMED'])->get();
        if ($completedJobs->isEmpty()) {
            $completedJobs = $jobs;
        }

        $j1 = $completedJobs->first();
        $j2 = $completedJobs->count() > 1 ? $completedJobs->get(1) : $j1;
        $j3 = $completedJobs->count() > 2 ? $completedJobs->get(2) : $j1;
        $j4 = $completedJobs->count() > 3 ? $completedJobs->get(3) : $j1;

        // Payment 1: Completed & PAID
        if ($j1) {
            $p1Amount = (float) ($j1->final_cost ?? $j1->estimated_cost ?? 5500.00);
            $p1 = Payment::updateOrCreate(
                [
                    'job_id' => $j1->id,
                    'status' => 'PAID',
                ],
                [
                    'customer_id' => $j1->customer_id,
                    'worker_id' => $j1->worker_id,
                    'amount' => $p1Amount,
                    'currency' => 'LKR',
                    'payment_method' => 'TEST_PAYMENT',
                    'gateway' => 'test',
                    'gateway_transaction_id' => 'SANDBOX-TXN-20260928-88319A',
                    'paid_at' => now()->subDays(2),
                    'metadata' => [
                        'job_number' => $j1->job_number,
                        'job_title' => $j1->title,
                    ],
                ]
            );

            Transaction::updateOrCreate(
                [
                    'payment_id' => $p1->id,
                    'type' => 'PAYMENT',
                ],
                [
                    'job_id' => $j1->id,
                    'customer_id' => $j1->customer_id,
                    'worker_id' => $j1->worker_id,
                    'amount' => $p1Amount,
                    'currency' => 'LKR',
                    'status' => 'COMPLETED',
                    'reference' => 'TXN-WL-20260928-DEMO01',
                    'metadata' => [
                        'gateway_transaction_id' => 'SANDBOX-TXN-20260928-88319A',
                        'payment_method' => 'TEST_PAYMENT',
                    ],
                ]
            );
        }

        // Payment 2: Completed & PAID (second worker/job)
        if ($j2 && $j2->id !== $j1?->id) {
            $p2Amount = (float) ($j2->final_cost ?? $j2->estimated_cost ?? 8500.00);
            $p2 = Payment::updateOrCreate(
                [
                    'job_id' => $j2->id,
                    'status' => 'PAID',
                ],
                [
                    'customer_id' => $j2->customer_id,
                    'worker_id' => $j2->worker_id,
                    'amount' => $p2Amount,
                    'currency' => 'LKR',
                    'payment_method' => 'TEST_PAYMENT',
                    'gateway' => 'test',
                    'gateway_transaction_id' => 'SANDBOX-TXN-20260928-44910B',
                    'paid_at' => now()->subDays(1),
                    'metadata' => [
                        'job_number' => $j2->job_number,
                        'job_title' => $j2->title,
                    ],
                ]
            );

            Transaction::updateOrCreate(
                [
                    'payment_id' => $p2->id,
                    'type' => 'PAYMENT',
                ],
                [
                    'job_id' => $j2->id,
                    'customer_id' => $j2->customer_id,
                    'worker_id' => $j2->worker_id,
                    'amount' => $p2Amount,
                    'currency' => 'LKR',
                    'status' => 'COMPLETED',
                    'reference' => 'TXN-WL-20260928-DEMO02',
                    'metadata' => [
                        'gateway_transaction_id' => 'SANDBOX-TXN-20260928-44910B',
                        'payment_method' => 'TEST_PAYMENT',
                    ],
                ]
            );
        }

        // Payment 3: FAILED payment record (for testing retry flow)
        if ($j3 && $j3->id !== $j1?->id && $j3->id !== $j2?->id) {
            $p3Amount = (float) ($j3->final_cost ?? $j3->estimated_cost ?? 3200.00);
            Payment::updateOrCreate(
                [
                    'job_id' => $j3->id,
                    'status' => 'FAILED',
                ],
                [
                    'customer_id' => $j3->customer_id,
                    'worker_id' => $j3->worker_id,
                    'amount' => $p3Amount,
                    'currency' => 'LKR',
                    'payment_method' => 'TEST_PAYMENT',
                    'gateway' => 'test',
                    'failure_reason' => 'Simulated test failure: Card declined by simulated issuer.',
                    'metadata' => [
                        'job_number' => $j3->job_number,
                        'job_title' => $j3->title,
                    ],
                ]
            );
        }

        // Payment 4: REFUNDED payment record
        if ($j4 && $j4->id !== $j1?->id && $j4->id !== $j2?->id && $j4->id !== $j3?->id) {
            $p4Amount = (float) ($j4->final_cost ?? $j4->estimated_cost ?? 6000.00);
            $p4 = Payment::updateOrCreate(
                [
                    'job_id' => $j4->id,
                    'status' => 'REFUNDED',
                ],
                [
                    'customer_id' => $j4->customer_id,
                    'worker_id' => $j4->worker_id,
                    'amount' => $p4Amount,
                    'currency' => 'LKR',
                    'payment_method' => 'TEST_PAYMENT',
                    'gateway' => 'test',
                    'gateway_transaction_id' => 'SANDBOX-TXN-20260927-11002C',
                    'paid_at' => now()->subDays(4),
                    'refunded_at' => now()->subDays(3),
                    'refund_reason' => 'Customer requested schedule cancellation after agreement.',
                    'metadata' => [
                        'job_number' => $j4->job_number,
                        'job_title' => $j4->title,
                    ],
                ]
            );

            Transaction::updateOrCreate(
                [
                    'payment_id' => $p4->id,
                    'type' => 'REFUND',
                ],
                [
                    'job_id' => $j4->id,
                    'customer_id' => $j4->customer_id,
                    'worker_id' => $j4->worker_id,
                    'amount' => $p4Amount,
                    'currency' => 'LKR',
                    'status' => 'COMPLETED',
                    'reference' => 'REF-WL-20260927-DEMO01',
                    'metadata' => [
                        'gateway_refund_id' => 'SANDBOX-REF-20260927-9921',
                        'refund_reason' => 'Customer requested schedule cancellation after agreement.',
                    ],
                ]
            );
        }
    }
}
