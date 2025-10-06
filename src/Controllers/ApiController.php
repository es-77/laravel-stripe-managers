<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use EmmanuelSaleem\LaravelStripeManager\Services\ApiService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiController extends Controller
{
    protected ApiService $api;

    public function __construct(ApiService $api)
    {
        $this->api = $api;
    }

    // GET /api/stripe-manager/plans
    public function plans()
    {
        return response()->json(['data' => $this->api->listPlans()]);
    }

    // GET /api/stripe-manager/users/{user}/subscription
    public function userSubscription(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = $this->api->getUserSubscriptionSummary((int) $user->id);
        return response()->json(['data' => !empty($data) ? $data : null]);
    }

    // POST /select-subscription-plan
    public function selectSubscriptionPlan(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:em_stripe_product_pricing,id',
            'payment_method_id' => 'required|string',
        ]);
        $service = app(\EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService::class);
        $pricing = \EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing::findOrFail($validated['plan_id']);
        $subscription = $service->createSubscription($user, $pricing, [
            'payment_method' => $validated['payment_method_id']
        ]);
        return response()->json(['data' => ['id' => $subscription->id]], 201);
    }

    // GET /trial-info
    public function trialInfo(Request $request)
    {
        $request->validate(['user_id' => 'required|integer']);
        $data = app(ApiService::class)->getTrialInfo((int) $request->user_id);
        return response()->json(['data' => $data]);
    }

    // DELETE /cancel-subscription-plan
    public function cancelSubscriptionPlan(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $request->validate(['immediately' => 'sometimes|boolean']);
        $ok = app(ApiService::class)->cancelSubscriptionPlan((int) $user->id, $request->boolean('immediately', false));
        return response()->json(['ok' => $ok]);
    }

    // GET /user-payment-methods
    public function userPaymentMethods(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $list = app(ApiService::class)->listUserPaymentMethods((int) $user->id);
        return response()->json(['data' => $list]);
    }

    // POST /save-stripe-id
    public function saveStripeId(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $request->validate(['stripe_id' => 'required|string']);
        $ok = app(ApiService::class)->saveStripeId((int) $user->id, $request->stripe_id);
        return response()->json(['ok' => $ok]);
    }

    // POST /set-default-payment-method
    public function setDefaultPaymentMethod(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $request->validate(['payment_method_id' => 'required|string']);
        $ok = app(ApiService::class)->setDefaultPaymentMethod((int) $user->id, $request->payment_method_id);
        return response()->json(['ok' => $ok]);
    }
}


