<?php

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Support\Facades\Event;

it('charges due active subscriptions and renews the billing period', function (): void {
    Event::fake();

    $user = User::create(['name' => 'Ada', 'email' => 'ada@example.test', 'password' => 'secret']);
    $plan = Plan::create(['name' => 'Pro', 'slug' => 'pro', 'price_cents' => 4900, 'currency' => 'USD', 'billing_interval' => 'monthly']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'gateway' => 'stripe',
        'payment_method_token' => 'pm_valid',
        'current_period_starts_at' => now()->subMonth(),
        'current_period_ends_at' => now()->subMinute(),
    ]);

    $invoice = app(BillingService::class)->processDueSubscription($subscription);

    expect($invoice->status)->toBe(Invoice::STATUS_PAID)
        ->and($invoice->gateway_transaction_id)->toStartWith('stripe_')
        ->and($subscription->fresh()->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($subscription->fresh()->current_period_ends_at->isFuture())->toBeTrue();

    Event::assertDispatched(PaymentSucceeded::class);
});

it('marks subscriptions as past due when the card is expired', function (): void {
    Event::fake();

    $user = User::create(['name' => 'Grace', 'email' => 'grace@example.test', 'password' => 'secret']);
    $plan = Plan::create(['name' => 'Starter', 'slug' => 'starter', 'price_cents' => 1900, 'currency' => 'USD', 'billing_interval' => 'monthly']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'gateway' => 'stripe',
        'payment_method_token' => 'pm_expired',
        'current_period_starts_at' => now()->subMonth(),
        'current_period_ends_at' => now()->subMinute(),
    ]);

    $invoice = app(BillingService::class)->processDueSubscription($subscription);

    expect($invoice->status)->toBe(Invoice::STATUS_FAILED)
        ->and($invoice->failure_reason)->toContain('expired')
        ->and($subscription->fresh()->status)->toBe(Subscription::STATUS_PAST_DUE);

    Event::assertDispatched(PaymentFailed::class);
});

it('supports paypal through the same billing workflow', function (): void {
    $user = User::create(['name' => 'Linus', 'email' => 'linus@example.test', 'password' => 'secret']);
    $plan = Plan::create(['name' => 'Enterprise', 'slug' => 'enterprise', 'price_cents' => 49900, 'currency' => 'USD', 'billing_interval' => 'yearly']);
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_id' => $plan->id,
        'status' => Subscription::STATUS_ACTIVE,
        'gateway' => 'paypal',
        'payment_method_token' => 'ba_valid',
        'current_period_starts_at' => now()->subYear(),
        'current_period_ends_at' => now()->subMinute(),
    ]);

    $invoice = app(BillingService::class)->processDueSubscription($subscription);

    expect($invoice->status)->toBe(Invoice::STATUS_PAID)
        ->and($invoice->gateway_transaction_id)->toStartWith('paypal_');
});
