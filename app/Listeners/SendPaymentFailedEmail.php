<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentFailedEmail implements ShouldQueue
{
    public function handle(PaymentFailed $event): void
    {
        Log::channel('billing')->warning('Payment failure email queued.', ['invoice_id' => $event->invoice->id]);
    }
}
