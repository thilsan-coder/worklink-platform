<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for all jobs, payments, and transactions in WorkLink.
    |
    */
    'currency' => env('PAYMENT_CURRENCY', 'LKR'),

    /*
    |--------------------------------------------------------------------------
    | Payment Test Mode
    |--------------------------------------------------------------------------
    |
    | When enabled AND app environment is local, payments are safely simulated
    | without hitting live financial gateways or requiring real money.
    |
    */
    'test_mode' => env('PAYMENT_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Test Result Simulation
    |--------------------------------------------------------------------------
    |
    | In test mode, simulates either 'success' or 'failed' responses.
    |
    */
    'test_result' => env('PAYMENT_TEST_RESULT', 'success'),

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | Selected payment gateway implementation ('test', 'payhere', 'stripe').
    |
    */
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'test'),
];
