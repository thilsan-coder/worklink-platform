<?php

namespace App\Services\Payment;

use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Process a payment.
     *
     * @param Payment $payment
     * @param array $options
     * @return array [ 'success' => bool, 'gateway_transaction_id' => ?string, 'message' => string, 'raw' => array ]
     */
    public function process(Payment $payment, array $options = []): array;

    /**
     * Process a refund.
     *
     * @param Payment $payment
     * @param string $reason
     * @return array [ 'success' => bool, 'gateway_refund_id' => ?string, 'message' => string ]
     */
    public function refund(Payment $payment, string $reason): array;
}
