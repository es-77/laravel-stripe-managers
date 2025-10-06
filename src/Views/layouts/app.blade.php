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
        :root{
            --sm-bg: #0d1117;
            --sm-surface: #161b22;
            --sm-muted: #8b949e;
            --sm-border: #30363d;
            --sm-primary: #2f81f7;
            --sm-primary-dark: #1f6feb;
        }
        .sidebar {
            min-height: 100vh;
            background-color: var(--sm-surface);
            border-right: 1px solid var(--sm-border);
            color: #c9d1d9;
        }
        .main-content {
            padding: 24px;
            background: var(--sm-bg);
            color: #c9d1d9;
        }
        .card {
            background: var(--sm-surface);
            border: 1px solid var(--sm-border);
            color: #c9d1d9;
        }
        /* Polished UI helpers */
        .section-title {
            font-weight: 600;
            letter-spacing: .2px;
        }
        .pm-card-brand {
            width: 42px; height: 42px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: #eef2f7; color: #44566c;
        }
        .pm-card-item:hover { background: #f7f9fc; }
        .badge-soft {
            background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;
        }
        .list-actions .btn { min-width: 90px; }
        .nav .nav-link{ color: #c9d1d9; }
        .nav .nav-link.active{ color: #fff; font-weight: 600; }
        .btn-primary{ background: var(--sm-primary); border-color: var(--sm-primary); }
        .btn-primary:hover{ background: var(--sm-primary-dark); border-color: var(--sm-primary-dark); }
        .form-control, .form-select{
            background: #0b1220; border-color: var(--sm-border); color: #e6edf3;
        }
        .form-control:focus, .form-select:focus{ box-shadow: none; border-color: var(--sm-primary); }
        .table{ color: #c9d1d9; }
        .table thead th{ border-bottom-color: var(--sm-border); }
        .table td, .table th{ border-color: var(--sm-border); }
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
                    <a class="nav-link {{ request()->routeIs('stripe-manager.testing.stripe') ? 'active' : '' }}" href="{{ route('stripe-manager.testing.stripe') }}">
                        <i class="fas fa-flask me-2"></i>Stripe Testing
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                @yield('content')
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
