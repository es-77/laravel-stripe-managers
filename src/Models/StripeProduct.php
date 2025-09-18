<?php

namespace EmmanuelSaleem\LaravelStripeManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StripeProduct extends Model
{
    protected $table = 'em_stripe_products';
    
    protected $fillable = [
        'stripe_id',
        'name',
        'description',
        'active',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'active' => 'boolean'
    ];

    public function pricing(): HasMany
    {
        return $this->hasMany(StripeProductPricing::class, 'product_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StripeSubscription::class, 'product_id');
    }
}