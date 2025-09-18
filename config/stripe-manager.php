<?php

return [
    'stripe' => [
        'model' => env('STRIPE_MODEL', App\Models\User::class),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'endpoint' => env('STRIPE_WEBHOOK_ENDPOINT', url('/stripe-manager/webhooks/handle')),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],
    
    'currency' => env('CASHIER_CURRENCY', 'usd'),
    
    'trial_days' => 14,
    
    'routes' => [
        'prefix' => 'stripe-manager',
        'middleware' => ['web', 'auth'],
    ]
];