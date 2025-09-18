@extends('stripe-manager::layouts.app')

@section('title', 'Manage Trial Period')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-clock me-2"></i>Manage Trial Period</h2>
    <a href="{{ route('stripe-manager.subscriptions.show', $subscription) }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back to Subscription
    </a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-info-circle me-2"></i>Subscription Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Customer</h6>
                        <p class="text-muted">{{ $subscription->user->name ?? 'N/A' }} ({{ $subscription->user->email ?? 'N/A' }})</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Product</h6>
                        <p class="text-muted">{{ $subscription->product->name ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Current Status</h6>
                        <span class="badge {{ $subscription->stripe_status === 'trialing' ? 'bg-info' : 'bg-secondary' }}">
                            {{ $subscription->getFormattedStatusAttribute() }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <h6>Current Trial End</h6>
                        <p class="text-muted">
                            @if($subscription->trial_end)
                                {{ $subscription->trial_end->format('M j, Y g:i A') }}
                                @if($subscription->trial_end->isFuture())
                                    <small class="text-success">({{ $subscription->trial_end->diffForHumans() }})</small>
                                @else
                                    <small class="text-danger">(Expired {{ $subscription->trial_end->diffForHumans() }})</small>
                                @endif
                            @else
                                <span class="text-muted">No trial period</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i>Modify Trial Period</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('stripe-manager.subscriptions.trial.update', $subscription) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="action" class="form-label">Action *</label>
                                <select class="form-select @error('action') is-invalid @enderror" 
                                        id="action" 
                                        name="action" 
                                        required
                                        onchange="updateFormFields()">
                                    <option value="">Select Action</option>
                                    <option value="extend" {{ old('action') == 'extend' ? 'selected' : '' }}>
                                        Extend Trial Period
                                    </option>
                                    <option value="reduce" {{ old('action') == 'reduce' ? 'selected' : '' }}>
                                        Reduce Trial Period
                                    </option>
                                    <option value="remove" {{ old('action') == 'remove' ? 'selected' : '' }}>
                                        Remove Trial Period
                                    </option>
                                </select>
                                @error('action')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3" id="trial-days-field">
                                <label for="trial_days" class="form-label">Days *</label>
                                <input type="number" 
                                       class="form-control @error('trial_days') is-invalid @enderror" 
                                       id="trial_days" 
                                       name="trial_days" 
                                       value="{{ old('trial_days', 7) }}" 
                                       min="0" 
                                       max="365">
                                @error('trial_days')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text" id="trial-help-text">
                                    Enter number of days to extend the trial period
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info" id="action-info" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="action-description"></span>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('stripe-manager.subscriptions.show', $subscription) }}" 
                           class="btn btn-secondary me-2">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save me-2"></i>Update Trial Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-lightbulb me-2"></i>Trial Management Help</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <i class="fas fa-plus-circle text-success me-2"></i>
                        <strong>Extend:</strong> Add days to current trial period
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-minus-circle text-warning me-2"></i>
                        <strong>Reduce:</strong> Remove days from current trial period
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-times-circle text-danger me-2"></i>
                        <strong>Remove:</strong> End trial period immediately
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Changes are applied immediately in Stripe
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Customer will be notified of trial changes
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Cannot reduce trial to a past date
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        All changes are logged for audit purposes
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function updateFormFields() {
    const action = document.getElementById('action').value;
    const trialDaysField = document.getElementById('trial-days-field');
    const trialHelpText = document.getElementById('trial-help-text');
    const actionInfo = document.getElementById('action-info');
    const actionDescription = document.getElementById('action-description');
    const submitBtn = document.getElementById('submit-btn');
    
    // Reset form
    trialDaysField.style.display = 'block';
    actionInfo.style.display = 'none';
    
    switch(action) {
        case 'extend':
            trialHelpText.textContent = 'Enter number of days to extend the trial period';
            actionDescription.innerHTML = '<strong>Extend Trial:</strong> This will add the specified number of days to the current trial period.';
            actionInfo.style.display = 'block';
            submitBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Extend Trial Period';
            submitBtn.className = 'btn btn-success';
            break;
            
        case 'reduce':
            trialHelpText.textContent = 'Enter number of days to reduce from the trial period';
            actionDescription.innerHTML = '<strong>Reduce Trial:</strong> This will remove the specified number of days from the current trial period.';
            actionInfo.style.display = 'block';
            submitBtn.innerHTML = '<i class="fas fa-minus me-2"></i>Reduce Trial Period';
            submitBtn.className = 'btn btn-warning';
            break;
            
        case 'remove':
            trialDaysField.style.display = 'none';
            actionDescription.innerHTML = '<strong>Remove Trial:</strong> This will end the trial period immediately and start billing.';
            actionInfo.style.display = 'block';
            submitBtn.innerHTML = '<i class="fas fa-times me-2"></i>Remove Trial Period';
            submitBtn.className = 'btn btn-danger';
            break;
            
        default:
            actionInfo.style.display = 'none';
            submitBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Trial Period';
            submitBtn.className = 'btn btn-primary';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFormFields();
});
</script>
@endsection
