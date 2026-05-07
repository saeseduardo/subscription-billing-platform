<?php

namespace App\Services;

use App\Events\PaymentFailed;
use App\Events\PaymentSucceeded;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Payments\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    public function processDueSubscription(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription): Invoice {
            $subscription->loadMissing('plan');

            $invoice = Invoice::create([
                'subscription_id' => $subscription->id,
                'number' => config('billing.invoice_prefix').'-'.now()->format('Ymd').'-'.$subscription->id.'-'.uniqid(),
                'status' => Invoice::STATUS_DRAFT,
                'amount_cents' => $subscription->plan->price_cents,
                'currency' => $subscription->plan->currency,
                'due_at' => now(),
            ]);

            $result = $this->gatewayFactory->make($subscription->gateway)->charge($subscription, $invoice);

            if ($result->successful) {
                $invoice->forceFill([
                    'status' => Invoice::STATUS_PAID,
                    'gateway_transaction_id' => $result->transactionId,
                    'paid_at' => now(),
                ])->save();

                $this->subscriptionService->renewPeriod($subscription);
                PaymentSucceeded::dispatch($invoice);
                Log::channel('billing')->info('Recurring payment succeeded.', ['invoice_id' => $invoice->id]);

                return $invoice;
            }

            $invoice->forceFill([
                'status' => Invoice::STATUS_FAILED,
                'failure_reason' => $result->failureReason,
            ])->save();

            $subscription->forceFill(['status' => Subscription::STATUS_PAST_DUE])->save();
            PaymentFailed::dispatch($invoice);
            Log::channel('billing')->warning('Recurring payment failed.', [
                'invoice_id' => $invoice->id,
                'reason' => $result->failureReason,
            ]);

            return $invoice;
        });
    }
}
