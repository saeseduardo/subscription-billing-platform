<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateInvoicePdf implements ShouldQueue
{
    public function __construct(private readonly InvoicePdfService $invoicePdfService)
    {
    }

    public function handle(PaymentSucceeded $event): void
    {
        $path = $this->invoicePdfService->generate($event->invoice);
        $event->invoice->forceFill(['pdf_path' => $path])->save();
    }
}
