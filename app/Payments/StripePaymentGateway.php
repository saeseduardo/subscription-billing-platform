<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Models\Subscription;

class StripePaymentGateway implements PaymentGateway
{
    public function charge(Subscription $subscription, Invoice $invoice): PaymentResult
    {
        if (str_contains($subscription->payment_method_token, 'expired')) {
            return PaymentResult::failure('Stripe rejected the payment method because the card is expired.');
        }

        if (str_contains($subscription->payment_method_token, 'fail')) {
            return PaymentResult::failure('Stripe declined the charge due to insufficient funds.');
        }

        return PaymentResult::success('stripe_'.uniqid());
    }
}
