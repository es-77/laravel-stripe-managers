<?php

namespace EmmanuelSaleem\LaravelStripeManager\Services;

use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ApiService
{
    protected function getUserModel()
    {
        return app(config('stripe-manager.stripe.model'));
    }

    public function listPlans(): array
    {
        $products = StripeProduct::with(['pricing' => function($q){
            $q->where('active', true);
        }])->where('active', true)->orderByDesc('display_order')->orderBy('name')->get();

        return $products->map(function($p){
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'stripe_product_id' => $p->stripe_id,
                'pricing' => $p->pricing->map(function($pr){
                    return [
                        'id' => $pr->id,
                        'stripe_price_id' => $pr->stripe_price_id,
                        'nickname' => $pr->nickname,
                        'unit_amount' => $pr->unit_amount,
                        'currency' => $pr->currency,
                        'type' => $pr->type,
                        'billing_period' => $pr->billing_period,
                        'billing_period_count' => $pr->billing_period_count,
                        'trial_period_days' => $pr->trial_period_days,
                    ];
                })
            ];
        })->toArray();
    }

    public function getUserSubscriptionSummary(int $userId): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::findOrFail($userId);

        $subscription = StripeSubscription::with('product','pricing')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();

        if (!$subscription) {
            return [];
        }

        $nextBilling = $subscription->ends_at ? null : $subscription->current_period_end;
        $amount = $subscription->pricing ? $subscription->pricing->unit_amount : null;

        return [
            'subscription_id' => $subscription->id,
            'stripe_subscription_id' => $subscription->stripe_subscription_id,
            'status' => $subscription->stripe_status,
            'product' => $subscription->product ? $subscription->product->name : null,
            'price' => $subscription->pricing ? [
                'nickname' => $subscription->pricing->nickname,
                'unit_amount' => $subscription->pricing->unit_amount,
                'currency' => $subscription->pricing->currency,
                'billing_period' => $subscription->pricing->billing_period,
                'billing_period_count' => $subscription->pricing->billing_period_count,
            ] : null,
            'current_period_start' => optional($subscription->current_period_start)->toDateTimeString(),
            'current_period_end' => optional($subscription->current_period_end)->toDateTimeString(),
            'next_billing_at' => optional($nextBilling)->toDateTimeString(),
            'next_billing_amount' => $amount,
        ];
    }

    public function getTrialInfo(int $userId): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::findOrFail($userId);
        $latest = StripeSubscription::where('user_id',$user->id)
            ->whereNotNull('trial_end')
            ->orderByDesc('trial_end')
            ->first();
        if (!$latest) return ['has_trial' => false];
        return [
            'has_trial' => true,
            'trial_start' => optional($latest->trial_start)->toDateTimeString(),
            'trial_end' => optional($latest->trial_end)->toDateTimeString(),
            'active' => $latest->trial_end ? $latest->trial_end->isFuture() : false,
        ];
    }

    public function cancelSubscriptionPlan(int $userId, bool $immediately = false): bool
    {
        $userModel = $this->getUserModel();
        $user = $userModel::findOrFail($userId);
        $subscription = StripeSubscription::where('user_id', $user->id)
            ->orderByDesc('created_at')->first();
        if (!$subscription) return false;

        // Use SubscriptionService to cancel on Stripe + local
        $service = app(SubscriptionService::class);
        $service->cancelSubscription($subscription, $immediately);
        return true;
    }

    public function listUserPaymentMethods(int $userId): array
    {
        $userModel = $this->getUserModel();
        $user = $userModel::findOrFail($userId);
        // Reuse CustomerService to fetch payment methods from Stripe
        $customerService = app(CustomerService::class);
        $methods = $customerService->getPaymentMethods($user);
        return array_map(function($pm){
            return [
                'id' => $pm->id,
                'brand' => $pm->card->brand,
                'last4' => $pm->card->last4,
                'exp_month' => $pm->card->exp_month,
                'exp_year' => $pm->card->exp_year,
            ];
        }, $methods);
    }

    public function saveStripeId(int $userId, string $stripeCustomerId): bool
    {
        $userModel = $this->getUserModel();
        $user = $userModel::findOrFail($userId);
        $user->stripe_id = $stripeCustomerId;
        $user->save();
        return true;
    }

    public function setDefaultPaymentMethod(int $userId, string $paymentMethodId): bool
    {
        $userModel = $this->getUserModel();
        $user = $userModel::findOrFail($userId);
        $customerService = app(CustomerService::class);
        return $customerService->setDefaultPaymentMethod($user, $paymentMethodId);
    }
}


