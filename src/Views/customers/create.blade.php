
<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/customers/create.blade.php -->
@extends('stripe-manager::layouts.app')

@section('title', 'Add Customer')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-user-plus me-2"></i>Add Customer</h2>
    <a href="{{ route('stripe-manager.customers.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Customers
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Customer Information</h5>
                    <form method="GET" action="{{ url()->current() }}" class="d-flex gap-2">
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search users by name, email, or ID" />
                        <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach([10,25,50,100] as $size)
                                <option value="{{ $size }}" {{ (int)request('per_page', 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <form id="create-customer-form" action="{{ route('stripe-manager.customers.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User *</label>
                        <select class="form-select @error('user_id') is-invalid @enderror" 
                                id="user_id" 
                                name="user_id" 
                                required>
                            <option value="">Choose a user...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" 
                                        {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Only users without Stripe accounts are shown.</div>
                        @if(method_exists($users, 'links'))
                            <div class="mt-2">
                                {{ $users->onEachSide(1)->links() }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Customer Name *</label>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Card Details (optional)</label>
                        <div id="card-element" class="form-control" style="height: 40px; padding: 10px;"></div>
                        <div id="card-errors" class="text-danger mt-2"></div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="set_as_default" name="set_as_default" checked>
                            <label class="form-check-label" for="set_as_default">
                                Set as default payment method
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="create-and-save" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Create Customer & Save Card
                        </button>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>Create Without Card
                        </button>
                        <a href="{{ route('stripe-manager.customers.index') }}" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Help</h5>
            </div>
            <div class="card-body">
                <small class="text-muted">
                    Creating a customer will:
                    <ul>
                        <li>Create a Stripe customer account</li>
                        <li>Link it to the selected user</li>
                        <li>Allow the user to make purchases</li>
                        <li>Enable subscription management</li>
                    </ul>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
const stripe = Stripe(window.STRIPE_PUBLISHABLE_KEY);
const elements = stripe.elements();
const cardElement = elements.create('card', { style: { base: { fontSize: '16px' } } });
cardElement.mount('#card-element');
cardElement.on('change', function(event) {
    document.getElementById('card-errors').textContent = event.error ? event.error.message : '';
});

async function initSetup(userId, name, email) {
    const resp = await fetch("{{ route('stripe-manager.customers.init-setup') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        },
        body: JSON.stringify({ user_id: userId, name, email })
    });
    if (!resp.ok) throw new Error('Failed to init setup');
    return await resp.json();
}

async function finalizeSetup(customerId, paymentMethod, setAsDefault) {
    const resp = await fetch("{{ route('stripe-manager.customers.finalize-setup') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
        },
        body: JSON.stringify({ customer_id: customerId, payment_method: paymentMethod, set_as_default: setAsDefault })
    });
    if (!resp.ok) throw new Error('Failed to finalize');
    return await resp.json();
}

document.getElementById('create-and-save').addEventListener('click', async function () {
    const userId = document.getElementById('user_id').value;
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const setAsDefault = document.getElementById('set_as_default').checked;

    if (!userId || !name || !email) {
        document.getElementById('card-errors').textContent = 'Please fill user, name and email first.';
        return;
    }

    try {
        this.disabled = true;
        const { client_secret, customer_id } = await initSetup(userId, name, email);

        const { setupIntent, error } = await stripe.confirmCardSetup(client_secret, {
            payment_method: { card: cardElement, billing_details: { name, email } }
        });
        if (error) throw error;

        await finalizeSetup(customer_id, setupIntent.payment_method, setAsDefault);
        window.location = "{{ route('stripe-manager.customers.index') }}";
    } catch (e) {
        document.getElementById('card-errors').textContent = e.message || 'Failed to save card.';
        this.disabled = false;
    }
});
document.getElementById('user_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        const userInfo = selectedOption.text;
        const emailMatch = userInfo.match(/\(([^)]+)\)/);
        const nameMatch = userInfo.match(/^([^(]+)/);
        
        if (emailMatch) {
            document.getElementById('email').value = emailMatch[1];
        }
        if (nameMatch) {
            document.getElementById('name').value = nameMatch[1].trim();
        }
    }
});
</script>
@endsection