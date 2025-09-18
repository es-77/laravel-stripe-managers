<?php

namespace EmmanuelSaleem\LaravelStripeManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeProductPricing extends Model
{
    protected $table = 'em_stripe_product_pricing';

    protected $fillable = [
        'product_id',
        'stripe_price_id',
        'nickname',
        'unit_amount',
        'currency',
        'type',
        'billing_period',
        'billing_period_count',
        'trial_period_days',
        'active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'active' => 'boolean',
        'unit_amount' => 'integer',
        'billing_period_count' => 'integer',
        'trial_period_days' => 'integer'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StripeProduct::class, 'product_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StripeSubscription::class, 'pricing_id');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->unit_amount / 100, 2);
    }

    public function getFormattedPriceAttribute(): string
    {
        $amount = $this->getFormattedAmountAttribute();
        $currency = strtoupper($this->currency);

        if ($this->type === 'recurring') {
            $interval = $this->billing_period_count > 1
                ? $this->billing_period_count . ' ' . \Str::plural($this->billing_period)
                : $this->billing_period;
            return "{$currency} {$amount} / {$interval}";
        }

        return "{$currency} {$amount}";
    }
}
