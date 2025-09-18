<?php

namespace EmmanuelSaleem\LaravelStripeManager\Services;

// use App\Models\User; // Will use configurable model
use EmmanuelSaleem\LaravelStripeManager\Models\StripeCard;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;

class CustomerService
{
    /**
     * Get the configurable user model
     */
    protected function getUserModel()
    {
        return app(config('stripe-manager.stripe.model'));
    }

    public function __construct()
    {
        Stripe::setApiKey(config('stripe-manager.stripe.secret'));
    }

    /**
     * Create a Stripe customer
     */
    public function createCustomer($user, array $data = [])
    {
        try {
            $customerData = array_merge([
                'name' => $user->name,
                'email' => $user->email,
            ], $data);

            $stripeCustomer = Customer::create($customerData);

            $user->update(['stripe_id' => $stripeCustomer->id]);

            Log::info('Stripe customer created', [
                'user_id' => $user->id,
                'stripe_customer_id' => $stripeCustomer->id
            ]);

            return $user->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update a Stripe customer
     */
    public function updateCustomer($user, array $data)
    {
        try {
            if (!$user->hasStripeId()) {
                throw new \Exception('User does not have a Stripe customer ID');
            }

            Customer::update($user->stripe_id, $data);

            Log::info('Stripe customer updated', [
                'user_id' => $user->id,
                'stripe_customer_id' => $user->stripe_id
            ]);

            return $user;
        } catch (\Exception $e) {
            Log::error('Failed to update Stripe customer', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Store a payment method for customer
     */
    public function storePaymentMethod($user, string $paymentMethodId, bool $setAsDefault = false): StripeCard
    {
        try {
            if (!$user->hasStripeId()) {
                throw new \Exception('User does not have a Stripe customer ID');
            }

            // Attach payment method to customer
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $user->stripe_id]);

            // Set as default if requested
            if ($setAsDefault) {
                Customer::update($user->stripe_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethodId
                    ]
                ]);
            }

            // Store in local database
            $card = StripeCard::create([
                'user_id' => $user->id,
                'stripe_payment_method_id' => $paymentMethodId,
                'brand' => $paymentMethod->card->brand,
                'last_four' => $paymentMethod->card->last4,
                'exp_month' => $paymentMethod->card->exp_month,
                'exp_year' => $paymentMethod->card->exp_year,
                'is_default' => $setAsDefault,
            ]);

            // Update other cards to not be default if this one is set as default
            if ($setAsDefault) {
                StripeCard::where('user_id', $user->id)
                    ->where('id', '!=', $card->id)
                    ->update(['is_default' => false]);
            }

            Log::info('Payment method stored for customer', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'is_default' => $setAsDefault
            ]);

            return $card;
        } catch (\Exception $e) {
            Log::error('Failed to store payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove a payment method
     */
    public function removePaymentMethod($user, string $paymentMethodId): bool
    {
        try {
            // Detach from Stripe
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();

            // Remove from local database
            StripeCard::where('user_id', $user->id)
                ->where('stripe_payment_method_id', $paymentMethodId)
                ->delete();

            Log::info('Payment method removed for customer', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod($user, string $paymentMethodId): bool
    {
        try {
            if (!$user->hasStripeId()) {
                throw new \Exception('User does not have a Stripe customer ID');
            }

            // Update in Stripe
            Customer::update($user->stripe_id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId
                ]
            ]);

            // Update in local database
            StripeCard::where('user_id', $user->id)->update(['is_default' => false]);
            StripeCard::where('user_id', $user->id)
                ->where('stripe_payment_method_id', $paymentMethodId)
                ->update(['is_default' => true]);

            Log::info('Default payment method set for customer', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Create setup intent for payment method collection
     */
    public function createSetupIntent($user): SetupIntent
    {
        try {
            if (!$user->hasStripeId()) {
                throw new \Exception('User does not have a Stripe customer ID');
            }

            $setupIntent = SetupIntent::create([
                'customer' => $user->stripe_id,
                'payment_method_types' => ['card'],
                'usage' => 'off_session'
            ]);

            Log::info('Setup intent created for customer', [
                'user_id' => $user->id,
                'setup_intent_id' => $setupIntent->id
            ]);

            return $setupIntent;
        } catch (\Exception $e) {
            Log::error('Failed to create setup intent', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get customer's payment methods from Stripe
     */
    public function getPaymentMethods($user): array
    {
        try {
            if (!$user->hasStripeId()) {
                return [];
            }

            $paymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_id,
                'type' => 'card',
            ]);

            return $paymentMethods->data;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve payment methods', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Sync payment methods from Stripe to local database
     */
    public function syncPaymentMethods($user): void
    {
        try {
            $stripePaymentMethods = $this->getPaymentMethods($user);
            $existingPaymentMethodIds = StripeCard::where('user_id', $user->id)
                ->pluck('stripe_payment_method_id')
                ->toArray();

            foreach ($stripePaymentMethods as $paymentMethod) {
                if (!in_array($paymentMethod->id, $existingPaymentMethodIds)) {
                    StripeCard::create([
                        'user_id' => $user->id,
                        'stripe_payment_method_id' => $paymentMethod->id,
                        'brand' => $paymentMethod->card->brand,
                        'last_four' => $paymentMethod->card->last4,
                        'exp_month' => $paymentMethod->card->exp_month,
                        'exp_year' => $paymentMethod->card->exp_year,
                        'is_default' => false,
                    ]);
                }
            }

            // Remove payment methods that no longer exist in Stripe
            $stripePaymentMethodIds = collect($stripePaymentMethods)->pluck('id')->toArray();
            StripeCard::where('user_id', $user->id)
                ->whereNotIn('stripe_payment_method_id', $stripePaymentMethodIds)
                ->delete();

            Log::info('Payment methods synced for customer', [
                'user_id' => $user->id,
                'stripe_count' => count($stripePaymentMethods),
                'local_count' => StripeCard::where('user_id', $user->id)->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync payment methods', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
