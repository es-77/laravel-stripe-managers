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

    <!-- Payment Method Setup Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Setup Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="card-element" class="mb-3">
                        <!-- Stripe Elements will create the form elements here -->
                    </div>
                    <div id="card-errors" class="text-danger"></div>
                    <input type="hidden" id="selected-pricing-id" name="selected_pricing_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submit-payment">Submit Payment</button>
                </div>
            </div>
        </div>
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

    <script src="https://js.stripe.com/v3"></script>
    <script>
        var stripe = Stripe("{{ config('stripe.key') ?: config('cashier.key') }}");
        var elements = stripe.elements();

        // Setup payment method and subscribe to selected package
        document.addEventListener('submit', async function(e) {
            if (!e.target.matches('form')) return;
            
            e.preventDefault();
            const form = e.target;
            const pricingId = form.querySelector('input[name="pricing_id"]').value;
            const button = form.querySelector('button[type="submit"]');
            
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            try {
                const response = await fetch("{{ route('stripe-manager.api.select-subscription-plan') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: pricingId,
                        payment_method_id: 'pm_card_visa' // In production, collect real payment method
                    })
                });

                const data = await response.json();
                
                if (data.status) {
                    window.location.href = "{{ route('stripe-manager.packages.success') }}";
                } else {
                    alert('Error: ' + data.message);
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-check me-2"></i>Select This Package';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-check me-2"></i>Select This Package';
            }
        });
    </script>
</div>
@endsection
