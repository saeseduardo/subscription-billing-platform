<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'starter'], [
            'name' => 'Starter',
            'price_cents' => 1900,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'trial_days' => 14,
            'features' => ['10 projects', 'Basic support'],
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'price_cents' => 4900,
            'currency' => 'USD',
            'billing_interval' => 'monthly',
            'trial_days' => 7,
            'features' => ['Unlimited projects', 'Priority support', 'PDF invoices'],
        ]);

        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise',
            'price_cents' => 49900,
            'currency' => 'USD',
            'billing_interval' => 'yearly',
            'trial_days' => 30,
            'features' => ['SLA', 'Dedicated manager', 'Custom billing'],
        ]);
    }
}
