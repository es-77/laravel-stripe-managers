<?php

namespace EmmanuelSaleem\LaravelStripeManager\Traits;

trait HasStripeId
{
    /**
     * Check if the user has a Stripe ID
     *
     * @return bool
     */
    public function hasStripeId(): bool
    {
        return !is_null($this->stripe_id);
    }

    /**
     * Get the Stripe ID
     *
     * @return string|null
     */
    public function getStripeId(): ?string
    {
        return $this->stripe_id;
    }
}
