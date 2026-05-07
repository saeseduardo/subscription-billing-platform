<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing('subscription.plan');

        $path = 'invoices/'.$invoice->number.'.pdf';
        if (! is_dir(storage_path('app/public/invoices'))) {
            mkdir(storage_path('app/public/invoices'), 0775, true);
        }

        Pdf::loadView('invoices.show', ['invoice' => $invoice])
            ->save(storage_path('app/public/'.$path));

        return $path;
    }
}
