<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/customers/test.blade.php -->
@extends('stripe-manager::layouts.app')

@section('title', 'Stripe Testing')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-flask me-2"></i>Stripe Testing</h2>
    <a href="{{ route('stripe-manager.dashboard') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
    </div>

<div class="card mb-4">
    <div class="card-header section-title">Lookup</div>
    <div class="card-body">
        <form method="GET" action="{{ route('stripe-manager.testing.stripe') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Local User ID (optional)</label>
                <input type="number" name="user_id" class="form-control" value="{{ request('user_id') }}" placeholder="e.g. 123" />
            </div>
            <div class="col-md-6">
                <label class="form-label">Stripe Customer ID (optional)</label>
                <input type="text" name="stripe_id" class="form-control" value="{{ request('stripe_id') }}" placeholder="e.g. cus_123" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Search
                </button>
            </div>
            <div class="col-12">
                <small class="text-muted">Provide either Local User ID or Stripe Customer ID (at least one required).</small>
            </div>
        </form>
    </div>
 </div>

@if(isset($error) && $error)
    <div class="alert alert-danger">{{ $error }}</div>
@endif

@if(isset($data) && $data)
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header section-title">Customer</div>
            <div class="card-body">
                @if($data['user'])
                    <div class="mb-2"><strong>Local User:</strong> {{ $data['user']->name }} (ID {{ $data['user']->id }})</div>
                @endif
                <div><strong>Stripe ID:</strong> <code>{{ $data['customer']->id }}</code></div>
                <div><strong>Name:</strong> {{ $data['customer']->name ?? '-' }}</div>
                <div><strong>Email:</strong> {{ $data['customer']->email ?? '-' }}</div>
                <div><strong>Default PM:</strong> {{ optional($data['customer']->invoice_settings)->default_payment_method ?? '-' }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header section-title">Payment Methods</div>
            <div class="card-body">
                @forelse($data['paymentMethods']->data as $pm)
                    <div class="mb-2">
                        <strong>•••• {{ $pm->card->last4 }}</strong> — {{ strtoupper($pm->card->brand) }} ({{ $pm->card->exp_month }}/{{ $pm->card->exp_year }})
                    </div>
                @empty
                    <div class="text-muted">None</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header section-title">Subscriptions</div>
            <div class="card-body">
                @forelse($data['subscriptions']->data as $sub)
                    <div class="border rounded p-2 mb-2">
                        <div><strong>ID:</strong> <code>{{ $sub->id }}</code> — <strong>Status:</strong> {{ $sub->status }}</div>
                        <div><strong>Plan/Price:</strong> {{ optional($sub->items->data[0]->price)->id }} ({{ optional($sub->items->data[0]->price)->nickname ?? '-' }})</div>
                        <div><strong>Billing cycle:</strong> {{ optional($sub->items->data[0]->price->recurring)->interval ?? '-' }} / {{ optional($sub->items->data[0]->price->recurring)->interval_count ?? 1 }}</div>
                        <div><strong>Current period:</strong> {{ $sub->current_period_start ? date('M j, Y', $sub->current_period_start) : '-' }} → {{ $sub->current_period_end ? date('M j, Y', $sub->current_period_end) : '-' }}</div>
                        <div><strong>Trial ends:</strong> {{ $sub->trial_end ? date('M j, Y', $sub->trial_end) : '-' }}</div>
                        <div><strong>Next billing:</strong> {{ $sub->cancel_at_period_end ? 'Will cancel at period end' : ($sub->current_period_end ? date('M j, Y', $sub->current_period_end) : '-') }}</div>
                        @if(isset($sub->latest_invoice))
                            <div><strong>Latest invoice:</strong> <code>{{ $sub->latest_invoice->id }}</code> — {{ $sub->latest_invoice->status }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No subscriptions</div>
                @endforelse
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header section-title">Invoices</div>
            <div class="card-body">
                @forelse($data['invoices']->data as $inv)
                    <div class="border rounded p-2 mb-2">
                        <div><strong>ID:</strong> <code>{{ $inv->id }}</code> — {{ $inv->status }} — Amount: {{ number_format($inv->amount_due/100, 2) }} {{ strtoupper($inv->currency) }}</div>
                        <div><strong>Created:</strong> {{ date('M j, Y', $inv->created) }}</div>
                        <div><strong>Due:</strong> {{ $inv->due_date ? date('M j, Y', $inv->due_date) : '-' }}</div>
                        @if($inv->next_payment_attempt)
                            <div><strong>Next payment attempt:</strong> {{ date('M j, Y g:i A', $inv->next_payment_attempt) }}</div>
                        @endif
                    </div>
                @empty
                    <div class="text-muted">No invoices</div>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header section-title">Recent Transactions</div>
            <div class="card-body">
                @forelse($data['charges']->data as $charge)
                    <div class="border rounded p-2 mb-2">
                        <div><strong>ID:</strong> <code>{{ $charge->id }}</code> — {{ strtoupper($charge->status) }} — {{ number_format($charge->amount/100, 2) }} {{ strtoupper($charge->currency) }}</div>
                        <div><strong>Created:</strong> {{ date('M j, Y g:i A', $charge->created) }}</div>
                        <div><strong>Card:</strong> •••• {{ optional($charge->payment_method_details->card)->last4 }} — {{ strtoupper(optional($charge->payment_method_details->card)->brand) }}</div>
                    </div>
                @empty
                    <div class="text-muted">No recent transactions</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif
@endsection

