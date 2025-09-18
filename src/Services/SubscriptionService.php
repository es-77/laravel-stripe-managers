<?php

namespace EmmanuelSaleem\LaravelStripeManager\Services;

// use App\Models\User; // Will use configurable model
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\SubscriptionItem;
use Carbon\Carbon;

class SubscriptionService
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
     * Create a subscription for a customer
     */
    public function createSubscription($user, StripeProductPricing $pricing, array $options = []): StripeSubscription
    {
        try {
            if (!$user->hasStripeId()) {
                throw new \Exception('User does not have a Stripe customer ID');
            }

            $subscriptionData = [
                'customer' => $user->stripe_id,
                'items' => [
                    ['price' => $pricing->stripe_price_id]
                ],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            // Add trial period if specified
            if (isset($options['trial_days']) && $options['trial_days'] > 0) {
                $subscriptionData['trial_period_days'] = $options['trial_days'];
            } elseif ($pricing->trial_period_days) {
                $subscriptionData['trial_period_days'] = $pricing->trial_period_days;
            }

            // Add payment method if specified
            if (isset($options['payment_method'])) {
                $subscriptionData['default_payment_method'] = $options['payment_method'];
            }

            // Add metadata
            if (isset($options['metadata'])) {
                $subscriptionData['metadata'] = $options['metadata'];
            }

            // Create subscription in Stripe
            $stripeSubscription = Subscription::create($subscriptionData);

            // Store in local database
            $subscription = StripeSubscription::create([
                'user_id' => $user->id,
                'product_id' => $pricing->product_id,
                'pricing_id' => $pricing->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_status' => $stripeSubscription->status,
                'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'trial_start' => $stripeSubscription->trial_start ? Carbon::createFromTimestamp($stripeSubscription->trial_start) : null,
                'trial_end' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                'canceled_at' => $stripeSubscription->canceled_at ? Carbon::createFromTimestamp($stripeSubscription->canceled_at) : null,
                'ends_at' => $stripeSubscription->cancel_at_period_end ? Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null,
                'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                'metadata' => $stripeSubscription->metadata->toArray(),
            ]);

            Log::info('Subscription created', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'pricing_id' => $pricing->id
            ]);

            return $subscription;
        } catch (\Exception $e) {
            Log::error('Failed to create subscription', [
                'user_id' => $user->id,
                'pricing_id' => $pricing->id,
                'options' => $options,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update a subscription
     */
    public function updateSubscription(StripeSubscription $subscription, array $data): StripeSubscription
    {
        try {
            $updateData = [];

            // Update quantity
            if (isset($data['quantity'])) {
                $updateData['items'] = [
                    [
                        'id' => $this->getSubscriptionItemId($subscription->stripe_subscription_id),
                        'quantity' => $data['quantity']
                    ]
                ];
            }

            // Update metadata
            if (isset($data['metadata'])) {
                $updateData['metadata'] = $data['metadata'];
            }

            // Update payment method
            if (isset($data['payment_method'])) {
                $updateData['default_payment_method'] = $data['payment_method'];
            }

            // Update proration behavior
            if (isset($data['proration_behavior'])) {
                $updateData['proration_behavior'] = $data['proration_behavior'];
            }

            if (!empty($updateData)) {
                // Update subscription in Stripe
                $stripeSubscription = Subscription::update($subscription->stripe_subscription_id, $updateData);

                // Update local database
                $subscription->update([
                    'stripe_status' => $stripeSubscription->status,
                    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                    'quantity' => $data['quantity'] ?? $subscription->quantity,
                    'metadata' => array_merge($subscription->metadata ?? [], $data['metadata'] ?? []),
                ]);
            }

            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'data' => $data
            ]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update subscription', [
                'subscription_id' => $subscription->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(StripeSubscription $subscription, bool $immediately = false): StripeSubscription
    {
        try {
            if ($immediately) {
                // Cancel immediately
                $stripeSubscription = Subscription::retrieve($subscription->stripe_subscription_id);
                $stripeSubscription->cancel();

                $subscription->update([
                    'stripe_status' => 'canceled',
                    'canceled_at' => now(),
                    'ends_at' => now(),
                ]);
            } else {
                // Cancel at period end
                $stripeSubscription = Subscription::update($subscription->stripe_subscription_id, [
                    'cancel_at_period_end' => true
                ]);

                $subscription->update([
                    'stripe_status' => $stripeSubscription->status,
                    'ends_at' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                ]);
            }

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'immediately' => $immediately
            ]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'immediately' => $immediately,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Resume a cancelled subscription
     */
    public function resumeSubscription(StripeSubscription $subscription): StripeSubscription
    {
        try {
            // Resume subscription in Stripe
            $stripeSubscription = Subscription::update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => false
            ]);

            // Update local database
            $subscription->update([
                'stripe_status' => $stripeSubscription->status,
                'ends_at' => null,
            ]);

            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id
            ]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to resume subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Change subscription plan
     */
    public function changePlan(StripeSubscription $subscription, StripeProductPricing $newPricing, array $options = []): StripeSubscription
    {
        try {
            $subscriptionItemId = $this->getSubscriptionItemId($subscription->stripe_subscription_id);

            $updateData = [
                'items' => [
                    [
                        'id' => $subscriptionItemId,
                        'price' => $newPricing->stripe_price_id,
                        'quantity' => $options['quantity'] ?? $subscription->quantity,
                    ]
                ],
                'proration_behavior' => $options['proration_behavior'] ?? 'create_prorations',
            ];

            // Update subscription in Stripe
            $stripeSubscription = Subscription::update($subscription->stripe_subscription_id, $updateData);

            // Update local database
            $subscription->update([
                'pricing_id' => $newPricing->id,
                'product_id' => $newPricing->product_id,
                'stripe_status' => $stripeSubscription->status,
                'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'quantity' => $options['quantity'] ?? $subscription->quantity,
            ]);

            Log::info('Subscription plan changed', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'old_pricing_id' => $subscription->pricing_id,
                'new_pricing_id' => $newPricing->id
            ]);

            return $subscription->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to change subscription plan', [
                'subscription_id' => $subscription->id,
                'new_pricing_id' => $newPricing->id,
                'options' => $options,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sync subscription from Stripe
     */
    public function syncSubscription(string $stripeSubscriptionId): ?StripeSubscription
    {
        try {
            $stripeSubscription = Subscription::retrieve($stripeSubscriptionId);

            // Find or create local subscription
            $subscription = StripeSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

            if ($subscription) {
                // Update existing subscription
                $subscription->update([
                    'stripe_status' => $stripeSubscription->status,
                    'current_period_start' => Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                    'current_period_end' => Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                    'trial_start' => $stripeSubscription->trial_start ? Carbon::createFromTimestamp($stripeSubscription->trial_start) : null,
                    'trial_end' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                    'canceled_at' => $stripeSubscription->canceled_at ? Carbon::createFromTimestamp($stripeSubscription->canceled_at) : null,
                    'ends_at' => $stripeSubscription->cancel_at_period_end ? Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null,
                    'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
                    'metadata' => $stripeSubscription->metadata->toArray(),
                ]);

                Log::info('Subscription synced', [
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_id' => $stripeSubscriptionId
                ]);

                return $subscription->fresh();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to sync subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get subscription item ID for updates
     */
    private function getSubscriptionItemId(string $stripeSubscriptionId): string
    {
        $stripeSubscription = Subscription::retrieve($stripeSubscriptionId);
        return $stripeSubscription->items->data[0]->id;
    }

    /**
     * Check if subscription is active
     */
    public function isActive(StripeSubscription $subscription): bool
    {
        return in_array($subscription->stripe_status, ['active', 'trialing']);
    }

    /**
     * Check if subscription is on trial
     */
    public function onTrial(StripeSubscription $subscription): bool
    {
        return $subscription->stripe_status === 'trialing' &&
               $subscription->trial_end &&
               $subscription->trial_end->isFuture();
    }

    /**
     * Check if subscription is cancelled
     */
    public function cancelled(StripeSubscription $subscription): bool
    {
        return $subscription->stripe_status === 'canceled' ||
               ($subscription->ends_at && $subscription->ends_at->isPast());
    }

    /**
     * Get subscription usage for current period
     */
    public function getUsage(StripeSubscription $subscription): array
    {
        try {
            $subscriptionItemId = $this->getSubscriptionItemId($subscription->stripe_subscription_id);

            // This would be implemented based on your usage tracking needs
            // For now, returning basic subscription info
            return [
                'quantity' => $subscription->quantity,
                'period_start' => $subscription->current_period_start,
                'period_end' => $subscription->current_period_end,
                'status' => $subscription->stripe_status,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get subscription usage', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
