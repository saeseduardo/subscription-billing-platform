<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Models\Subscription;

interface PaymentGateway
{
    public function charge(Subscription $subscription, Invoice $invoice): PaymentResult;
}
