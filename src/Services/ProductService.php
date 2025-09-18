<?php

namespace EmmanuelSaleem\LaravelStripeManager\Services;

use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class ProductService
{
    public function __construct()
    {
        Stripe::setApiKey(config('stripe-manager.stripe.secret'));
    }

    /**
     * Create a product in Stripe and local database
     */
    public function createProduct(array $data): StripeProduct
    {
        try {
            // Create product in Stripe
            $stripeProduct = Product::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Store in local database
            $product = StripeProduct::create([
                'stripe_id' => $stripeProduct->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,
                'metadata' => $data['metadata'] ?? [],
            ]);

            Log::info('Product created', [
                'product_id' => $product->id,
                'stripe_product_id' => $stripeProduct->id
            ]);

            return $product;
        } catch (\Exception $e) {
            Log::error('Failed to create product', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update a product in Stripe and local database
     */
    public function updateProduct(StripeProduct $product, array $data): StripeProduct
    {
        try {
            // Update product in Stripe
            Product::update($product->stripe_id, [
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'active' => $data['active'] ?? $product->active,
                'metadata' => $data['metadata'] ?? $product->metadata,
            ]);

            // Update in local database
            $product->update([
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'active' => $data['active'] ?? $product->active,
                'metadata' => $data['metadata'] ?? $product->metadata,
            ]);

            Log::info('Product updated', [
                'product_id' => $product->id,
                'stripe_product_id' => $product->stripe_id
            ]);

            return $product->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update product', [
                'product_id' => $product->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Delete a product (archive in Stripe)
     */
    public function deleteProduct(StripeProduct $product): bool
    {
        try {
            // Archive product in Stripe (can't delete, only archive)
            Product::update($product->stripe_id, ['active' => false]);

            // Update local database
            $product->update(['active' => false]);

            Log::info('Product archived', [
                'product_id' => $product->id,
                'stripe_product_id' => $product->stripe_id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to archive product', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create pricing for a product
     */
    public function createProductPrice(StripeProduct $product, array $priceData): StripeProductPricing
    {
        try {
            // Create price in Stripe
            $stripePrice = Price::create([
                'product' => $product->stripe_id,
                'unit_amount' => $priceData['unit_amount'], // Amount in cents
                'currency' => $priceData['currency'] ?? config('stripe-manager.currency', 'usd'),
                'recurring' => $priceData['recurring'] ?? null,
                'nickname' => $priceData['nickname'] ?? null,
                'active' => $priceData['active'] ?? true,
                'metadata' => $priceData['metadata'] ?? [],
            ]);

            // Store in local database
            $pricing = StripeProductPricing::create([
                'product_id' => $product->id,
                'stripe_price_id' => $stripePrice->id,
                'nickname' => $priceData['nickname'] ?? null,
                'unit_amount' => $priceData['unit_amount'],
                'currency' => $priceData['currency'] ?? config('stripe-manager.currency', 'usd'),
                'type' => isset($priceData['recurring']) ? 'recurring' : 'one_time',
                'billing_period' => $priceData['recurring']['interval'] ?? null,
                'billing_period_count' => $priceData['recurring']['interval_count'] ?? null,
                'trial_period_days' => $priceData['recurring']['trial_period_days'] ?? null,
                'active' => $priceData['active'] ?? true,
                'metadata' => $priceData['metadata'] ?? [],
            ]);

            Log::info('Product pricing created', [
                'product_id' => $product->id,
                'pricing_id' => $pricing->id,
                'stripe_price_id' => $stripePrice->id
            ]);

            return $pricing;
        } catch (\Exception $e) {
            Log::error('Failed to create product pricing', [
                'product_id' => $product->id,
                'price_data' => $priceData,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update pricing for a product
     */
    public function updateProductPrice(StripeProductPricing $pricing, array $data): StripeProductPricing
    {
        try {
            // Update price in Stripe (only active and metadata can be updated)
            Price::update($pricing->stripe_price_id, [
                'active' => $data['active'] ?? $pricing->active,
                'nickname' => $data['nickname'] ?? $pricing->nickname,
                'metadata' => $data['metadata'] ?? $pricing->metadata,
            ]);

            // Update in local database
            $pricing->update([
                'nickname' => $data['nickname'] ?? $pricing->nickname,
                'active' => $data['active'] ?? $pricing->active,
                'metadata' => $data['metadata'] ?? $pricing->metadata,
            ]);

            Log::info('Product pricing updated', [
                'pricing_id' => $pricing->id,
                'stripe_price_id' => $pricing->stripe_price_id
            ]);

            return $pricing->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update product pricing', [
                'pricing_id' => $pricing->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Archive a price (set as inactive)
     */
    public function archivePrice(StripeProductPricing $pricing): bool
    {
        try {
            // Deactivate price in Stripe
            Price::update($pricing->stripe_price_id, ['active' => false]);

            // Update local database
            $pricing->update(['active' => false]);

            Log::info('Product pricing archived', [
                'pricing_id' => $pricing->id,
                'stripe_price_id' => $pricing->stripe_price_id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to archive product pricing', [
                'pricing_id' => $pricing->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync products from Stripe to local database
     */
    public function syncProductsFromStripe(int $limit = 100): array
    {
        try {
            $stripeProducts = Product::all(['limit' => $limit]);
            $synced = [];

            foreach ($stripeProducts->data as $stripeProduct) {
                $product = StripeProduct::firstOrCreate(
                    ['stripe_id' => $stripeProduct->id],
                    [
                        'name' => $stripeProduct->name,
                        'description' => $stripeProduct->description,
                        'active' => $stripeProduct->active,
                        'metadata' => $stripeProduct->metadata->toArray(),
                    ]
                );

                $synced[] = $product;
            }

            Log::info('Products synced from Stripe', [
                'count' => count($synced)
            ]);

            return $synced;
        } catch (\Exception $e) {
            Log::error('Failed to sync products from Stripe', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync prices from Stripe to local database
     */
    public function syncPricesFromStripe(StripeProduct $product): array
    {
        try {
            $stripePrices = Price::all([
                'product' => $product->stripe_id,
                'limit' => 100
            ]);

            $synced = [];

            foreach ($stripePrices->data as $stripePrice) {
                $pricing = StripeProductPricing::firstOrCreate(
                    ['stripe_price_id' => $stripePrice->id],
                    [
                        'product_id' => $product->id,
                        'nickname' => $stripePrice->nickname,
                        'unit_amount' => $stripePrice->unit_amount,
                        'currency' => $stripePrice->currency,
                        'type' => $stripePrice->type,
                        'billing_period' => $stripePrice->recurring->interval ?? null,
                        'billing_period_count' => $stripePrice->recurring->interval_count ?? null,
                        'trial_period_days' => $stripePrice->recurring->trial_period_days ?? null,
                        'active' => $stripePrice->active,
                        'metadata' => $stripePrice->metadata->toArray(),
                    ]
                );

                $synced[] = $pricing;
            }

            Log::info('Prices synced from Stripe', [
                'product_id' => $product->id,
                'count' => count($synced)
            ]);

            return $synced;
        } catch (\Exception $e) {
            Log::error('Failed to sync prices from Stripe', [
                'product_id' => $product->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
