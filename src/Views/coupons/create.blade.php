@extends('stripe-manager::layouts.app')

@section('title', 'Create Coupon')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-ticket-alt me-2"></i>Create Coupon</h2>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <form action="{{ route('stripe-manager.coupons.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name (optional)</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Duration</label>
                    <select name="duration" class="form-select">
                        <option value="once" {{ old('duration')==='once' ? 'selected' : '' }}>Once</option>
                        <option value="repeating" {{ old('duration')==='repeating' ? 'selected' : '' }}>Repeating</option>
                        <option value="forever" {{ old('duration')==='forever' ? 'selected' : '' }}>Forever</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Percent off (%)</label>
                    <input type="number" name="percent_off" class="form-control" min="1" max="100" value="{{ old('percent_off') }}">
                    <small class="text-muted">Use either percent or amount off.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Amount off (in smallest unit)</label>
                    <input type="number" name="amount_off" class="form-control" min="1" value="{{ old('amount_off') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Currency (for amount off)</label>
                    <input type="text" name="currency" class="form-control" value="{{ old('currency','usd') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Duration in months (if repeating)</label>
                    <input type="number" name="duration_in_months" class="form-control" min="1" max="36" value="{{ old('duration_in_months') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Max redemptions (optional)</label>
                    <input type="number" name="max_redemptions" class="form-control" min="1" value="{{ old('max_redemptions') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Redeem by (optional)</label>
                    <input type="date" name="redeem_by" class="form-control" value="{{ old('redeem_by') }}">
                </div>

                <hr class="my-4">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="create_promo" name="create_promo" value="1" {{ old('create_promo') ? 'checked' : '' }}>
                        <label class="form-check-label" for="create_promo">Also create Promotion Code</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Promotion Code (optional)</label>
                    <input type="text" name="promo_code" class="form-control" value="{{ old('promo_code') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Promo Max Redemptions</label>
                    <input type="number" name="promo_max_redemptions" class="form-control" min="1" value="{{ old('promo_max_redemptions') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Promo Expires At</label>
                    <input type="date" name="promo_expires_at" class="form-control" value="{{ old('promo_expires_at') }}">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create
                    </button>
                </div>
            </div>
        </form>
    </div>
    </div>
@endsection


