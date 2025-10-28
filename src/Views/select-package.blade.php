@extends('stripe-manager::layouts.app')

@section('title', 'Select a Package')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-star me-2"></i>Select Your Package</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        @forelse($products as $product)
            @foreach($product->pricing->where('active', true) as $pricing)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 package-card">
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-3">
                                <i class="fas fa-box fa-3x text-primary mb-3"></i>
                                <h4>{{ $product->name }}</h4>
                                @if($product->description)
                                    <p class="text-muted mb-3">{{ $product->description }}</p>
                                @endif
                            </div>
                            
                            <div class="text-center mb-3">
                                <h3 class="mb-1">{{ $pricing->getFormattedPriceAttribute() }}</h3>
                                @if($pricing->nickname)
                                    <p class="text-muted small">{{ $pricing->nickname }}</p>
                                @endif
                                @if($pricing->billing_period)
                                    <p class="text-muted small">Billed {{ $pricing->billing_period }}</p>
                                @endif
                                @if($pricing->trial_period_days > 0)
                                    <span class="badge bg-success">
                                        <i class="fas fa-gift me-1"></i>{{ $pricing->trial_period_days }} day trial
                                    </span>
                                @endif
                            </div>

                            <form action="{{ route('stripe-manager.packages.subscribe') }}" method="POST" class="mt-auto">
                                @csrf
                                <input type="hidden" name="pricing_id" value="{{ $pricing->id }}">
                                <div class="mb-3">
                                    <label class="form-label">Coupon / Promo code (optional)</label>
                                    <input type="text" name="coupon" class="form-control" placeholder="Enter coupon or promo code">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-check me-2"></i>Select This Package
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-box fa-3x text-muted mb-3"></i>
                        <h5>No packages available</h5>
                        <p class="text-muted">Please contact support or check back later.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

</div>

<style>
        .package-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid var(--sm-border);
        }
        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .package-card .card-body {
            position: relative;
        }
    </style>

    
</div>
@endsection
