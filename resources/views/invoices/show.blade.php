<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; }
        .header { border-bottom: 2px solid #111827; margin-bottom: 24px; padding-bottom: 12px; }
        .total { font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Invoice {{ $invoice->number }}</h1>
        <p>Status: {{ strtoupper($invoice->status) }}</p>
    </div>
    <p>Plan: {{ $invoice->subscription->plan->name }}</p>
    <p>Period ending: {{ $invoice->subscription->current_period_ends_at->toDateString() }}</p>
    <p class="total">Total: {{ $invoice->currency }} {{ number_format($invoice->amount_cents / 100, 2) }}</p>
</body>
</html>
