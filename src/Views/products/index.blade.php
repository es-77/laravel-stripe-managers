
<!-- packages/emmanuelsaleem/laravel-stripe-manager/src/Views/products/index.blade.php -->
@extends('stripe-manager::layouts.app')

@section('title', 'Products')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-box me-2"></i>Products</h2>
    <div>
        <a href="{{ route('stripe-manager.products.sync') }}" class="btn btn-info me-2" 
           onclick="return confirm('This will sync all products from Stripe. Continue?')">
            <i class="fas fa-sync me-2"></i>Sync from Stripe
        </a>
        <a href="{{ route('stripe-manager.products.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create Product
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($products->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover" id="products-table">
                    <thead>
                        <tr>
                            <th style="width:42px"></th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Pricing</th>
                            <th>Status</th>
                            <th>Subscriptions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr data-id="{{ $product->id }}">
                                <td class="text-muted"><i class="fas fa-grip-vertical"></i></td>
                                <td>
                                    <strong>{{ $product->name }}</strong><br>
                                    <small class="text-muted">{{ $product->stripe_id }}</small>
                                </td>
                                <td>
                                    {{ $product->description ? Str::limit($product->description, 50) : '-' }}
                                </td>
                                <td>
                                    @if($product->pricing->count() > 0)
                                        @foreach($product->pricing as $price)
                                            <span class="badge bg-info me-1">
                                                {{ $price->getFormattedPriceAttribute() }}
                                            </span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No pricing</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="status-badge status-{{ $product->active ? 'active' : 'inactive' }}">
                                        {{ $product->active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $product->subscriptions_count ?? 0 }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('stripe-manager.products.show', $product) }}" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('stripe-manager.products.edit', $product) }}" 
                                           class="btn btn-outline-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{ $products->links() }}
        @else
            <div class="text-center py-5">
                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                <h5>No products found</h5>
                <p class="text-muted">Create your first product to get started.</p>
                <a href="{{ route('stripe-manager.products.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Product
                </a>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('products-table');
    if (!table) return;

    let draggingRow = null;
    table.querySelectorAll('tbody tr').forEach(row => {
        row.draggable = true;
        row.addEventListener('dragstart', () => draggingRow = row);
        row.addEventListener('dragover', e => {
            e.preventDefault();
            const target = e.currentTarget;
            const bounding = target.getBoundingClientRect();
            const offset = bounding.y + (bounding.height / 2);
            const parent = target.parentNode;
            if (e.clientY - offset > 0) parent.insertBefore(draggingRow, target.nextSibling);
            else parent.insertBefore(draggingRow, target);
        });
    });

    table.addEventListener('drop', async () => {
        const orders = Array.from(table.querySelectorAll('tbody tr')).map((tr, index) => ({ id: tr.dataset.id, order: (table.rows.length - index) }));
        try {
            const resp = await fetch("{{ route('stripe-manager.products.reorder') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') },
                body: JSON.stringify({ orders })
            });
            if (!resp.ok) throw new Error('Failed to save order');
        } catch (e) {
            alert('Could not save new order.');
        }
    });
});
</script>
@endsection

