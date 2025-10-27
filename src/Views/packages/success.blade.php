@extends('stripe-manager::layouts.app')

@section('title', 'Subscription Success')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center">
                <div class="card-body py-5">
                    <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
                    <h2 class="mb-3">Subscription Successful!</h2>
                    <p class="text-muted mb-4">
                        Thank you for subscribing. Your subscription has been activated and you now have access to all premium features.
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('stripe-manager.dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="{{ route('stripe-manager.api.user-subscription') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-eye me-2"></i>View Subscription
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
