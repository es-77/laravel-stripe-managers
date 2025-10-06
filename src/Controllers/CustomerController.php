<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
// use App\Models\User; // Will use configurable model
use EmmanuelSaleem\LaravelStripeManager\Services\CustomerService;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeCard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    protected $customerService;

    /**
     * Get the configurable user model
     */
    protected function getUserModel()
    {
        return app(config('stripe-manager.stripe.model'));
    }

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index()
    {
        $customers = $this->getUserModel()::whereNotNull('stripe_id')->with('subscriptions')->paginate(15);
        return view('stripe-manager::customers.index', compact('customers'));
    }

    public function show($customer)
    {
        $customer = $this->getUserModel()::findOrFail($customer);
        $customer->load('subscriptions.product', 'subscriptions.pricing');

        // Get stored cards and sync with Stripe
        $cards = StripeCard::where('user_id', $customer->id)->get();

        if ($customer->hasStripeId()) {
            try {
                $this->customerService->syncPaymentMethods($customer);
                $cards = StripeCard::where('user_id', $customer->id)->get();
            } catch (\Exception $e) {
                // Handle error silently
            }
        }

        return view('stripe-manager::customers.show', compact('customer', 'cards'));
    }

    public function create()
    {
        $query = request('q');
        $perPage = (int) (request('per_page') ?: 25);

        $users = $this->getUserModel()::query()
            ->whereNull('stripe_id')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                    if (is_numeric($query)) {
                        $q->orWhere('id', (int) $query);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('stripe-manager::customers.create', compact('users', 'query', 'perPage'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        try {
            $user = $this->getUserModel()::findOrFail($request->user_id);

            // Create Stripe customer using our service
            $this->customerService->createCustomer($user, [
                'name' => $request->name,
                'email' => $request->email,
            ]);

            return redirect()->route('stripe-manager.customers.index')
                ->with('success', 'Customer created successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error creating customer: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function setupPaymentMethod($customer)
    {
        $customer = $this->getUserModel()::findOrFail($customer);
        if (!$customer->hasStripeId()) {
            return back()->with('error', 'Customer must have Stripe ID first.');
        }

        try {
            $intent = $this->customerService->createSetupIntent($customer);
            return view('stripe-manager::customers.setup-payment', compact('customer', 'intent'));
        } catch (\Exception $e) {
            return back()->with('error', 'Error creating setup intent: ' . $e->getMessage());
        }
    }

    public function storePaymentMethod(Request $request, $customer)
    {
        $customer = $this->getUserModel()::findOrFail($customer);
        $request->validate([
            'payment_method' => 'required|string',
            'set_as_default' => 'boolean'
        ]);

        try {
            $this->customerService->storePaymentMethod(
                $customer,
                $request->payment_method,
                $request->boolean('set_as_default', false)
            );

            return redirect()->route('stripe-manager.customers.show', $customer)
                ->with('success', 'Payment method saved successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error saving payment method: ' . $e->getMessage());
        }
    }

    public function removePaymentMethod(Request $request, $customer)
    {
        $customer = $this->getUserModel()::findOrFail($customer);
        $request->validate([
            'payment_method' => 'required|string'
        ]);

        try {
            $this->customerService->removePaymentMethod($customer, $request->payment_method);

            return redirect()->route('stripe-manager.customers.show', $customer)
                ->with('success', 'Payment method removed successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error removing payment method: ' . $e->getMessage());
        }
    }

    public function setDefaultPaymentMethod(Request $request, $customer)
    {
        $customer = $this->getUserModel()::findOrFail($customer);
        $request->validate([
            'payment_method' => 'required|string'
        ]);

        try {
            $this->customerService->setDefaultPaymentMethod($customer, $request->payment_method);

            return redirect()->route('stripe-manager.customers.show', $customer)
                ->with('success', 'Default payment method updated successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Error updating default payment method: ' . $e->getMessage());
        }
    }

    /**
     * Stripe testing panel
     * Accepts either local user id or stripe customer id (one required)
     */
    public function stripeTest(Request $request)
    {
        $userId = $request->query('user_id');
        $stripeCustomerId = $request->query('stripe_id');

        if (!$userId && !$stripeCustomerId) {
            return view('stripe-manager::customers.test', [
                'error' => null,
                'data' => null,
            ]);
        }

        try {
            $user = null;
            if ($userId) {
                $user = $this->getUserModel()::find($userId);
                if (!$user) {
                    return view('stripe-manager::customers.test', [
                        'error' => 'Local user not found for id ' . $userId,
                        'data' => null,
                    ]);
                }
                if (!$user->stripe_id) {
                    return view('stripe-manager::customers.test', [
                        'error' => 'User does not have a Stripe customer id.',
                        'data' => null,
                    ]);
                }
                $stripeCustomerId = $user->stripe_id;
            }

            $secret = config('stripe.secret') ?: config('cashier.secret');
            if (!$secret) {
                return view('stripe-manager::customers.test', [
                    'error' => 'Stripe secret key not configured.',
                    'data' => null,
                ]);
            }

            $client = new \Stripe\StripeClient($secret);

            $customer = $client->customers->retrieve($stripeCustomerId);
            $subscriptions = $client->subscriptions->all([
                'customer' => $stripeCustomerId,
                'limit' => (int) config('stripe-manager.stripe.limits.subscriptions', 10),
                'expand' => ['data.items', 'data.latest_invoice', 'data.latest_invoice.payment_intent']
            ]);
            $invoices = $client->invoices->all([
                'customer' => $stripeCustomerId,
                'limit' => (int) config('stripe-manager.stripe.limits.invoices', 10)
            ]);
            $upcoming = null;
            try { $upcoming = $client->invoices->upcoming(['customer' => $stripeCustomerId]); } catch (\Exception $e) { $upcoming = null; }
            $paymentMethods = $client->paymentMethods->all(['customer' => $stripeCustomerId, 'type' => 'card']);
            $charges = $client->charges->all([
                'customer' => $stripeCustomerId,
                'limit' => (int) config('stripe-manager.stripe.limits.charges', 8)
            ]);

            $data = compact('customer', 'subscriptions', 'invoices', 'upcoming', 'paymentMethods', 'charges', 'user');
            return view('stripe-manager::customers.test', [ 'error' => null, 'data' => $data ]);

        } catch (\Exception $e) {
            return view('stripe-manager::customers.test', [
                'error' => $e->getMessage(),
                'data' => null,
            ]);
        }
    }

    /**
     * Initialize customer creation + setup intent (AJAX)
     */
    public function initSetup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);

        $user = $this->getUserModel()::findOrFail($validated['user_id']);

        if (!$user->hasStripeId()) {
            $this->customerService->createCustomer($user, [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);
            $user->refresh();
        }

        $intent = $this->customerService->createSetupIntent($user);

        return response()->json([
            'client_secret' => $intent->client_secret,
            'customer_id' => $user->id,
            'stripe_customer_id' => $user->stripe_id,
        ]);
    }

    /**
     * Finalize setup by attaching the payment method (AJAX)
     */
    public function finalizeSetup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:users,id',
            'payment_method' => 'required|string',
            'set_as_default' => 'sometimes|boolean',
        ]);

        $user = $this->getUserModel()::findOrFail($validated['customer_id']);

        $card = $this->customerService->storePaymentMethod(
            $user,
            $validated['payment_method'],
            $request->boolean('set_as_default', true)
        );

        return response()->json([
            'ok' => true,
            'card_id' => $card->id,
        ]);
    }
}
