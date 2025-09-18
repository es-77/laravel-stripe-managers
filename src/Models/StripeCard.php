<?php

namespace EmmanuelSaleem\LaravelStripeManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use App\Models\User; // Will use configurable model

class StripeCard extends Model
{
    protected $table = 'em_stripe_cards';

    protected $fillable = [
        'user_id',
        'stripe_payment_method_id',
        'brand',
        'last_four',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'exp_month' => 'integer',
        'exp_year' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('stripe-manager.stripe.model'));
    }

    /**
     * Get formatted expiry date
     */
    public function getFormattedExpiryAttribute(): string
    {
        return sprintf('%02d/%d', $this->exp_month, $this->exp_year);
    }

    /**
     * Get masked card number
     */
    public function getMaskedNumberAttribute(): string
    {
        return "**** **** **** {$this->last_four}";
    }

    /**
     * Check if card is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        if ($this->exp_year < $currentYear) {
            return true;
        }

        if ($this->exp_year == $currentYear && $this->exp_month < $currentMonth) {
            return true;
        }

        return false;
    }

    /**
     * Get brand icon class for UI
     */
    public function getBrandIconAttribute(): string
    {
        $brandIcons = [
            'visa' => 'fab fa-cc-visa',
            'mastercard' => 'fab fa-cc-mastercard',
            'amex' => 'fab fa-cc-amex',
            'discover' => 'fab fa-cc-discover',
            'diners' => 'fab fa-cc-diners-club',
            'jcb' => 'fab fa-cc-jcb',
            'unionpay' => 'fas fa-credit-card',
        ];

        return $brandIcons[strtolower($this->brand)] ?? 'fas fa-credit-card';
    }
}
