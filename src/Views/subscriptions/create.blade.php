

<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/subscriptions/create.blade.php -->
@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus me-2"></i>Create Subscription</h2>
    <a href="{{ route('stripe-manager.subscriptions.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Subscriptions
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5>Subscription Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('stripe-manager.subscriptions.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer *</label>
                        <select class="form-select @error('customer_id') is-invalid @enderror" 
                                id="customer_id" 
                                name="customer_id" 
                                required>
                            <option value="">Choose a customer...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" 
                                        {{ old('customer_id', request('customer_id')) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product *</label>
                        <select class="form-select @error('product_id') is-invalid @enderror" 
                                id="product_id" 
                                name="product_id" 
                                required 
                                onchange="updatePricing()">
                            <option value="">Choose a product...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" 
                                        data-pricing="{{ $product->pricing->toJson() }}"
                                        {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="pricing_id" class="form-label">Pricing *</label>
                        <select class="form-select @error('pricing_id') is-invalid @enderror" 
                                id="pricing_id" 
                                name="pricing_id" 
                                required>
                            <option value="">Choose pricing...</option>
                        </select>
                        @error('pricing_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="trial_days" class="form-label">Trial Days</label>
                        <input type="number" 
                               class="form-control @error('trial_days') is-invalid @enderror" 
                               id="trial_days" 
                               name="trial_days" 
                               value="{{ old('trial_days', 14) }}" 
                               min="0" 
                               max="365">
                        @error('trial_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Leave empty for no trial period</div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Subscription
                        </button>
                        <a href="{{ route('stripe-manager.subscriptions.index') }}" 
                           class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Help</h5>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    <strong>Requirements:</strong>
                    <ul>
                        <li>Customer must have a Stripe account</li>
                        <li>Customer should have a payment method setup</li>
                        <li>Product must have recurring pricing</li>
                    </ul>
                    
                    <strong>Trial Period:</strong><br>
                    Set trial days to give customers free access before billing starts.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
function updatePricing() {
    const productSelect = document.getElementById('product_id');
    const pricingSelect = document.getElementById('pricing_id');
    
    // Clear existing options
    pricingSelect.innerHTML = '<option value="">Choose pricing...</option>';
    
    if (productSelect.value) {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const pricing = JSON.parse(selectedOption.dataset.pricing || '[]');
        
        let hasRecurringPricing = false;
        
        pricing.forEach(price => {
            // Only show recurring pricing for subscriptions
            if (price.type === 'recurring' && price.active) {
                hasRecurringPricing = true;
                const option = document.createElement('option');
                option.value = price.id;
                
                // Format the price display
                const amount = (price.unit_amount / 100).toFixed(2);
                const currency = price.currency.toUpperCase();
                let intervalText = '';
                
                if (price.billing_period) {
                    const interval = price.billing_period_count > 1 
                        ? `${price.billing_period_count} ${price.billing_period}s` 
                        : price.billing_period;
                    intervalText = ` / ${interval}`;
                }
                
                // Add nickname if available
                const displayName = price.nickname ? `${price.nickname} - ` : '';
                option.textContent = `${displayName}${currency} ${amount}${intervalText}`;
                pricingSelect.appendChild(option);
            }
        });
        
        // Show message if no recurring pricing available
        if (!hasRecurringPricing) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No recurring pricing available';
            option.disabled = true;
            pricingSelect.appendChild(option);
        }
    }
}

// Initialize pricing on page load if product is already selected
document.addEventListener('DOMContentLoaded', function() {
    updatePricing();
});
</script>
@endsection