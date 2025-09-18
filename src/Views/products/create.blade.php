
@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus me-2"></i>Create Product</h2>
    <a href="{{ route('stripe-manager.products.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Products
    </a>
</div>

<form action="{{ route('stripe-manager.products.store') }}" method="POST">
    @csrf
    
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Product Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Pricing</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPricing()">
                        <i class="fas fa-plus me-1"></i>Add Price
                    </button>
                </div>
                <div class="card-body" id="pricing-container">
                    <div class="pricing-item border rounded p-3 mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Amount *</label>
                                <input type="number" 
                                       name="prices[0][amount]" 
                                       class="form-control" 
                                       step="0.01" 
                                       min="0" 
                                       required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Currency *</label>
                                <select name="prices[0][currency]" class="form-select" required>
                                    <option value="usd">USD</option>
                                    <option value="eur">EUR</option>
                                    <option value="gbp">GBP</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Billing Interval</label>
                                <select name="prices[0][interval]" class="form-select">
                                    <option value="">One-time</option>
                                    <option value="day">Daily</option>
                                    <option value="week">Weekly</option>
                                    <option value="month">Monthly</option>
                                    <option value="year">Yearly</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Count</label>
                                <input type="number" 
                                       name="prices[0][interval_count]" 
                                       class="form-control" 
                                       value="1" 
                                       min="1">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Actions</h5>
                </div>
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save me-2"></i>Create Product
                    </button>
                    <a href="{{ route('stripe-manager.products.index') }}" 
                       class="btn btn-secondary w-100">
                        Cancel
                    </a>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5>Help</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>One-time payments:</strong> Leave billing interval empty<br><br>
                        <strong>Subscriptions:</strong> Choose a billing interval (monthly, yearly, etc.)
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
let priceIndex = 1;

function addPricing() {
    const container = document.getElementById('pricing-container');
    const newPricing = document.createElement('div');
    newPricing.className = 'pricing-item border rounded p-3 mb-3 position-relative';
    newPricing.innerHTML = `
        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" onclick="removePricing(this)">
            <i class="fas fa-times"></i>
        </button>
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Amount *</label>
                <input type="number" 
                       name="prices[${priceIndex}][amount]" 
                       class="form-control" 
                       step="0.01" 
                       min="0" 
                       required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Currency *</label>
                <select name="prices[${priceIndex}][currency]" class="form-select" required>
                    <option value="usd">USD</option>
                    <option value="eur">EUR</option>
                    <option value="gbp">GBP</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Billing Interval</label>
                <select name="prices[${priceIndex}][interval]" class="form-select">
                    <option value="">One-time</option>
                    <option value="day">Daily</option>
                    <option value="week">Weekly</option>
                    <option value="month">Monthly</option>
                    <option value="year">Yearly</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Count</label>
                <input type="number" 
                       name="prices[${priceIndex}][interval_count]" 
                       class="form-control" 
                       value="1" 
                       min="1">
            </div>
        </div>
    `;
    container.appendChild(newPricing);
    priceIndex++;
}

function removePricing(button) {
    button.closest('.pricing-item').remove();
}
</script>
@endsection