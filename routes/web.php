<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Subscription Billing Platform',
        'status' => 'ready',
        'docs' => 'See README.md',
    ]);
});
