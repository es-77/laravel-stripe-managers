<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PackageWebController extends Controller
{
    // GET /select-package - Show package selection page
    public function selectPackage()
    {
        $products = \EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct::with(['pricing' => function($q) {
            $q->where('active', true);
        }])->where('active', true)
          ->orderByDesc('display_order')
          ->orderBy('name')
          ->get();

        return view('stripe-manager::select-package', compact('products'));
    }

    // POST /packages/subscribe - Handle package subscription
    public function subscribe(Request $request)
    {
        try {
            $userModel = app(config('stripe-manager.stripe.model'));
            $user = $userModel::findOrFail(auth()->id());

            $validated = $request->validate([
                'pricing_id' => 'required|integer|exists:em_stripe_product_pricing,id'
            ]);

            $pricing = \EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing::with('product')->findOrFail($validated['pricing_id']);

            // Ensure Stripe customer exists
            if (empty($user->stripe_id)) {
                $customerService = app(\EmmanuelSaleem\LaravelStripeManager\Services\CustomerService::class);
                $user = $customerService->createCustomer($user);
            }

            // Prevent duplicate active subscription
            $existingSubscription = \EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription::where('user_id', $user->id)
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->first();

            if ($existingSubscription) {
                return redirect()->route('stripe-manager.packages.select')
                    ->with('error', 'You already have an active subscription. Please cancel it first to subscribe to a new package.');
            }

            $paymentMethodId = $request->input('payment_method_id');

            $subscriptionService = app(\EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService::class);

            $subscriptionService->createSubscription($user, $pricing, [
                'payment_method' => $paymentMethodId ?? 'pm_card_visa',
                'metadata' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name ?? 'User',
                    'user_email' => $user->email ?? '',
                ]
            ]);

            return redirect()->route('stripe-manager.packages.success')
                ->with('success', 'You have successfully subscribed to ' . $pricing->product->name);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
        } catch (\Exception $e) {
            \Log::error('Subscription error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'pricing_id' => $request->input('pricing_id'),
            ]);
            return redirect()->back()
                ->with('error', 'Failed to subscribe: ' . $e->getMessage());
        }
    }
}


