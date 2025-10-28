<?php

namespace EmmanuelSaleem\LaravelStripeManager\Services;

use Illuminate\Support\Facades\Log;

class CouponService
{
    protected bool $apiKeySet = false;

    protected function ensureApiKey(): void
    {
        if (!$this->apiKeySet) {
            \Stripe\Stripe::setApiKey(config('stripe-manager.stripe.secret'));
            $this->apiKeySet = true;
        }
    }

    public function createCoupon(array $data): \Stripe\Coupon
    {
        $this->ensureApiKey();
        try {
            $payload = [];

            if (isset($data['percent_off'])) {
                $payload['percent_off'] = (int) $data['percent_off'];
            }

            if (isset($data['amount_off'])) {
                $payload['amount_off'] = (int) $data['amount_off'];
                $payload['currency'] = $data['currency'] ?? 'usd';
            }

            $payload['duration'] = $data['duration'] ?? 'once'; // once | repeating | forever
            if (($payload['duration'] ?? null) === 'repeating' && isset($data['duration_in_months'])) {
                $payload['duration_in_months'] = (int) $data['duration_in_months'];
            }

            if (!empty($data['name'])) {
                $payload['name'] = $data['name'];
            }

            if (isset($data['max_redemptions'])) {
                $payload['max_redemptions'] = (int) $data['max_redemptions'];
            }

            if (!empty($data['redeem_by'])) {
                $payload['redeem_by'] = (int) $data['redeem_by']; // unix timestamp
            }

            return \Stripe\Coupon::create($payload);
        } catch (\Exception $e) {
            Log::error('Failed to create coupon', [ 'error' => $e->getMessage(), 'data' => $data ]);
            throw $e;
        }
    }

    public function createPromotionCode(string $couponId, array $data): \Stripe\PromotionCode
    {
        $this->ensureApiKey();
        try {
            $payload = [
                'coupon' => $couponId,
                'active' => $data['active'] ?? true,
            ];

            if (!empty($data['code'])) {
                $payload['code'] = $data['code'];
            }

            if (isset($data['max_redemptions'])) {
                $payload['max_redemptions'] = (int) $data['max_redemptions'];
            }

            if (!empty($data['expires_at'])) {
                $payload['expires_at'] = (int) $data['expires_at'];
            }

            return \Stripe\PromotionCode::create($payload);
        } catch (\Exception $e) {
            Log::error('Failed to create promotion code', [ 'error' => $e->getMessage(), 'coupon' => $couponId, 'data' => $data ]);
            throw $e;
        }
    }
}


