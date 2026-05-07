<?php

return [
    'gateway' => env('BILLING_PAYMENT_GATEWAY', 'stripe'),
    'grace_days_after_failure' => 3,
    'invoice_prefix' => 'INV',
];
