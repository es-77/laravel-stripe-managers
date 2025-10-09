<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use EmmanuelSaleem\LaravelStripeManager\Services\ApiService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ApiController extends Controller
{
    protected ApiService $api;

    public function __construct(ApiService $api)
    {
        $this->api = $api;
    }

    protected function successResponse(mixed $data = null, $message = 'success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'success' => 'success',
            'code' => $code,
            'message' => $message,
            'errors' => [],
            'data' => $data,
        ], $code)->header('Content-Type', 'application/json');
    }

    protected function errorResponse(string $message, int $code = 400, array $errors = [])
    {
        return response()->json([
            'status' => false,
            'success' => 'error',
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], $code)->header('Content-Type', 'application/json');
    }

    // GET /api/stripe-manager/plans
    public function plans()
    {
        return $this->successResponse($this->api->listPlans(), 'Plans retrieved successfully');
    }

    // GET /api/stripe-manager/users/{user}/subscription
    public function userSubscription(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        $data = $this->api->getUserSubscriptionSummary((int) $user->id);
        return $this->successResponse(!empty($data) ? $data : null, 'User subscription retrieved successfully');
    }

    // POST /select-subscription-plan
    public function selectSubscriptionPlan(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        try {
            $validated = $request->validate([
                'plan_id' => 'required|integer|exists:em_stripe_product_pricing,id',
                'payment_method_id' => 'required|string',
            ]);
            $service = app(\EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService::class);
            $pricing = \EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing::findOrFail($validated['plan_id']);
            $subscription = $service->createSubscription($user, $pricing, [
                'payment_method' => $validated['payment_method_id']
            ]);
            return $this->successResponse(['id' => $subscription->id], 'Subscription plan selected successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to select subscription plan: ' . $e->getMessage(), 500);
        }
    }

    // GET /trial-info
    public function trialInfo(Request $request)
    {
        try {
            $request->validate(['user_id' => 'required|integer']);
            $data = app(ApiService::class)->getTrialInfo((int) $request->user_id);
            return $this->successResponse($data, 'Trial info retrieved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve trial info: ' . $e->getMessage(), 500);
        }
    }

    // DELETE /cancel-subscription-plan
    public function cancelSubscriptionPlan(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        try {
            $request->validate(['immediately' => 'sometimes|boolean']);
            $ok = app(ApiService::class)->cancelSubscriptionPlan((int) $user->id, $request->boolean('immediately', false));
            return $this->successResponse(['cancelled' => $ok], 'Subscription cancelled successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel subscription: ' . $e->getMessage(), 500);
        }
    }

    // GET /user-payment-methods
    public function userPaymentMethods(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        try {
            $list = app(ApiService::class)->listUserPaymentMethods((int) $user->id);
            return $this->successResponse($list, 'Payment methods retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve payment methods: ' . $e->getMessage(), 500);
        }
    }

    // POST /save-stripe-id
    public function saveStripeId(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        try {
            $request->validate(['stripe_id' => 'required|string']);
            $ok = app(ApiService::class)->saveStripeId((int) $user->id, $request->stripe_id);
            return $this->successResponse(['saved' => $ok], 'Stripe ID saved successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to save Stripe ID: ' . $e->getMessage(), 500);
        }
    }

    // POST /set-default-payment-method
    public function setDefaultPaymentMethod(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        try {
            $request->validate(['payment_method_id' => 'required|string']);
            $ok = app(ApiService::class)->setDefaultPaymentMethod((int) $user->id, $request->payment_method_id);
            return $this->successResponse(['set' => $ok], 'Default payment method set successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to set default payment method: ' . $e->getMessage(), 500);
        }
    }
}


