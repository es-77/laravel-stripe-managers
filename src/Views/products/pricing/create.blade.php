@extends('stripe-manager::layouts.app')

@section('title', 'Add Pricing Plan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tag me-2"></i>Add Pricing Plan</h2>
    <a href="{{ route('stripe-manager.products.show', $product) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Product
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Product: {{ $product->name }}</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('stripe-manager.products.pricing.store', $product) }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nickname" class="form-label">Plan Name (Optional)</label>
                                <input type="text" class="form-control" id="nickname" name="nickname" 
                                       value="{{ old('nickname') }}" placeholder="e.g., Basic Plan, Pro Plan">
                                <div class="form-text">A friendly name for this pricing plan</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="currency" class="form-label">Currency *</label>
                                <select class="form-select" id="currency" name="currency" required>
                                    <option value="">Select Currency</option>
                                    <option value="usd" {{ old('currency') == 'usd' ? 'selected' : '' }}>USD - US Dollar</option>
                                    <option value="eur" {{ old('currency') == 'eur' ? 'selected' : '' }}>EUR - Euro</option>
                                    <option value="gbp" {{ old('currency') == 'gbp' ? 'selected' : '' }}>GBP - British Pound</option>
                                    <option value="cad" {{ old('currency') == 'cad' ? 'selected' : '' }}>CAD - Canadian Dollar</option>
                                    <option value="aud" {{ old('currency') == 'aud' ? 'selected' : '' }}>AUD - Australian Dollar</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="unit_amount" class="form-label">Price (in cents) *</label>
                                <input type="number" class="form-control" id="unit_amount" name="unit_amount" 
                                       value="{{ old('unit_amount') }}" required min="0" step="1"
                                       placeholder="e.g., 999 for $9.99">
                                <div class="form-text">Enter amount in cents (e.g., 999 = $9.99)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="type" class="form-label">Billing Type *</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="">Select Type</option>
                                    <option value="one_time" {{ old('type') == 'one_time' ? 'selected' : '' }}>One-time Payment</option>
                                    <option value="recurring" {{ old('type') == 'recurring' ? 'selected' : '' }}>Recurring Subscription</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="recurring-options" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="billing_period" class="form-label">Billing Period</label>
                                    <select class="form-select" id="billing_period" name="billing_period">
                                        <option value="">Select Period</option>
                                        <option value="day" {{ old('billing_period') == 'day' ? 'selected' : '' }}>Daily</option>
                                        <option value="week" {{ old('billing_period') == 'week' ? 'selected' : '' }}>Weekly</option>
                                        <option value="month" {{ old('billing_period') == 'month' ? 'selected' : '' }}>Monthly</option>
                                        <option value="year" {{ old('billing_period') == 'year' ? 'selected' : '' }}>Yearly</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="billing_period_count" class="form-label">Interval Count</label>
                                    <input type="number" class="form-control" id="billing_period_count" name="billing_period_count" 
                                           value="{{ old('billing_period_count', 1) }}" min="1" step="1"
                                           placeholder="e.g., 2 for every 2 months">
                                    <div class="form-text">How often to charge (e.g., 2 = every 2 months)</div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="trial_period_days" class="form-label">Trial Period (Days)</label>
                                    <input type="number" class="form-control" id="trial_period_days" name="trial_period_days" 
                                           value="{{ old('trial_period_days') }}" min="0" step="1"
                                           placeholder="e.g., 7 for 7-day trial">
                                    <div class="form-text">Number of trial days (0 for no trial)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" 
                                   {{ old('active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (available for purchase)
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('stripe-manager.products.show', $product) }}" class="btn btn-secondary me-2">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Pricing Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-lightbulb me-2"></i>Tips</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>One-time:</strong> Customer pays once
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Recurring:</strong> Customer pays on schedule
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Price:</strong> Enter in cents (999 = $9.99)
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Trial:</strong> Optional free trial period
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('type').addEventListener('change', function() {
    const recurringOptions = document.getElementById('recurring-options');
    const billingPeriod = document.getElementById('billing_period');
    
    if (this.value === 'recurring') {
        recurringOptions.style.display = 'block';
        billingPeriod.required = true;
    } else {
        recurringOptions.style.display = 'none';
        billingPeriod.required = false;
    }
});

// Initialize on page load
document.getElementById('type').dispatchEvent(new Event('change'));
</script>
@endsection
