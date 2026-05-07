<?php

namespace App\Providers;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Listeners\GenerateInvoicePdf;
use App\Listeners\SendPaymentFailedEmail;
use App\Listeners\SendPaymentSucceededEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        PaymentSucceeded::class => [
            GenerateInvoicePdf::class,
            SendPaymentSucceededEmail::class,
        ],
        PaymentFailed::class => [
            SendPaymentFailedEmail::class,
        ],
    ];
}
