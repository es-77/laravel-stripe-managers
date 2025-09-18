<?php

// Example usage of Laravel Stripe Manager Package

use App\Models\User;
use EmmanuelSaleem\LaravelStripeManager\Services\CustomerService;
use EmmanuelSaleem\LaravelStripeManager\Services\ProductService;
use EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;

class StripeManagerExamples
{
    protected $customerService;
    protected $productService;
    protected $subscriptionService;

    public function __construct()
    {
        $this->customerService = app(CustomerService::class);
        $this->productService = app(ProductService::class);
        $this->subscriptionService = app(SubscriptionService::class);
    }

    /**
     * Example 1: Create a customer and store payment method
     */
    public function createCustomerWithCard()
    {
        $user = User::find(1);

        // Step 1: Create Stripe customer
        $customer = $this->customerService->createCustomer($user, [
            'name' => $user->name,
            'email' => $user->email,
            'metadata' => [
                'user_id' => $user->id,
                'plan_type' => 'premium'
            ]
        ]);

        // Step 2: Create setup intent for card collection
        $setupIntent = $this->customerService->createSetupIntent($customer);

        // Use $setupIntent->client_secret in frontend to collect payment method
        // After payment method is collected, store it:

        // $paymentMethodId = 'pm_1234567890'; // From frontend
        // $card = $this->customerService->storePaymentMethod($customer, $paymentMethodId, true);

        return $setupIntent;
    }

    /**
     * Example 2: Create a product with multiple pricing tiers
     */
    public function createProductWithPricing()
    {
        // Create product
        $product = $this->productService->createProduct([
            'name' => 'SaaS Subscription',
            'description' => 'Complete SaaS platform access',
            'active' => true,
            'metadata' => [
                'category' => 'software',
                'features' => 'unlimited_users,api_access,support'
            ]
        ]);

        // Create monthly pricing
        $monthlyPricing = $this->productService->createProductPrice($product, [
            'unit_amount' => 2999, // $29.99
            'currency' => 'usd',
            'recurring' => [
                'interval' => 'month',
                'interval_count' => 1,
                'trial_period_days' => 14
            ],
            'nickname' => 'Monthly Plan'
        ]);

        // Create yearly pricing (with discount)
        $yearlyPricing = $this->productService->createProductPrice($product, [
            'unit_amount' => 29999, // $299.99 (save $59.89)
            'currency' => 'usd',
            'recurring' => [
                'interval' => 'year',
                'interval_count' => 1,
                'trial_period_days' => 14
            ],
            'nickname' => 'Yearly Plan'
        ]);

        // Create one-time setup fee
        $setupFee = $this->productService->createProductPrice($product, [
            'unit_amount' => 9999, // $99.99
            'currency' => 'usd',
            'nickname' => 'Setup Fee'
        ]);

        return [
            'product' => $product,
            'monthly' => $monthlyPricing,
            'yearly' => $yearlyPricing,
            'setup' => $setupFee
        ];
    }

    /**
     * Example 3: Create subscription with trial
     */
    public function createSubscriptionWithTrial()
    {
        $user = User::find(1);
        $pricing = StripeProductPricing::find(1);

        $subscription = $this->subscriptionService->createSubscription($user, $pricing, [
            'trial_days' => 14,
            'payment_method' => 'pm_1234567890', // Customer's default payment method
            'metadata' => [
                'source' => 'website',
                'campaign' => 'summer_sale'
            ]
        ]);

        return $subscription;
    }

    /**
     * Example 4: Subscription lifecycle management
     */
    public function manageSubscriptionLifecycle()
    {
        $user = User::find(1);
        $subscription = $user->subscriptions()->first();

        // Check subscription status
        if ($subscription->isActive()) {
            echo "Subscription is active\n";
        }

        if ($subscription->onTrial()) {
            echo "Subscription is on trial until: " . $subscription->trial_end . "\n";
        }

        // Cancel subscription at period end
        $this->subscriptionService->cancelSubscription($subscription, false);

        // Resume cancelled subscription
        if ($subscription->onGracePeriod()) {
            $this->subscriptionService->resumeSubscription($subscription);
        }

        // Change subscription plan
        $newPricing = StripeProductPricing::find(2);
        $this->subscriptionService->changePlan($subscription, $newPricing, [
            'proration_behavior' => 'create_prorations'
        ]);

        // Cancel immediately
        $this->subscriptionService->cancelSubscription($subscription, true);
    }

    /**
     * Example 5: Handle multiple payment methods
     */
    public function managePaymentMethods()
    {
        $user = User::find(1);

        // Get all stored cards
        $cards = $user->stripeCards; // Relationship method needed in User model

        foreach ($cards as $card) {
            echo "Card: {$card->masked_number} ({$card->brand})\n";
            echo "Expires: {$card->formatted_expiry}\n";
            echo "Default: " . ($card->is_default ? 'Yes' : 'No') . "\n\n";
        }

        // Set a different card as default
        $cardToMakeDefault = $cards->where('is_default', false)->first();
        if ($cardToMakeDefault) {
            $this->customerService->setDefaultPaymentMethod(
                $user,
                $cardToMakeDefault->stripe_payment_method_id
            );
        }

        // Remove a payment method
        $cardToRemove = $cards->last();
        if ($cardToRemove && !$cardToRemove->is_default) {
            $this->customerService->removePaymentMethod(
                $user,
                $cardToRemove->stripe_payment_method_id
            );
        }
    }

    /**
     * Example 6: Subscription usage and billing
     */
    public function handleSubscriptionUsage()
    {
        $user = User::find(1);
        $subscription = $user->subscriptions()->active()->first();

        if ($subscription) {
            // Get subscription usage
            $usage = $this->subscriptionService->getUsage($subscription);

            echo "Current Period: {$usage['period_start']} to {$usage['period_end']}\n";
            echo "Quantity: {$usage['quantity']}\n";
            echo "Status: {$usage['status']}\n";

            // Update subscription quantity
            $this->subscriptionService->updateSubscription($subscription, [
                'quantity' => 5,
                'proration_behavior' => 'create_prorations'
            ]);

            // Get payment history
            $payments = $subscription->payments()->orderBy('payment_date', 'desc')->get();

            foreach ($payments as $payment) {
                echo "Payment: {$payment->formatted_currency_amount} - {$payment->status}\n";
                echo "Date: {$payment->payment_date}\n";
                echo "Period: {$payment->period_start} to {$payment->period_end}\n\n";
            }
        }
    }

    /**
     * Example 7: Sync data from Stripe
     */
    public function syncDataFromStripe()
    {
        // Sync products from Stripe
        $products = $this->productService->syncProductsFromStripe(100);
        echo "Synced " . count($products) . " products\n";

        // Sync prices for a specific product
        $product = StripeProduct::first();
        if ($product) {
            $prices = $this->productService->syncPricesFromStripe($product);
            echo "Synced " . count($prices) . " prices for product: {$product->name}\n";
        }

        // Sync payment methods for a customer
        $user = User::whereNotNull('stripe_id')->first();
        if ($user) {
            $this->customerService->syncPaymentMethods($user);
            echo "Synced payment methods for user: {$user->name}\n";
        }

        // Sync a specific subscription
        $subscription = \EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription::first();
        if ($subscription) {
            $this->subscriptionService->syncSubscription($subscription->stripe_subscription_id);
            echo "Synced subscription: {$subscription->stripe_subscription_id}\n";
        }
    }

    /**
     * Example 8: Advanced product and pricing management
     */
    public function advancedProductManagement()
    {
        // Create tiered pricing product
        $product = $this->productService->createProduct([
            'name' => 'API Usage',
            'description' => 'Pay per API call',
            'active' => true
        ]);

        // Create usage-based pricing (for metered billing)
        $meteeredPricing = $this->productService->createProductPrice($product, [
            'unit_amount' => 10, // $0.10 per unit
            'currency' => 'usd',
            'recurring' => [
                'interval' => 'month',
                'usage_type' => 'metered'
            ],
            'nickname' => 'Per API Call'
        ]);

        // Create pricing with different billing cycles
        $weeklyPricing = $this->productService->createProductPrice($product, [
            'unit_amount' => 999, // $9.99
            'currency' => 'usd',
            'recurring' => [
                'interval' => 'week',
                'interval_count' => 2 // Every 2 weeks
            ],
            'nickname' => 'Bi-weekly'
        ]);

        // Update product
        $this->productService->updateProduct($product, [
            'description' => 'Updated description',
            'metadata' => [
                'updated_at' => now()->toISOString()
            ]
        ]);

        // Archive a price
        $this->productService->archivePrice($meteeredPricing);

        return [
            'product' => $product,
            'metered' => $meteeredPricing,
            'weekly' => $weeklyPricing
        ];
    }
}

// Usage in a controller or service

class SubscriptionController extends Controller
{
    public function createSubscription(Request $request)
    {
        $examples = new StripeManagerExamples();

        try {
            // Create customer if needed
            $user = auth()->user();
            if (!$user->hasStripeId()) {
                $examples->customerService->createCustomer($user);
            }

            // Create subscription
            $pricing = StripeProductPricing::findOrFail($request->pricing_id);
            $subscription = $examples->subscriptionService->createSubscription($user, $pricing, [
                'trial_days' => $request->trial_days ?? 0,
                'payment_method' => $request->payment_method
            ]);

            return redirect()->route('dashboard')->with('success', 'Subscription created successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating subscription: ' . $e->getMessage());
        }
    }
}
