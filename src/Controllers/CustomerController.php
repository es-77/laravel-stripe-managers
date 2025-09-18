<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
// use App\Models\User; // Will use configurable model
use EmmanuelSaleem\LaravelStripeManager\Services\CustomerService;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeCard;
use Illuminate\Http\Request;

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
        $users = $this->getUserModel()::whereNull('stripe_id')->get();
        return view('stripe-manager::customers.create', compact('users'));
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
}
