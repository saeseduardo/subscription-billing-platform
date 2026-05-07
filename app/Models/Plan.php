<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price_cents',
        'currency',
        'billing_interval',
        'trial_days',
        'features',
    ];

    protected $casts = [
        'features' => 'array',
        'price_cents' => 'integer',
        'trial_days' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
