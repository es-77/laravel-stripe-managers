<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use EmmanuelSaleem\LaravelStripeManager\Services\CouponService;

class CouponController extends Controller
{
    public function create()
    {
        return view('stripe-manager::coupons.create');
    }

    public function store(Request $request, CouponService $service)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'percent_off' => 'nullable|integer|min:1|max:100',
            'amount_off' => 'nullable|integer|min:1',
            'currency' => 'nullable|string|size:3',
            'duration' => 'required|string|in:once,repeating,forever',
            'duration_in_months' => 'nullable|integer|min:1|max:36',
            'max_redemptions' => 'nullable|integer|min:1',
            'redeem_by' => 'nullable|date',
            'create_promo' => 'sometimes|boolean',
            'promo_code' => 'nullable|string|max:50',
            'promo_max_redemptions' => 'nullable|integer|min:1',
            'promo_expires_at' => 'nullable|date',
        ]);

        // Build coupon payload
        $couponData = [
            'name' => $validated['name'] ?? null,
            'duration' => $validated['duration'],
            'percent_off' => $validated['percent_off'] ?? null,
            'amount_off' => $validated['amount_off'] ?? null,
            'currency' => $validated['currency'] ?? null,
            'duration_in_months' => $validated['duration_in_months'] ?? null,
            'max_redemptions' => $validated['max_redemptions'] ?? null,
            'redeem_by' => isset($validated['redeem_by']) ? strtotime($validated['redeem_by']) : null,
        ];

        $coupon = $service->createCoupon(array_filter($couponData, fn($v) => $v !== null));

        if ($request->boolean('create_promo')) {
            $promoData = [
                'code' => $validated['promo_code'] ?? null,
                'max_redemptions' => $validated['promo_max_redemptions'] ?? null,
                'expires_at' => isset($validated['promo_expires_at']) ? strtotime($validated['promo_expires_at']) : null,
            ];
            $service->createPromotionCode($coupon->id, array_filter($promoData, fn($v) => $v !== null));
        }

        return redirect()->route('stripe-manager.coupons.create')
            ->with('success', 'Coupon created successfully' . ($request->boolean('create_promo') ? ' with promo code' : ''));
    }
}


