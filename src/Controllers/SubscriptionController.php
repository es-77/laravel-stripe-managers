<?php


// packages/emmanuelsaleem/laravel-stripe-manager/src/Controllers/SubscriptionController.php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
// use App\Models\User; // Will use configurable model
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Get the configurable user model
     */
    protected function getUserModel()
    {
        return app(config('stripe-manager.stripe.model'));
    }

    public function index()
    {
        $subscriptions = StripeSubscription::with('user', 'product', 'pricing')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return view('stripe-manager::subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $customers = $this->getUserModel()::whereNotNull('stripe_id')->get();
        $products = StripeProduct::where('active', true)->with('pricing')->get();
        
        return view('stripe-manager::subscriptions.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $userModel = $this->getUserModel();
        $userTable = (new $userModel)->getTable();
        
        $request->validate([
            'customer_id' => "required|exists:{$userTable},id",
            'pricing_id' => 'required|exists:em_stripe_product_pricing,id',
            'trial_days' => 'nullable|integer|min:0|max:365',
        ]);

        try {
            $customer = $userModel::findOrFail($request->customer_id);
            $pricing = StripeProductPricing::with('product')->findOrFail($request->pricing_id);

            // Check if customer has Stripe ID
            if (!$customer->stripe_id) {
                return back()->with('error', 'Customer must have a Stripe ID first. Please set up payment method first.')
                    ->withInput();
            }

            // Check if pricing is recurring
            if ($pricing->type !== 'recurring') {
                return back()->with('error', 'Selected pricing must be recurring for subscriptions.')
                    ->withInput();
            }

            // Check if pricing is active
            if (!$pricing->active) {
                return back()->with('error', 'Selected pricing plan is not active.')
                    ->withInput();
            }

            // Create subscription using Stripe directly
            $stripeSecret = config('stripe.secret') ?: config('cashier.secret');
            
            if (!$stripeSecret) {
                return back()->with('error', 'Stripe secret key not configured. Please check your .env file.')
                    ->withInput();
            }
            
            $stripe = new \Stripe\StripeClient($stripeSecret);
            
            $subscriptionData = [
                'customer' => $customer->stripe_id,
                'items' => [
                    [
                        'price' => $pricing->stripe_price_id,
                    ],
                ],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
            ];

            if ($request->trial_days && $request->trial_days > 0) {
                $subscriptionData['trial_period_days'] = $request->trial_days;
            }

            $stripeSubscription = $stripe->subscriptions->create($subscriptionData);

            // Save locally
            $localSubscription = StripeSubscription::create([
                'user_id' => $customer->id,
                'product_id' => $pricing->product_id,
                'pricing_id' => $pricing->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'stripe_status' => $stripeSubscription->status,
                'current_period_start' => $stripeSubscription->current_period_start ? Carbon::createFromTimestamp($stripeSubscription->current_period_start) : null,
                'current_period_end' => $stripeSubscription->current_period_end ? Carbon::createFromTimestamp($stripeSubscription->current_period_end) : null,
                'trial_start' => $stripeSubscription->trial_start ? Carbon::createFromTimestamp($stripeSubscription->trial_start) : null,
                'trial_end' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromTimestamp($stripeSubscription->trial_end) : null,
                'ends_at' => null,
                'quantity' => 1,
            ]);

            \Log::info('Subscription created successfully', [
                'local_id' => $localSubscription->id,
                'stripe_id' => $stripeSubscription->id,
                'customer_id' => $customer->id,
                'pricing_id' => $pricing->id
            ]);

            return redirect()->route('stripe-manager.subscriptions.index')
                ->with('success', 'Subscription created successfully!');

        } catch (\Exception $e) {
            \Log::error('Subscription creation error: ' . $e->getMessage());
            return back()->with('error', 'Error creating subscription: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(StripeSubscription $subscription)
    {
        $subscription->load('user', 'product', 'pricing', 'payments');
        return view('stripe-manager::subscriptions.show', compact('subscription'));
    }

    public function cancel(StripeSubscription $subscription)
    {
        try {
            $customer = $subscription->user;
            $stripeSubscription = $customer->subscription('default');
            
            if ($stripeSubscription && $stripeSubscription->stripe_id === $subscription->stripe_subscription_id) {
                $stripeSubscription->cancel();
                
                $subscription->update([
                    'status' => 'canceled',
                    'ends_at' => now(),
                ]);
            }

            return back()->with('success', 'Subscription canceled successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error canceling subscription: ' . $e->getMessage());
        }
    }

    public function resume(StripeSubscription $subscription)
    {
        try {
            $customer = $subscription->user;
            $stripeSubscription = $customer->subscription('default');
            
            if ($stripeSubscription && $stripeSubscription->stripe_id === $subscription->stripe_subscription_id) {
                $stripeSubscription->resume();
                
                $subscription->update([
                    'status' => 'active',
                    'ends_at' => null,
                ]);
            }

            return back()->with('success', 'Subscription resumed successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error resuming subscription: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing trial period
     */
    public function editTrial(StripeSubscription $subscription)
    {
        $subscription->load('user', 'product', 'pricing');
        return view('stripe-manager::subscriptions.trial.edit', compact('subscription'));
    }

    /**
     * Update the trial period
     */
    public function updateTrial(Request $request, StripeSubscription $subscription)
    {
        $request->validate([
            'trial_days' => 'required|integer|min:0|max:365',
            'action' => 'required|in:extend,reduce,remove'
        ]);

        try {
            $stripeSecret = config('stripe.secret') ?: config('cashier.secret');
            
            if (!$stripeSecret) {
                return back()->with('error', 'Stripe secret key not configured.')
                    ->withInput();
            }

            $stripe = new \Stripe\StripeClient($stripeSecret);
            
            // Get current subscription from Stripe
            $stripeSubscription = $stripe->subscriptions->retrieve($subscription->stripe_subscription_id);
            
            $trialEnd = null;
            $trialStart = null;
            
            switch ($request->action) {
                case 'extend':
                    // Extend trial period
                    if ($stripeSubscription->status === 'trialing') {
                        // If currently on trial, extend from current trial end
                        $currentTrialEnd = $stripeSubscription->trial_end;
                        $trialEnd = $currentTrialEnd + ($request->trial_days * 24 * 60 * 60);
                    } else {
                        // If not on trial, start new trial
                        $trialStart = time();
                        $trialEnd = $trialStart + ($request->trial_days * 24 * 60 * 60);
                    }
                    break;
                    
                case 'reduce':
                    // Reduce trial period
                    if ($stripeSubscription->status === 'trialing') {
                        $currentTrialEnd = $stripeSubscription->trial_end;
                        $trialEnd = $currentTrialEnd - ($request->trial_days * 24 * 60 * 60);
                        
                        // Don't allow reducing trial to past
                        if ($trialEnd <= time()) {
                            return back()->with('error', 'Cannot reduce trial period to a past date.')
                                ->withInput();
                        }
                    } else {
                        return back()->with('error', 'Cannot reduce trial period for non-trialing subscription.')
                            ->withInput();
                    }
                    break;
                    
                case 'remove':
                    // Remove trial period
                    $trialEnd = time(); // End trial immediately
                    break;
            }

            // Update subscription in Stripe
            $updateData = [];
            if ($trialStart !== null) {
                $updateData['trial_start'] = $trialStart;
            }
            if ($trialEnd !== null) {
                $updateData['trial_end'] = $trialEnd;
            }

            if (!empty($updateData)) {
                $stripe->subscriptions->update($subscription->stripe_subscription_id, $updateData);
            }

            // Update local subscription
            $subscription->update([
                'trial_start' => $trialStart ? Carbon::createFromTimestamp($trialStart) : $subscription->trial_start,
                'trial_end' => $trialEnd ? Carbon::createFromTimestamp($trialEnd) : null,
                'trial_ends_at' => $trialEnd ? Carbon::createFromTimestamp($trialEnd) : null,
            ]);

            $actionMessages = [
                'extend' => 'Trial period extended successfully!',
                'reduce' => 'Trial period reduced successfully!',
                'remove' => 'Trial period removed successfully!'
            ];

            \Log::info('Trial period updated', [
                'subscription_id' => $subscription->id,
                'action' => $request->action,
                'trial_days' => $request->trial_days,
                'new_trial_end' => $trialEnd ? Carbon::createFromTimestamp($trialEnd)->toDateTimeString() : null
            ]);

            return redirect()->route('stripe-manager.subscriptions.show', $subscription)
                ->with('success', $actionMessages[$request->action]);

        } catch (\Exception $e) {
            \Log::error('Trial period update error: ' . $e->getMessage());
            return back()->with('error', 'Error updating trial period: ' . $e->getMessage())
                ->withInput();
        }
    }
}