<?php

namespace Tests\Feature;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BillingServicePhpUnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_charges_due_active_subscriptions_and_renews_the_billing_period(): void
    {
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

        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertStringStartsWith('stripe_', $invoice->gateway_transaction_id);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->current_period_ends_at->isFuture());

        Event::assertDispatched(PaymentSucceeded::class);
    }

    public function test_it_marks_subscriptions_as_past_due_when_the_card_is_expired(): void
    {
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

        $this->assertSame(Invoice::STATUS_FAILED, $invoice->status);
        $this->assertStringContainsString('expired', $invoice->failure_reason);
        $this->assertSame(Subscription::STATUS_PAST_DUE, $subscription->fresh()->status);

        Event::assertDispatched(PaymentFailed::class);
    }

    public function test_it_supports_paypal_through_the_same_billing_workflow(): void
    {
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

        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
        $this->assertStringStartsWith('paypal_', $invoice->gateway_transaction_id);
    }
}
