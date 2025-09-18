
<?php

use Illuminate\Support\Facades\Route;
use EmmanuelSaleem\LaravelStripeManager\Controllers\ProductController;
use EmmanuelSaleem\LaravelStripeManager\Controllers\CustomerController;
use EmmanuelSaleem\LaravelStripeManager\Controllers\SubscriptionController;
use EmmanuelSaleem\LaravelStripeManager\Controllers\WebhookController;

$config = config('stripe-manager.routes');

Route::group([
    'prefix' => $config['prefix'],
    'middleware' => $config['middleware'],
], function () {

    // Dashboard
    Route::get('/', function () {
        return view('stripe-manager::layouts.dashboard');
    })->name('stripe-manager.dashboard');

    // Products
    Route::resource('products', ProductController::class)->names([
        'index' => 'stripe-manager.products.index',
        'create' => 'stripe-manager.products.create',
        'store' => 'stripe-manager.products.store',
        'show' => 'stripe-manager.products.show',
        'edit' => 'stripe-manager.products.edit',
        'update' => 'stripe-manager.products.update',
        'destroy' => 'stripe-manager.products.destroy',
    ]);
    
    // Product sync route
    Route::get('products-sync', [ProductController::class, 'sync'])->name('stripe-manager.products.sync');
    
    // Product pricing routes
    Route::get('products/{product}/pricing/create', [ProductController::class, 'createPricing'])
        ->name('stripe-manager.products.pricing.create');
    Route::post('products/{product}/pricing', [ProductController::class, 'storePricing'])
        ->name('stripe-manager.products.pricing.store');
    Route::get('products/pricing/{pricing}/edit', [ProductController::class, 'editPricing'])
        ->name('stripe-manager.products.pricing.edit');
    Route::put('products/pricing/{pricing}', [ProductController::class, 'updatePricing'])
        ->name('stripe-manager.products.pricing.update');
    Route::delete('products/pricing/{pricing}', [ProductController::class, 'destroyPricing'])
        ->name('stripe-manager.products.pricing.destroy');

    // Customers
    Route::resource('customers', CustomerController::class)->names([
        'index' => 'stripe-manager.customers.index',
        'create' => 'stripe-manager.customers.create',
        'store' => 'stripe-manager.customers.store',
        'show' => 'stripe-manager.customers.show',
    ]);

    Route::get('customers/{customer}/setup-payment', [CustomerController::class, 'setupPaymentMethod'])
        ->name('stripe-manager.customers.setup-payment');
    Route::post('customers/{customer}/payment-methods', [CustomerController::class, 'storePaymentMethod'])
        ->name('stripe-manager.customers.store-payment-method');
    Route::delete('customers/{customer}/payment-methods', [CustomerController::class, 'removePaymentMethod'])
        ->name('stripe-manager.customers.remove-payment-method');
    Route::patch('customers/{customer}/payment-methods/default', [CustomerController::class, 'setDefaultPaymentMethod'])
        ->name('stripe-manager.customers.set-default-payment-method');

    // Subscriptions
    Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'create', 'store', 'show'])
        ->names([
            'index' => 'stripe-manager.subscriptions.index',
            'create' => 'stripe-manager.subscriptions.create',
            'store' => 'stripe-manager.subscriptions.store',
            'show' => 'stripe-manager.subscriptions.show',
        ]);

    Route::post('subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])
        ->name('stripe-manager.subscriptions.cancel');
    Route::post('subscriptions/{subscription}/resume', [SubscriptionController::class, 'resume'])
        ->name('stripe-manager.subscriptions.resume');
    
    // Trial period management
    Route::get('subscriptions/{subscription}/trial', [SubscriptionController::class, 'editTrial'])
        ->name('stripe-manager.subscriptions.trial.edit');
    Route::put('subscriptions/{subscription}/trial', [SubscriptionController::class, 'updateTrial'])
        ->name('stripe-manager.subscriptions.trial.update');
    
    // Webhook management
    Route::get('webhooks', [WebhookController::class, 'index'])
        ->name('stripe-manager.webhooks.index');
    Route::post('webhooks/test', [WebhookController::class, 'test'])
        ->name('stripe-manager.webhooks.test');
    Route::get('webhooks/logs', [WebhookController::class, 'logs'])
        ->name('stripe-manager.webhooks.logs');
});
