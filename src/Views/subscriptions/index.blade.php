
<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/subscriptions/index.blade.php -->
@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-refresh me-2"></i>Subscriptions</h2>
    <a href="{{ route('stripe-manager.subscriptions.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Create Subscription
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if($subscriptions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Trial Ends</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptions as $subscription)
                            <tr>
                                <td>
                                    <strong>{{ $subscription->user->name }}</strong><br>
                                    <small class="text-muted">{{ $subscription->user->email }}</small>
                                </td>
                                <td>{{ $subscription->product->name }}</td>
                                <td>
                                    ${{ $subscription->pricing->formatted_amount }}
                                    @if($subscription->pricing->interval)
                                        <small class="text-muted">/{{ $subscription->pricing->interval }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $subscription->status }}">
                                        {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if($subscription->trial_ends_at)
                                        {{ $subscription->trial_ends_at->format('M j, Y') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $subscription->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('stripe-manager.subscriptions.show', $subscription) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($subscription->status === 'active')
                                            <form action="{{ route('stripe-manager.subscriptions.cancel', $subscription) }}" 
                                                  method="POST" 
                                                  class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to cancel this subscription?')">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @elseif($subscription->status === 'canceled')
                                            <form action="{{ route('stripe-manager.subscriptions.resume', $subscription) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{ $subscriptions->links() }}
        @else
            <div class="text-center py-5">
                <i class="fas fa-refresh fa-3x text-muted mb-3"></i>
                <h5>No subscriptions found</h5>
                <p class="text-muted">Create your first subscription to get started.</p>
                <a href="{{ route('stripe-manager.subscriptions.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Subscription
                </a>
            </div>
        @endif
    </div>
</div>
@endsection