<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    public function subscribe(User $user, Plan $plan, string $paymentMethodToken, string $gateway): Subscription
    {
        $now = Carbon::now();
        $trialEndsAt = $plan->trial_days > 0 ? $now->copy()->addDays($plan->trial_days) : null;

        return Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => $trialEndsAt ? Subscription::STATUS_TRIALING : Subscription::STATUS_ACTIVE,
            'gateway' => $gateway,
            'payment_method_token' => $paymentMethodToken,
            'trial_ends_at' => $trialEndsAt,
            'current_period_starts_at' => $now,
            'current_period_ends_at' => $trialEndsAt ?? $this->nextPeriodEnd($now, $plan->billing_interval),
        ]);
    }

    public function renewPeriod(Subscription $subscription): void
    {
        $subscription->forceFill([
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_starts_at' => now(),
            'current_period_ends_at' => $this->nextPeriodEnd(now(), $subscription->plan->billing_interval),
        ])->save();
    }

    private function nextPeriodEnd(Carbon $startsAt, string $interval): Carbon
    {
        return match ($interval) {
            'yearly' => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth(),
        };
    }
}
