<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Stripe Manager')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h4 class="mb-4"><i class="fas fa-credit-card me-2"></i>Stripe Manager</h4>
                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->routeIs('stripe-manager.dashboard') ? 'active' : '' }}" href="{{ route('stripe-manager.dashboard') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a class="nav-link {{ request()->routeIs('stripe-manager.products.*') ? 'active' : '' }}" href="{{ route('stripe-manager.products.index') }}">
                        <i class="fas fa-box me-2"></i>Products
                    </a>
                    <a class="nav-link {{ request()->routeIs('stripe-manager.customers.*') ? 'active' : '' }}" href="{{ route('stripe-manager.customers.index') }}">
                        <i class="fas fa-users me-2"></i>Customers
                    </a>
                    <a class="nav-link {{ request()->routeIs('stripe-manager.subscriptions.*') ? 'active' : '' }}" href="{{ route('stripe-manager.subscriptions.index') }}">
                        <i class="fas fa-refresh me-2"></i>Subscriptions
                    </a>
                    <a class="nav-link {{ request()->routeIs('stripe-manager.webhooks.*') ? 'active' : '' }}" href="{{ route('stripe-manager.webhooks.index') }}">
                        <i class="fas fa-webhook me-2"></i>Webhooks
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://js.stripe.com/v3"></script>
    <script>
        window.STRIPE_PUBLISHABLE_KEY = "{{ config('stripe.key') ?: config('cashier.key') }}";
        
        // Ensure Stripe is loaded before any page scripts run
        window.addEventListener('load', function() {
            if (typeof Stripe === 'undefined') {
                console.error('Failed to load Stripe.js. Please check your internet connection.');
                // Try to reload Stripe script
                const script = document.createElement('script');
                script.src = 'https://js.stripe.com/v3';
                script.onload = function() {
                    console.log('Stripe.js loaded successfully on retry');
                };
                script.onerror = function() {
                    console.error('Failed to load Stripe.js on retry');
                };
                document.head.appendChild(script);
            }
        });
    </script>
</body>
</html>
