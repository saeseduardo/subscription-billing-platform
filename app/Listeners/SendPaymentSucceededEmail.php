<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendPaymentSucceededEmail implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        Log::channel('billing')->info('Payment success email queued.', ['invoice_id' => $event->invoice->id]);
    }
}
