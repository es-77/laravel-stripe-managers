@extends('stripe-manager::layouts.app')

@section('title', 'Dashboard')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
</div>

<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-primary rounded-circle p-3">
                        <i class="fas fa-box text-white fa-2x"></i>
                    </div>
                </div>
                <h5 class="card-title">Products</h5>
                <h3 class="text-primary">{{ \EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct::count() }}</h3>
                <a href="{{ route('stripe-manager.products.index') }}" class="btn btn-primary btn-sm">
                    View All
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-success rounded-circle p-3">
                        <i class="fas fa-users text-white fa-2x"></i>
                    </div>
                </div>
                <h5 class="card-title">Customers</h5>
                <h3 class="text-success">{{ app(config('stripe-manager.stripe.model'))::whereNotNull('stripe_id')->count() }}</h3>
                <a href="{{ route('stripe-manager.customers.index') }}" class="btn btn-success btn-sm">
                    View All
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-info rounded-circle p-3">
                        <i class="fas fa-refresh text-white fa-2x"></i>
                    </div>
                </div>
                <h5 class="card-title">Active Subscriptions</h5>
                <h3 class="text-info">{{ \EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription::where('stripe_status', 'active')->count() }}</h3>
                <a href="{{ route('stripe-manager.subscriptions.index') }}" class="btn btn-info btn-sm">
                    View All
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-center">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="bg-warning rounded-circle p-3">
                        <i class="fas fa-dollar-sign text-white fa-2x"></i>
                    </div>
                </div>
                <h5 class="card-title">Total Revenue</h5>
                <h3 class="text-warning">${{ number_format(\EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscriptionPayment::where('status', 'paid')->sum('amount') / 100, 2) }}</h3>
                <small class="text-muted">All time</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-line me-2"></i>Recent Subscriptions</h5>
            </div>
            <div class="card-body">
                @php
                    $recentSubscriptions = \EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription::with('user', 'product')->latest()->take(5)->get();
                @endphp
                
                @if($recentSubscriptions->count() > 0)
                    @foreach($recentSubscriptions as $subscription)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong>{{ $subscription->user->name }}</strong><br>
                                <small class="text-muted">{{ $subscription->product->name }}</small>
                            </div>
                            <span class="status-badge status-{{ $subscription->status }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </div>
                    @endforeach
                @else
                    <p class="text-muted text-center">No subscriptions yet</p>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="{{ route('stripe-manager.products.create') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus me-2"></i>Create New Product
                    </a>
                    <a href="{{ route('stripe-manager.customers.create') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-plus me-2"></i>Add New Customer
                    </a>
                    <a href="{{ route('stripe-manager.subscriptions.create') }}" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus-circle me-2"></i>Create Subscription
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
