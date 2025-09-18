@extends('stripe-manager::layouts.app')

@section('title', 'Webhook Management')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-webhook me-2"></i>Webhook Management</h2>
    <button class="btn btn-primary" onclick="refreshWebhooks()">
        <i class="fas fa-sync me-2"></i>Refresh
    </button>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Webhook Configuration -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-cog me-2"></i>Webhook Configuration</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Endpoint URL</h6>
                        <div class="input-group">
                            <input type="text" class="form-control" 
                                   value="{{ $webhookEndpoint ?: 'Not configured' }}" 
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="copyToClipboard('{{ $webhookEndpoint }}')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">Configure this URL in your Stripe dashboard</small>
                    </div>
                    <div class="col-md-6">
                        <h6>Webhook Secret</h6>
                        <div class="input-group">
                            <input type="password" class="form-control" 
                                   value="{{ $webhookSecret ? '••••••••••••••••' : 'Not configured' }}" 
                                   readonly>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="toggleSecret()">
                                <i class="fas fa-eye" id="secret-icon"></i>
                            </button>
                        </div>
                        <small class="text-muted">Keep this secret secure</small>
                    </div>
                </div>
                
                @if(!$webhookEndpoint || !$webhookSecret)
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Configuration Required:</strong> 
                    Please configure your webhook endpoint and secret in the config file.
                </div>
                @endif
            </div>
        </div>

        <!-- Webhook Testing -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-flask me-2"></i>Test Webhook</h5>
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

                <form action="{{ route('stripe-manager.webhooks.test') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_type" class="form-label">Event Type *</label>
                                <select class="form-select @error('event_type') is-invalid @enderror" 
                                        id="event_type" 
                                        name="event_type" 
                                        required
                                        onchange="updateTestForm()">
                                    <option value="">Select Event Type</option>
                                    <option value="invoice.payment_succeeded" {{ old('event_type') == 'invoice.payment_succeeded' ? 'selected' : '' }}>
                                        Invoice Payment Succeeded
                                    </option>
                                    <option value="invoice.payment_failed" {{ old('event_type') == 'invoice.payment_failed' ? 'selected' : '' }}>
                                        Invoice Payment Failed
                                    </option>
                                    <option value="customer.subscription.created" {{ old('event_type') == 'customer.subscription.created' ? 'selected' : '' }}>
                                        Subscription Created
                                    </option>
                                    <option value="customer.subscription.updated" {{ old('event_type') == 'customer.subscription.updated' ? 'selected' : '' }}>
                                        Subscription Updated
                                    </option>
                                    <option value="customer.subscription.deleted" {{ old('event_type') == 'customer.subscription.deleted' ? 'selected' : '' }}>
                                        Subscription Deleted
                                    </option>
                                    <option value="customer.subscription.trial_will_end" {{ old('event_type') == 'customer.subscription.trial_will_end' ? 'selected' : '' }}>
                                        Trial Will End
                                    </option>
                                </select>
                                @error('event_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Test Data (Optional)</label>
                                <textarea class="form-control" 
                                          id="test_data" 
                                          name="test_data" 
                                          rows="3" 
                                          placeholder='{"amount_paid": 1999, "currency": "usd"}'
                                          style="font-family: monospace; font-size: 12px;"></textarea>
                                <small class="text-muted">JSON format for custom test data</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info" id="event-description" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        <span id="description-text"></span>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="test-btn">
                            <i class="fas fa-play me-2"></i>Send Test Webhook
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Webhook Events -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history me-2"></i>Recent Webhook Events</h5>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-secondary" onclick="filterEvents('all')">All</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="filterEvents('invoice')">Invoices</button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="filterEvents('subscription')">Subscriptions</button>
                </div>
            </div>
            <div class="card-body">
                <div id="webhook-events">
                    @if(count($recentEvents) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Event Type</th>
                                        <th>Event ID</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentEvents as $event)
                                        <tr>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ str_replace('.', ' ', $event['type']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <code class="small">{{ $event['id'] }}</code>
                                            </td>
                                            <td>{{ $event['date'] }}</td>
                                            <td>
                                                <span class="badge bg-success">{{ $event['status'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No webhook events found</p>
                            <small class="text-muted">Events will appear here when webhooks are received</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Webhook Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-chart-bar me-2"></i>Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h3 class="text-primary">{{ $stats['total_events'] }}</h3>
                        <small class="text-muted">Total Events</small>
                    </div>
                    <div class="col-6">
                        <h3 class="text-success">{{ $stats['recent_activity'] }}</h3>
                        <small class="text-muted">Last 24h</small>
                    </div>
                </div>
                
                @if(count($stats['events_by_type']) > 0)
                <hr>
                <h6>Events by Type</h6>
                @foreach($stats['events_by_type'] as $type => $count)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small">{{ str_replace('.', ' ', $type) }}</span>
                        <span class="badge bg-secondary">{{ $count }}</span>
                    </div>
                @endforeach
                @endif
            </div>
        </div>

        <!-- Webhook Help -->
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-question-circle me-2"></i>Webhook Help</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Endpoint:</strong> Configure in Stripe Dashboard
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Secret:</strong> Add to your .env file
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Testing:</strong> Use test events to verify setup
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success me-2"></i>
                        <strong>Logs:</strong> Check Laravel logs for details
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function updateTestForm() {
    const eventType = document.getElementById('event_type').value;
    const description = document.getElementById('event-description');
    const descriptionText = document.getElementById('description-text');
    const testBtn = document.getElementById('test-btn');
    
    const descriptions = {
        'invoice.payment_succeeded': 'This event is triggered when a payment for an invoice succeeds.',
        'invoice.payment_failed': 'This event is triggered when a payment for an invoice fails.',
        'customer.subscription.created': 'This event is triggered when a new subscription is created.',
        'customer.subscription.updated': 'This event is triggered when a subscription is updated.',
        'customer.subscription.deleted': 'This event is triggered when a subscription is cancelled.',
        'customer.subscription.trial_will_end': 'This event is triggered 3 days before a trial ends.'
    };
    
    if (eventType && descriptions[eventType]) {
        descriptionText.textContent = descriptions[eventType];
        description.style.display = 'block';
        testBtn.innerHTML = '<i class="fas fa-play me-2"></i>Send Test ' + eventType.split('.').pop().replace('_', ' ').toUpperCase();
    } else {
        description.style.display = 'none';
        testBtn.innerHTML = '<i class="fas fa-play me-2"></i>Send Test Webhook';
    }
}

function filterEvents(type) {
    // This would be implemented with AJAX in a real application
    console.log('Filtering events by type:', type);
}

function refreshWebhooks() {
    location.reload();
}

function copyToClipboard(text) {
    if (text && text !== 'Not configured') {
        navigator.clipboard.writeText(text).then(function() {
            // Show success message
            const btn = event.target.closest('button');
            const originalIcon = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(() => {
                btn.innerHTML = originalIcon;
            }, 2000);
        });
    }
}

function toggleSecret() {
    const input = document.querySelector('input[type="password"]');
    const icon = document.getElementById('secret-icon');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateTestForm();
});
</script>
@endsection
