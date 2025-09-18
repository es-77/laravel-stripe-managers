
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
                <h5>Customer Information</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('stripe-manager.customers.store') }}" method="POST">
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
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Customer
                        </button>
                        <a href="{{ route('stripe-manager.customers.index') }}" 
                           class="btn btn-secondary">
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