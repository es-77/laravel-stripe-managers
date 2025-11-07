<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use EmmanuelSaleem\LaravelStripeManager\Models\Coupon;
use EmmanuelSaleem\LaravelStripeManager\Services\CouponService;
use Stripe\Stripe;
use Stripe\Coupon as StripeCoupon;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
        Stripe::setApiKey(config('cashier.secret'));
    }

    public function index()
    {
        $coupons = Coupon::orderBy('created_at', 'desc')->paginate(10);
        return view('stripe-manager::coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('stripe-manager::coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code',
            'description' => 'nullable|string',
            'discount_type' => 'required|string|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'duration' => 'required|string|in:once,repeating,forever',
            'duration_in_months' => 'nullable|integer|min:1|max:36|required_if:duration,repeating',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|string|in:active,inactive',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Build Stripe coupon payload
            $stripeCouponData = [
                'duration' => $validated['duration'],
            ];

            if ($validated['discount_type'] === 'percentage') {
                $stripeCouponData['percent_off'] = (int) $validated['discount_value'];
            } else {
                $stripeCouponData['amount_off'] = (int) ($validated['discount_value'] * 100); // Convert to cents
                $stripeCouponData['currency'] = config('cashier.currency', 'usd');
            }

            if ($validated['duration'] === 'repeating' && isset($validated['duration_in_months'])) {
                $stripeCouponData['duration_in_months'] = $validated['duration_in_months'];
            }

            if (isset($validated['usage_limit'])) {
                $stripeCouponData['max_redemptions'] = $validated['usage_limit'];
            }

            if (isset($validated['end_date'])) {
                $stripeCouponData['redeem_by'] = strtotime($validated['end_date']);
            }

            // Create coupon in Stripe
            $stripeCoupon = StripeCoupon::create($stripeCouponData);

            // Save coupon locally
            $coupon = Coupon::create([
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'duration' => $validated['duration'],
                'duration_in_months' => $validated['duration_in_months'] ?? null,
                'start_date' => isset($validated['start_date']) ? $validated['start_date'] : null,
                'end_date' => isset($validated['end_date']) ? $validated['end_date'] : null,
                'status' => $validated['status'],
                'usage_limit' => $validated['usage_limit'] ?? null,
                'minimum_amount' => $validated['minimum_amount'] ?? null,
                'maximum_discount' => $validated['maximum_discount'] ?? null,
                'stripe_coupon_id' => $stripeCoupon->id,
            ]);

            return redirect()->route('stripe-manager.coupons.index')
                ->with('success', 'Coupon created successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error creating coupon: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Coupon $coupon)
    {
        return view('stripe-manager::coupons.show', compact('coupon'));
    }

    public function edit(Coupon $coupon)
    {
        return view('stripe-manager::coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:coupons,code,' . $coupon->id,
            'description' => 'nullable|string',
            'discount_type' => 'required|string|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'duration' => 'required|string|in:once,repeating,forever',
            'duration_in_months' => 'nullable|integer|min:1|max:36|required_if:duration,repeating',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'required|string|in:active,inactive',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
        ]);

        try {
            // Update coupon in Stripe if stripe_coupon_id exists
            if ($coupon->stripe_coupon_id) {
                // Note: Stripe coupons are immutable, so we can only update metadata
                // For actual changes, you might need to create a new coupon
                StripeCoupon::update($coupon->stripe_coupon_id, [
                    'metadata' => [
                        'code' => $validated['code'],
                        'status' => $validated['status'],
                    ],
                ]);
            }

            // Update coupon locally
            $coupon->update([
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
                'discount_type' => $validated['discount_type'],
                'discount_value' => $validated['discount_value'],
                'duration' => $validated['duration'],
                'duration_in_months' => $validated['duration_in_months'] ?? null,
                'start_date' => isset($validated['start_date']) ? $validated['start_date'] : null,
                'end_date' => isset($validated['end_date']) ? $validated['end_date'] : null,
                'status' => $validated['status'],
                'usage_limit' => $validated['usage_limit'] ?? null,
                'minimum_amount' => $validated['minimum_amount'] ?? null,
                'maximum_discount' => $validated['maximum_discount'] ?? null,
            ]);

            return redirect()->route('stripe-manager.coupons.index')
                ->with('success', 'Coupon updated successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error updating coupon: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Coupon $coupon)
    {
        try {
            // Delete coupon from Stripe if stripe_coupon_id exists
            if ($coupon->stripe_coupon_id) {
                StripeCoupon::update($coupon->stripe_coupon_id, [
                    'metadata' => ['deleted' => 'true'],
                ]);
            }

            // Delete coupon locally
            $coupon->delete();

            return redirect()->route('stripe-manager.coupons.index')
                ->with('success', 'Coupon deleted successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Error deleting coupon: ' . $e->getMessage());
        }
    }
}


