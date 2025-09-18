<?php

use Illuminate\Support\Facades\Route;
use EmmanuelSaleem\LaravelStripeManager\Controllers\WebhookController;

// Stripe webhook routes - must be accessible without authentication
Route::post('/stripe/webhook', [WebhookController::class, 'handle'])
    ->name('stripe-manager.webhook')
    ->middleware(['api']);

// Alternative webhook route for stripe-manager prefix
Route::post('/stripe-manager/webhooks/handle', [WebhookController::class, 'handle'])
    ->name('stripe-manager.webhook.handle')
    ->middleware(['api']);
