<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRecurringBillingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(BillingService $billingService): void
    {
        Subscription::query()
            ->with('plan')
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->where('current_period_ends_at', '<=', now())
            ->chunkById(100, function ($subscriptions) use ($billingService): void {
                foreach ($subscriptions as $subscription) {
                    $billingService->processDueSubscription($subscription);
                }
            });
    }
}
