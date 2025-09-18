<?php

namespace EmmanuelSaleem\LaravelStripeManager;

use Illuminate\Support\ServiceProvider;
use EmmanuelSaleem\LaravelStripeManager\Services\CustomerService;
use EmmanuelSaleem\LaravelStripeManager\Services\ProductService;
use EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService;

class StripeManagerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/stripe-manager.php', 'stripe-manager'
        );

        // Register services
        $this->app->singleton(CustomerService::class, function ($app) {
            return new CustomerService();
        });

        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService();
        });

        $this->app->singleton(SubscriptionService::class, function ($app) {
            return new SubscriptionService();
        });

        // Register aliases
        $this->app->alias(CustomerService::class, 'stripe-manager.customer');
        $this->app->alias(ProductService::class, 'stripe-manager.product');
        $this->app->alias(SubscriptionService::class, 'stripe-manager.subscription');
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/Routes/webhook.php');
        $this->loadViewsFrom(__DIR__.'/Views', 'stripe-manager');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');

        $this->publishes([
            __DIR__.'/../config/stripe-manager.php' => config_path('stripe-manager.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/Views' => resource_path('views/vendor/stripe-manager'),
        ], 'views');

        $this->publishes([
            __DIR__.'/Migrations' => database_path('migrations'),
        ], 'migrations');
    }
}
