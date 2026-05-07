<?php

use App\Jobs\ProcessRecurringBillingJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ProcessRecurringBillingJob)->dailyAt('02:00');
