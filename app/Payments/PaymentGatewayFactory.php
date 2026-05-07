<?php

namespace App\Payments;

use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function make(?string $gateway = null): PaymentGateway
    {
        return match ($gateway ?? config('billing.gateway')) {
            'stripe' => new StripePaymentGateway,
            'paypal' => new PayPalPaymentGateway,
            default => throw new InvalidArgumentException('Unsupported payment gateway.'),
        };
    }
}
