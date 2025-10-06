
<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/customers/setup-payment.blade.php -->
@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-credit-card me-2"></i>Setup Payment Method</h2>
    <a href="{{ route('stripe-manager.customers.show', $customer) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Customer
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Add Payment Method for {{ $customer->name }}</h5>
            </div>
            <div class="card-body">
                <form id="payment-form">
                    <div class="mb-3">
                        <label for="card-element" class="form-label">Credit or Debit Card</label>
                        <div id="card-element" class="form-control" style="height: 40px; padding: 10px;">
                            <!-- Stripe Elements will create form elements here -->
                        </div>
                        <div id="card-errors" class="text-danger mt-2" role="alert"></div>
                    </div>
                    
                    <button type="submit" id="submit-button" class="btn btn-primary w-100">
                        <span id="button-text">
                            <i class="fas fa-save me-2"></i>Save Payment Method
                        </span>
                        <div id="spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Stripe === 'undefined') {
        document.getElementById('card-errors').textContent = 'Stripe failed to load. Please refresh the page.';
        return;
    }

    if (!window.STRIPE_PUBLISHABLE_KEY) {
        document.getElementById('card-errors').textContent = 'Stripe publishable key is not configured.';
        return;
    }

    const stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);
    const elements = stripe.elements();

    const cardElement = elements.create('card', {
        style: {
            base: { fontSize: '16px' }
        },
    });

    cardElement.mount('#card-element');

    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        displayError.textContent = event.error ? event.error.message : '';
    });

    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');
        submitButton.disabled = true;
        buttonText.style.display = 'none';
        spinner.style.display = 'inline-block';

        const {setupIntent, error} = await stripe.confirmCardSetup(
            '{{ $intent->client_secret }}',
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: '{{ $customer->name }}',
                        email: '{{ $customer->email }}',
                    }
                }
            }
        );

        if (error) {
            document.getElementById('card-errors').textContent = error.message;
            submitButton.disabled = false;
            buttonText.style.display = 'inline';
            spinner.style.display = 'none';
        } else {
            window.location.href = '{{ route('stripe-manager.customers.show', $customer) }}';
        }
    });
});
</script>
@endsection