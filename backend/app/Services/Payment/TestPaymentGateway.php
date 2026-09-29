<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Str;

class TestPaymentGateway implements PaymentGatewayInterface
{
    public function process(Payment $payment, array $options = []): array
    {
        $isLocal = app()->environment('local', 'testing');
        $isTestMode = config('payment.test_mode', true);

        // Disallow test processing outside local / testing environments
        if (! $isLocal) {
            return [
                'success' => false,
                'gateway_transaction_id' => null,
                'message' => 'Simulated test payments are strictly disabled in non-local environments.',
                'raw' => ['environment' => app()->environment()],
            ];
        }

        // Check simulated failure override
        $simulateFailure = ($options['simulate_failure'] ?? false) === true
            || ($options['result'] ?? '') === 'failed'
            || config('payment.test_result') === 'failed';

        if ($simulateFailure) {
            return [
                'success' => false,
                'gateway_transaction_id' => null,
                'message' => 'Simulated test payment failed: Insufficient test funds or declined by simulated bank.',
                'raw' => ['simulated_code' => 'DECLINED_BY_BANK'],
            ];
        }

        $gatewayTxnId = 'SANDBOX-TXN-' . date('Ymd') . '-' . Str::upper(Str::random(8));

        return [
            'success' => true,
            'gateway_transaction_id' => $gatewayTxnId,
            'message' => 'Test payment processed successfully in sandbox mode.',
            'raw' => [
                'gateway' => 'test',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    public function refund(Payment $payment, string $reason): array
    {
        $isLocal = app()->environment('local', 'testing');
        if (! $isLocal) {
            return [
                'success' => false,
                'gateway_refund_id' => null,
                'message' => 'Simulated test refunds are strictly disabled in non-local environments.',
            ];
        }

        return [
            'success' => true,
            'gateway_refund_id' => 'SANDBOX-REF-' . date('Ymd') . '-' . Str::upper(Str::random(8)),
            'message' => 'Test refund processed successfully.',
        ];
    }
}
