<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

if (function_exists('pest')) {
    pest()->extend(Tests\TestCase::class)
        ->use(RefreshDatabase::class)
        ->in('Feature');
}
