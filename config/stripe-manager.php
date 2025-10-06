<?php

return [
    'stripe' => [
        'model' => env('STRIPE_MODEL', App\Models\User::class),
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        // Query limits for Stripe API listings
        'limits' => [
            'subscriptions' => env('STRIPE_LIST_LIMIT_SUBSCRIPTIONS', 10),
            'invoices' => env('STRIPE_LIST_LIMIT_INVOICES', 10),
            'charges' => env('STRIPE_LIST_LIMIT_CHARGES', 8),
            'events' => env('STRIPE_LIST_LIMIT_EVENTS', 20),
        ],
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            // Do NOT call url() here; it triggers UrlGenerator during console/package discovery
            // Prefer explicit STRIPE_WEBHOOK_ENDPOINT, else derive from APP_URL without helpers
            'endpoint' => env('STRIPE_WEBHOOK_ENDPOINT', rtrim(env('APP_URL', ''), '/') . '/stripe-manager/webhooks/handle'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    // User model column mapping for search/listing
    'user' => [
        'columns' => [
            // Underlying DB column that represents the user's display name
            'name' => env('STRIPE_USER_NAME_COLUMN', 'name'),
            // Underlying DB column that represents the user's email
            'email' => env('STRIPE_USER_EMAIL_COLUMN', 'email'),
        ],
    ],
    
    'currency' => env('CASHIER_CURRENCY', 'usd'),
    
    'trial_days' => 14,
    
    'routes' => [
        'prefix' => 'stripe-manager',
        'middleware' => ['web', 'auth'],
    ]
];