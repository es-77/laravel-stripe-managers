<?php
// packages/emmanuelsaleem/laravel-stripe-manager/src/Controllers/ProductController.php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

class ProductController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('cashier.secret'));
    }

    public function index()
    {
        $products = StripeProduct::with('pricing')->paginate(10);
        return view('stripe-manager::products.index', compact('products'));
    }

    public function create()
    {
        return view('stripe-manager::products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prices' => 'required|array|min:1',
            'prices.*.amount' => 'required|numeric|min:0',
            'prices.*.currency' => 'required|string|size:3',
            'prices.*.interval' => 'nullable|in:day,week,month,year',
            'prices.*.interval_count' => 'nullable|integer|min:1',
        ]);

        try {
            // Create product in Stripe
            $stripeProduct = Product::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            // Save product locally
            $product = StripeProduct::create([
                'stripe_id' => $stripeProduct->id,
                'name' => $request->name,
                'description' => $request->description,
                'active' => true,
            ]);

            // Create prices
            foreach ($request->prices as $priceData) {
                $stripePriceData = [
                    'unit_amount' => $priceData['amount'] * 100, // Convert to cents
                    'currency' => $priceData['currency'],
                    'product' => $stripeProduct->id,
                ];

                if (!empty($priceData['interval'])) {
                    $stripePriceData['recurring'] = [
                        'interval' => $priceData['interval'],
                        'interval_count' => $priceData['interval_count'] ?? 1,
                    ];
                }

                $stripePrice = Price::create($stripePriceData);

                StripeProductPricing::create([
                    'product_id' => $product->id,
                    'stripe_price_id' => $stripePrice->id,
                    'amount' => $priceData['amount'] * 100,
                    'currency' => $priceData['currency'],
                    'interval' => $priceData['interval'],
                    'interval_count' => $priceData['interval_count'] ?? 1,
                    'active' => true,
                ]);
            }

            return redirect()->route('stripe-manager.products.index')
                ->with('success', 'Product created successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error creating product: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(StripeProduct $product)
    {
        $product->load('pricing', 'subscriptions.user');
        return view('stripe-manager::products.show', compact('product'));
    }

    public function edit(StripeProduct $product)
    {
        $product->load('pricing');
        return view('stripe-manager::products.edit', compact('product'));
    }

    public function update(Request $request, StripeProduct $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
        ]);

        try {
            // Update product in Stripe
            Product::update($product->stripe_id, [
                'name' => $request->name,
                'description' => $request->description,
                'active' => $request->boolean('active'),
            ]);

            // Update product locally
            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'active' => $request->boolean('active'),
            ]);

            return redirect()->route('stripe-manager.products.index')
                ->with('success', 'Product updated successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error updating product: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(StripeProduct $product)
    {
        try {
            // Archive product in Stripe (can't delete products with prices)
            Product::update($product->stripe_id, ['active' => false]);
            
            // Update locally
            $product->update(['active' => false]);

            return redirect()->route('stripe-manager.products.index')
                ->with('success', 'Product archived successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error archiving product: ' . $e->getMessage());
        }
    }

    /**
     * Sync all products from Stripe
     */
    public function sync()
    {
        try {
            $syncedCount = 0;
            $updatedCount = 0;
            
            // Fetch all products from Stripe
            $stripeProducts = Product::all(['limit' => 100]);
            
            foreach ($stripeProducts->data as $stripeProduct) {
                // Check if product already exists locally
                $localProduct = StripeProduct::where('stripe_id', $stripeProduct->id)->first();
                
                if ($localProduct) {
                    // Update existing product
                    $localProduct->update([
                        'name' => $stripeProduct->name,
                        'description' => $stripeProduct->description,
                        'active' => $stripeProduct->active,
                        'metadata' => $stripeProduct->metadata ? $stripeProduct->metadata->toArray() : null,
                    ]);
                    $updatedCount++;
                } else {
                    // Create new product
                    StripeProduct::create([
                        'stripe_id' => $stripeProduct->id,
                        'name' => $stripeProduct->name,
                        'description' => $stripeProduct->description,
                        'active' => $stripeProduct->active,
                        'metadata' => $stripeProduct->metadata ? $stripeProduct->metadata->toArray() : null,
                    ]);
                    $syncedCount++;
                }
                
                // Sync pricing for this product
                $this->syncProductPricing($stripeProduct->id);
            }
            
            $message = "Sync completed! ";
            if ($syncedCount > 0) {
                $message .= "{$syncedCount} new products added. ";
            }
            if ($updatedCount > 0) {
                $message .= "{$updatedCount} existing products updated. ";
            }
            if ($syncedCount == 0 && $updatedCount == 0) {
                $message .= "No changes needed - all products are up to date.";
            }
            
            return redirect()->route('stripe-manager.products.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            return redirect()->route('stripe-manager.products.index')
                ->with('error', 'Error syncing products: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync pricing for a specific product
     */
    private function syncProductPricing($stripeProductId)
    {
        try {
            // Fetch all prices for this product
            $prices = Price::all(['product' => $stripeProductId, 'limit' => 100]);
            
            foreach ($prices->data as $price) {
                $localProduct = StripeProduct::where('stripe_id', $stripeProductId)->first();
                
                if ($localProduct) {
                    // Check if pricing already exists
                    $existingPricing = StripeProductPricing::where('stripe_price_id', $price->id)->first();
                    
                    if ($existingPricing) {
                        // Update existing pricing
                        $existingPricing->update([
                            'nickname' => $price->nickname,
                            'unit_amount' => $price->unit_amount,
                            'currency' => $price->currency,
                            'type' => $price->type,
                            'billing_period' => $price->recurring->interval ?? null,
                            'billing_period_count' => $price->recurring->interval_count ?? null,
                            'trial_period_days' => $price->recurring->trial_period_days ?? null,
                            'active' => $price->active,
                            'metadata' => $price->metadata ? $price->metadata->toArray() : null,
                        ]);
                    } else {
                        // Create new pricing
                        StripeProductPricing::create([
                            'product_id' => $localProduct->id,
                            'stripe_price_id' => $price->id,
                            'nickname' => $price->nickname,
                            'unit_amount' => $price->unit_amount,
                            'currency' => $price->currency,
                            'type' => $price->type,
                            'billing_period' => $price->recurring->interval ?? null,
                            'billing_period_count' => $price->recurring->interval_count ?? null,
                            'trial_period_days' => $price->recurring->trial_period_days ?? null,
                            'active' => $price->active,
                            'metadata' => $price->metadata ? $price->metadata->toArray() : null,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but don't stop the sync process
            \Log::error('Error syncing pricing for product ' . $stripeProductId . ': ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new pricing plan
     */
    public function createPricing(StripeProduct $product)
    {
        return view('stripe-manager::products.pricing.create', compact('product'));
    }

    /**
     * Store a newly created pricing plan
     */
    public function storePricing(Request $request, StripeProduct $product)
    {
        $request->validate([
            'nickname' => 'nullable|string|max:255',
            'unit_amount' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
            'type' => 'required|in:one_time,recurring',
            'billing_period' => 'required_if:type,recurring|in:day,week,month,year',
            'billing_period_count' => 'nullable|integer|min:1',
            'trial_period_days' => 'nullable|integer|min:0',
            'active' => 'boolean',
        ]);

        try {
            // Create pricing in Stripe
            $stripePrice = \Stripe\Price::create([
                'product' => $product->stripe_id,
                'unit_amount' => $request->unit_amount,
                'currency' => $request->currency,
                'nickname' => $request->nickname,
                'recurring' => $request->type === 'recurring' ? [
                    'interval' => $request->billing_period,
                    'interval_count' => $request->billing_period_count ?? 1,
                    'trial_period_days' => $request->trial_period_days,
                ] : null,
                'active' => $request->active ?? true,
            ]);

            // Create local pricing record
            StripeProductPricing::create([
                'product_id' => $product->id,
                'stripe_price_id' => $stripePrice->id,
                'nickname' => $request->nickname,
                'unit_amount' => $request->unit_amount,
                'currency' => $request->currency,
                'type' => $request->type,
                'billing_period' => $request->billing_period,
                'billing_period_count' => $request->billing_period_count ?? 1,
                'trial_period_days' => $request->trial_period_days,
                'active' => $request->active ?? true,
            ]);

            return redirect()->route('stripe-manager.products.show', $product)
                ->with('success', 'Pricing plan created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating pricing plan: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a pricing plan
     */
    public function editPricing(StripeProductPricing $pricing)
    {
        return view('stripe-manager::products.pricing.edit', compact('pricing'));
    }

    /**
     * Update a pricing plan
     */
    public function updatePricing(Request $request, StripeProductPricing $pricing)
    {
        $request->validate([
            'nickname' => 'nullable|string|max:255',
            'active' => 'boolean',
        ]);

        try {
            // Update pricing in Stripe
            \Stripe\Price::update($pricing->stripe_price_id, [
                'nickname' => $request->nickname,
                'active' => $request->active ?? $pricing->active,
            ]);

            // Update local pricing record
            $pricing->update([
                'nickname' => $request->nickname,
                'active' => $request->active ?? $pricing->active,
            ]);

            return redirect()->route('stripe-manager.products.show', $pricing->product)
                ->with('success', 'Pricing plan updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating pricing plan: ' . $e->getMessage());
        }
    }

    /**
     * Remove a pricing plan
     */
    public function destroyPricing(StripeProductPricing $pricing)
    {
        try {
            // Deactivate pricing in Stripe (can't delete active prices)
            \Stripe\Price::update($pricing->stripe_price_id, [
                'active' => false,
            ]);

            // Update local pricing record
            $pricing->update(['active' => false]);

            return redirect()->route('stripe-manager.products.show', $pricing->product)
                ->with('success', 'Pricing plan deactivated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deactivating pricing plan: ' . $e->getMessage());
        }
    }
}
