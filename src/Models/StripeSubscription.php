<?php

namespace EmmanuelSaleem\LaravelStripeManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use App\Models\User; // Will use configurable model

class StripeSubscription extends Model
{
    protected $table = 'em_stripe_subscriptions';

    protected $fillable = [
        'user_id',
        'product_id',
        'pricing_id',
        'stripe_subscription_id',
        'stripe_status',
        'current_period_start',
        'current_period_end',
        'trial_start',
        'trial_end',
        'trial_ends_at',
        'canceled_at',
        'ends_at',
        'quantity',
        'metadata'
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'ends_at' => 'datetime',
        'quantity' => 'integer',
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('stripe-manager.stripe.model'));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StripeProduct::class, 'product_id');
    }

    public function pricing(): BelongsTo
    {
        return $this->belongsTo(StripeProductPricing::class, 'pricing_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StripeSubscriptionPayment::class, 'subscription_id');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return in_array($this->stripe_status, ['active', 'trialing']);
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial(): bool
    {
        return $this->stripe_status === 'trialing' &&
               $this->trial_end &&
               $this->trial_end->isFuture();
    }

    /**
     * Check if subscription is cancelled
     */
    public function cancelled(): bool
    {
        return $this->stripe_status === 'canceled' ||
               ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Check if subscription will cancel at period end
     */
    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        $statusMap = [
            'incomplete' => 'Incomplete',
            'incomplete_expired' => 'Incomplete Expired',
            'trialing' => 'On Trial',
            'active' => 'Active',
            'past_due' => 'Past Due',
            'canceled' => 'Cancelled',
            'unpaid' => 'Unpaid',
        ];

        return $statusMap[$this->stripe_status] ?? ucfirst($this->stripe_status);
    }
}
