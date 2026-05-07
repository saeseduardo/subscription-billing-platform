<?php

namespace App\Providers;

use App\Payments\PaymentGatewayFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayFactory::class);
    }

    public function boot(): void
    {
    }
}
