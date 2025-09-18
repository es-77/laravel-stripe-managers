@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-refresh me-2"></i>Subscription Details</h2>
    <div>
        @if($subscription->isActive())
            <form action="{{ route('stripe-manager.subscriptions.cancel', $subscription->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger me-2" 
                        onclick="return confirm('Are you sure you want to cancel this subscription?')">
                    <i class="fas fa-times me-2"></i>Cancel Subscription
                </button>
            </form>
        @elseif($subscription->cancelled())
            <form action="{{ route('stripe-manager.subscriptions.resume', $subscription->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success me-2">
                    <i class="fas fa-play me-2"></i>Resume Subscription
                </button>
            </form>
        @endif
        <a href="{{ route('stripe-manager.subscriptions.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Subscriptions
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Subscription Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer</h6>
                        <p class="text-muted">{{ $subscription->user->name }} ({{ $subscription->user->email }})</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Product</h6>
                        <p class="text-muted">{{ $subscription->product->name }}</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Stripe Subscription ID</h6>
                        <p class="text-muted"><code>{{ $subscription->stripe_subscription_id }}</code></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Status</h6>
                        <span class="badge {{ $subscription->isActive() ? 'bg-success' : ($subscription->cancelled() ? 'bg-danger' : 'bg-warning') }}">
                            {{ $subscription->getFormattedStatusAttribute() }}
                        </span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Period</h6>
                        <p class="text-muted">
                            @if($subscription->current_period_start && $subscription->current_period_end)
                                {{ $subscription->current_period_start->format('M d, Y') }} - 
                                {{ $subscription->current_period_end->format('M d, Y') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Quantity</h6>
                        <p class="text-muted">{{ $subscription->quantity }}</p>
                    </div>
                </div>
                
                @if($subscription->onTrial())
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Trial Period</h6>
                            <p class="text-muted">
                                @if($subscription->trial_start && $subscription->trial_end)
                                    {{ $subscription->trial_start->format('M d, Y') }} - 
                                    {{ $subscription->trial_end->format('M d, Y') }}
                                    <br>
                                    <small class="text-info">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $subscription->trial_end->diffForHumans() }}
                                    </small>
                                @else
                                    N/A
                                @endif
                            </p>
                            <a href="{{ route('stripe-manager.subscriptions.trial.edit', $subscription) }}" 
                               class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-clock me-1"></i>Manage Trial Period
                            </a>
                        </div>
                    </div>
                @elseif($subscription->stripe_status === 'active' && !$subscription->trial_end)
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Trial Period</h6>
                            <p class="text-muted">No trial period</p>
                            <a href="{{ route('stripe-manager.subscriptions.trial.edit', $subscription) }}" 
                               class="btn btn-sm btn-outline-info">
                                <i class="fas fa-plus me-1"></i>Add Trial Period
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($subscription->cancelled())
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Cancelled At</h6>
                            <p class="text-muted">{{ $subscription->canceled_at ? $subscription->canceled_at->format('M d, Y H:i') : 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>Ends At</h6>
                            <p class="text-muted">{{ $subscription->ends_at ? $subscription->ends_at->format('M d, Y H:i') : 'N/A' }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Pricing Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Plan Name</h6>
                        <p class="text-muted">{{ $subscription->pricing->nickname ?: 'Default Plan' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Price</h6>
                        <p class="text-muted">{{ $subscription->pricing->getFormattedPriceAttribute() }}</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Billing Period</h6>
                        <p class="text-muted">
                            @if($subscription->pricing->type === 'recurring')
                                {{ $subscription->pricing->billing_period_count > 1 ? $subscription->pricing->billing_period_count . ' ' . Str::plural($subscription->pricing->billing_period) : $subscription->pricing->billing_period }}
                            @else
                                One-time
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Currency</h6>
                        <p class="text-muted">{{ strtoupper($subscription->pricing->currency) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Payment History</h5>
            </div>
            <div class="card-body">
                @if($subscription->payments->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($subscription->payments->take(5) as $payment)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $payment->getFormattedCurrencyAmountAttribute() }}</h6>
                                        <small class="text-muted">
                                            {{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : 'Pending' }}
                                        </small>
                                    </div>
                                    <span class="badge {{ $payment->getStatusBadgeClassAttribute() }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if($subscription->payments->count() > 5)
                        <div class="text-center mt-3">
                            <small class="text-muted">And {{ $subscription->payments->count() - 5 }} more payments</small>
                        </div>
                    @endif
                @else
                    <div class="text-center py-3">
                        <i class="fas fa-credit-card fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No payments yet</p>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @if($subscription->isActive())
                        <form action="{{ route('stripe-manager.subscriptions.cancel', $subscription->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100" 
                                    onclick="return confirm('Are you sure you want to cancel this subscription?')">
                                <i class="fas fa-times me-2"></i>Cancel Subscription
                            </button>
                        </form>
                    @elseif($subscription->cancelled())
                        <form action="{{ route('stripe-manager.subscriptions.resume', $subscription->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-play me-2"></i>Resume Subscription
                            </button>
                        </form>
                    @endif
                    
                    <a href="{{ route('stripe-manager.customers.show', $subscription->user->id) }}" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-user me-2"></i>View Customer
                    </a>
                    
                    <a href="{{ route('stripe-manager.products.show', $subscription->product->id) }}" 
                       class="btn btn-outline-info">
                        <i class="fas fa-box me-2"></i>View Product
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
