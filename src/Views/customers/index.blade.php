<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/customers/index.blade.php -->
@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users me-2"></i>Customers</h2>
    <a href="{{ route('stripe-manager.customers.create') }}" class="btn btn-primary">
        <i class="fas fa-user-plus me-2"></i>Add Customer
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($customers->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Stripe ID</th>
                            <th>Subscriptions</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td>
                                    <strong>{{ $customer->name }}</strong>
                                </td>
                                <td>{{ $customer->email }}</td>
                                <td>
                                    <small class="text-muted">{{ $customer->stripe_id }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $customer->subscriptions->count() }}
                                    </span>
                                </td>
                                <td>{{ $customer->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('stripe-manager.customers.show', $customer) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($customer->hasStripeId())
                                            <a href="{{ route('stripe-manager.customers.setup-payment', $customer) }}" 
                                               class="btn btn-outline-success">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{ $customers->links() }}
        @else
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h5>No customers found</h5>
                <p class="text-muted">Add your first customer to get started.</p>
                <a href="{{ route('stripe-manager.customers.create') }}" class="btn btn-primary">
                    <i class="fas fa-user-plus me-2"></i>Add Customer
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
