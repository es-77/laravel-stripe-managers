

<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/customers/show.blade.php -->
@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user me-2"></i>{{ $customer->name }}</h2>
    <div>
        @if($customer->hasStripeId())
            <a href="{{ route('stripe-manager.customers.setup-payment', $customer) }}" 
               class="btn btn-success me-2">
                <i class="fas fa-credit-card me-2"></i>Setup Payment Method
            </a>
        @endif
        <a href="{{ route('stripe-manager.customers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Customers
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Customer Details</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Name:</dt>
                    <dd class="col-sm-8">{{ $customer->name }}</dd>
                    
                    <dt class="col-sm-4">Email:</dt>
                    <dd class="col-sm-8">{{ $customer->email }}</dd>
                    
                    <dt class="col-sm-4">Stripe ID:</dt>
                    <dd class="col-sm-8">
                        @if($customer->hasStripeId())
                            <code>{{ $customer->stripe_id }}</code>
                        @else
                            <span class="text-muted">Not created</span>
                        @endif
                    </dd>
                    
                    <dt class="col-sm-4">Created:</dt>
                    <dd class="col-sm-8">{{ $customer->created_at->format('M j, Y g:i A') }}</dd>
                </dl>
            </div>
        </div>
        
        @if(count($paymentMethods) > 0)
            <div class="card">
                <div class="card-header">
                    <h5>Payment Methods</h5>
                </div>
                <div class="card-body">
                    @foreach($paymentMethods as $method)
                        <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                            <i class="fab fa-cc-{{ $method->card->brand }} fa-2x me-3"></i>
                            <div>
                                <strong>•••• •••• •••• {{ $method->card->last4 }}</strong><br>
                                <small class="text-muted">
                                    {{ ucfirst($method->card->brand) }} • 
                                    {{ $method->card->exp_month }}/{{ $method->card->exp_year }}
                                </small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Subscriptions</h5>
                <a href="{{ route('stripe-manager.subscriptions.create', ['customer_id' => $customer->id]) }}" 
                   class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>New Subscription
                </a>
            </div>
            <div class="card-body">
                @if($customer->subscriptions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customer->subscriptions as $subscription)
                                    <tr>
                                        <td>{{ $subscription->product->name }}</td>
                                        <td>
                                            ${{ $subscription->pricing->formatted_amount }}
                                            @if($subscription->pricing->interval)
                                                /{{ $subscription->pricing->interval }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge status-{{ $subscription->status }}">
                                                {{ ucfirst($subscription->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $subscription->created_at->format('M j, Y') }}</td>
                                        <td>
                                            <a href="{{ route('stripe-manager.subscriptions.show', $subscription) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-refresh fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No subscriptions yet</p>
                        <a href="{{ route('stripe-manager.subscriptions.create', ['customer_id' => $customer->id]) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Create First Subscription
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection