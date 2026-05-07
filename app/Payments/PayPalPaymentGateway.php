<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Models\Subscription;

class PayPalPaymentGateway implements PaymentGateway
{
    public function charge(Subscription $subscription, Invoice $invoice): PaymentResult
    {
        if (str_contains($subscription->payment_method_token, 'expired')) {
            return PaymentResult::failure('PayPal billing agreement is expired.');
        }

        if (str_contains($subscription->payment_method_token, 'fail')) {
            return PaymentResult::failure('PayPal could not capture the recurring payment.');
        }

        return PaymentResult::success('paypal_'.uniqid());
    }
}
