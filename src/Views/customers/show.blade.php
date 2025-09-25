

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
        
        @if(isset($cards) && count($cards) > 0)
            <div class="card">
                <div class="card-header">
                    <h5>Payment Methods</h5>
                </div>
                <div class="card-body">
                    @foreach($cards as $card)
                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-light rounded">
                            <div class="d-flex align-items-center">
                                <i class="fab fa-cc-{{ $card->brand }} fa-2x me-3"></i>
                                <div>
                                    <strong>•••• •••• •••• {{ $card->last_four }}</strong>
                                    @if($card->is_default)
                                        <span class="badge bg-success ms-2">Default</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">
                                        {{ ucfirst($card->brand) }} • 
                                        {{ $card->exp_month }}/{{ $card->exp_year }}
                                    </small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if(!$card->is_default)
                                <form action="{{ route('stripe-manager.customers.set-default-payment-method', $customer) }}" method="POST" class="m-0">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="payment_method" value="{{ $card->stripe_payment_method_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        Set Default
                                    </button>
                                </form>
                                @endif
                                <form action="{{ route('stripe-manager.customers.remove-payment-method', $customer) }}" method="POST" class="m-0" onsubmit="return confirm('Remove this payment method?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="payment_method" value="{{ $card->stripe_payment_method_id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Add New Payment Method</h5>
            </div>
            <div class="card-body">
                @if(!$customer->hasStripeId())
                    <div class="alert alert-warning">Customer must have a Stripe ID first. Use the Create page with card or click "Setup Payment Method".</div>
                @else
                <div class="mb-3">
                    <label class="form-label">Card Details</label>
                    <div id="card-element" class="form-control" style="height: 40px; padding: 10px;"></div>
                    <div id="card-errors" class="text-danger mt-2"></div>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="set_as_default" checked>
                    <label class="form-check-label" for="set_as_default">Set as default</label>
                </div>
                <button id="add-card-btn" class="btn btn-primary">
                    <i class="fas fa-credit-card me-2"></i>Add Card
                </button>
                @endif
            </div>
        </div>

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

@if($customer->hasStripeId())
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Stripe === 'undefined') return;
    if (!window.STRIPE_PUBLISHABLE_KEY) return;

    const stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);
    const elements = stripe.elements();
    const cardElement = elements.create('card', { style: { base: { fontSize: '16px' } } });
    cardElement.mount('#card-element');
    cardElement.on('change', function(event) {
        document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
    });

    const addBtn = document.getElementById('add-card-btn');
    if (addBtn) {
        addBtn.addEventListener('click', async function() {
            try {
                this.disabled = true;
                const resp = await fetch("{{ route('stripe-manager.customers.init-setup') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') },
                    body: JSON.stringify({ user_id: {{ $customer->id }}, name: "{{ $customer->name }}", email: "{{ $customer->email }}" })
                });
                if (!resp.ok) throw new Error('Failed to init setup');
                const { client_secret } = await resp.json();

                const { setupIntent, error } = await stripe.confirmCardSetup(client_secret, {
                    payment_method: { card: cardElement, billing_details: { name: "{{ $customer->name }}", email: "{{ $customer->email }}" } }
                });
                if (error) throw error;

                const finalize = await fetch("{{ route('stripe-manager.customers.finalize-setup') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') },
                    body: JSON.stringify({ customer_id: {{ $customer->id }}, payment_method: setupIntent.payment_method, set_as_default: document.getElementById('set_as_default').checked })
                });
                if (!finalize.ok) throw new Error('Failed to save card');
                window.location.reload();
            } catch (e) {
                document.getElementById('card-errors').textContent = e.message || 'Failed to save card.';
                this.disabled = false;
            }
        });
    }
});
</script>
@endif
@endsection