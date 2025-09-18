@extends('stripe-manager::layouts.app')

@section('title', 'Edit Pricing Plan')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-edit me-2"></i>Edit Pricing Plan</h2>
    <a href="{{ route('stripe-manager.products.show', $pricing->product) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Product
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Product: {{ $pricing->product->name }}</h5>
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

                <form action="{{ route('stripe-manager.products.pricing.update', $pricing) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nickname" class="form-label">Plan Name</label>
                                <input type="text" class="form-control" id="nickname" name="nickname" 
                                       value="{{ old('nickname', $pricing->nickname) }}" placeholder="e.g., Basic Plan, Pro Plan">
                                <div class="form-text">A friendly name for this pricing plan</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Current Price</label>
                                <div class="form-control-plaintext">
                                    <span class="badge bg-info fs-6">{{ $pricing->getFormattedPriceAttribute() }}</span>
                                </div>
                                <div class="form-text">Price cannot be changed after creation in Stripe</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Billing Type</label>
                                <div class="form-control-plaintext">
                                    <span class="badge {{ $pricing->type === 'recurring' ? 'bg-info' : 'bg-primary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $pricing->type)) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <div class="form-control-plaintext">
                                    <span class="badge bg-secondary">{{ strtoupper($pricing->currency) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($pricing->type === 'recurring')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Billing Period</label>
                                <div class="form-control-plaintext">
                                    {{ $pricing->billing_period_count > 1 ? $pricing->billing_period_count . ' ' . Str::plural($pricing->billing_period) : $pricing->billing_period }}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Trial Period</label>
                                <div class="form-control-plaintext">
                                    {{ $pricing->trial_period_days ? $pricing->trial_period_days . ' days' : 'No trial' }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1" 
                                   {{ old('active', $pricing->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active (available for purchase)
                            </label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('stripe-manager.products.show', $pricing->product) }}" class="btn btn-secondary me-2">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Pricing Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-info-circle me-2"></i>Pricing Details</h6>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Stripe ID:</dt>
                    <dd class="col-sm-8">
                        <code class="small">{{ $pricing->stripe_price_id }}</code>
                    </dd>
                    
                    <dt class="col-sm-4">Amount:</dt>
                    <dd class="col-sm-8">{{ $pricing->unit_amount }} cents</dd>
                    
                    <dt class="col-sm-4">Type:</dt>
                    <dd class="col-sm-8">{{ ucfirst(str_replace('_', ' ', $pricing->type)) }}</dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge {{ $pricing->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $pricing->active ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                    
                    @if($pricing->created_at)
                    <dt class="col-sm-4">Created:</dt>
                    <dd class="col-sm-8">{{ $pricing->created_at->format('M j, Y g:i A') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Price and billing details cannot be changed after creation
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        You can only activate/deactivate the pricing plan
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        To change pricing, create a new pricing plan
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
