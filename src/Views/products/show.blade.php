@extends('stripe-manager::layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-box me-2"></i>{{ $product->name }}</h2>
    <div>
        <a href="{{ route('stripe-manager.products.edit', $product->id) }}" class="btn btn-primary me-2">
            <i class="fas fa-edit me-2"></i>Edit Product
        </a>
        <a href="{{ route('stripe-manager.products.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Products
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Product Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Product Name</h6>
                        <p class="text-muted">{{ $product->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Stripe Product ID</h6>
                        <p class="text-muted"><code>{{ $product->stripe_id }}</code></p>
                    </div>
                </div>
                
                @if($product->description)
                    <div class="mb-3">
                        <h6>Description</h6>
                        <p class="text-muted">{{ $product->description }}</p>
                    </div>
                @endif
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>Status</h6>
                        <span class="badge {{ $product->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $product->active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <h6>Created</h6>
                        <p class="text-muted">{{ $product->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Pricing Plans</h5>
            </div>
            <div class="card-body">
                @if($product->pricing->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Price</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->pricing as $pricing)
                                    <tr>
                                        <td>{{ $pricing->nickname ?: 'Default Plan' }}</td>
                                        <td>{{ $pricing->getFormattedPriceAttribute() }}</td>
                                        <td>
                                            <span class="badge {{ $pricing->type === 'recurring' ? 'bg-info' : 'bg-primary' }}">
                                                {{ ucfirst($pricing->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $pricing->active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $pricing->active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('stripe-manager.products.pricing.edit', $pricing->id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('stripe-manager.products.pricing.destroy', $pricing->id) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to deactivate this pricing plan?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-tag fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No pricing plans yet</p>
                        <a href="{{ route('stripe-manager.products.pricing.create', $product->id) }}" 
                           class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Pricing Plan
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-primary">{{ $product->pricing->count() }}</h4>
                        <small class="text-muted">Pricing Plans</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-success">{{ $product->subscriptions->count() }}</h4>
                        <small class="text-muted">Active Subscriptions</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('stripe-manager.products.pricing.create', $product->id) }}" 
                       class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Pricing Plan
                    </a>
                    <a href="{{ route('stripe-manager.products.edit', $product->id) }}" 
                       class="btn btn-outline-primary">
                        <i class="fas fa-edit me-2"></i>Edit Product
                    </a>
                    <button type="button" class="btn btn-outline-danger" 
                            onclick="confirmDelete('{{ route('stripe-manager.products.destroy', $product->id) }}')">
                        <i class="fas fa-trash me-2"></i>Delete Product
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
