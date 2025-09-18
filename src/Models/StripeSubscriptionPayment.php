<?php

namespace EmmanuelSaleem\LaravelStripeManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeSubscriptionPayment extends Model
{
    protected $table = 'em_stripe_subscription_payments';

    protected $fillable = [
        'subscription_id',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'payment_date',
        'period_start',
        'period_end',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'payment_date' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'amount' => 'integer'
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(StripeSubscription::class, 'subscription_id');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function getFormattedCurrencyAmountAttribute(): string
    {
        return strtoupper($this->currency) . ' ' . $this->getFormattedAmountAttribute();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $classes = [
            'paid' => 'badge-success',
            'failed' => 'badge-danger',
            'pending' => 'badge-warning',
            'canceled' => 'badge-secondary',
        ];

        return $classes[$this->status] ?? 'badge-secondary';
    }
}
