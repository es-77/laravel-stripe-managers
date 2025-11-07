<?php

namespace EmmanuelSaleem\LaravelStripeManager\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupons';
    
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'duration',
        'duration_in_months',
        'start_date',
        'end_date',
        'status',
        'usage_limit',
        'minimum_amount',
        'maximum_discount',
        'stripe_coupon_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'duration_in_months' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'usage_limit' => 'integer',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
    ];
}

