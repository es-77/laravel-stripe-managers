# Laravel Stripe Manager

A comprehensive Laravel package for managing Stripe customers, products, subscriptions, and payments with a complete web interface.

## Features

- ✅ **Customer Management**: Create, update, and manage Stripe customers
- ✅ **Card Storage**: Store and manage customer payment methods locally
- ✅ **Product Management**: Create and manage Stripe products with multiple pricing tiers
- ✅ **Pricing Management**: Assign and manage product pricing with recurring/one-time options
- ✅ **Subscription Management**: Create, update, cancel, and resume subscriptions
- ✅ **Webhook Handler**: Handle Stripe webhooks for payment received/cancelled events
- ✅ **Web Interface**: Complete UI for managing all Stripe resources
- ✅ **Payment Tracking**: Store and track subscription payments locally

## Installation


1. **Install via Composer** (if published):
```bash
composer require emmanuelsaleem/laravel-stripe-manager
```

2. **Or add to your project manually**:
Copy the package to your `packages/emmanuelsaleem/laravel-stripe-manager` directory.

3. **Add to composer.json** (for local development):
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/emmanuelsaleem/laravel-stripe-manager"
        }
    ],
    "require": {
        "emmanuelsaleem/laravel-stripe-manager": "*"
    }
}
```

4. **Install dependencies**:
```bash
composer require laravel/cashier stripe/stripe-php
```

5. **Publish configuration**:
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelStripeManager\StripeManagerServiceProvider" --tag="config"
```

6. **Publish and run migrations**:
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelStripeManager\StripeManagerServiceProvider" --tag="migrations"
php artisan migrate
```

7. **Publish views** (optional):
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelStripeManager\StripeManagerServiceProvider" --tag="views"
```

## Configuration

Add your Stripe credentials to `.env`:

```env
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
CASHIER_CURRENCY=usd
```

Update `config/stripe-manager.php`:

```php
return [
    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],
    
    'currency' => env('CASHIER_CURRENCY', 'usd'),
    'trial_days' => 14,
    
    'routes' => [
        'prefix' => 'stripe-manager',
        'middleware' => ['web', 'auth'],
    ]
];
```

## Usage

### Accessing the Web Interface

Visit `/stripe-manager` to access the complete web interface for managing:
- Customers
- Products
- Subscriptions
- Payments

### Using Services Programmatically

#### Customer Service

```php
use EmmanuelSaleem\LaravelStripeManager\Services\CustomerService;

$customerService = app(CustomerService::class);

// Create a customer
$user = User::find(1);
$customer = $customerService->createCustomer($user, [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Store a payment method
$card = $customerService->storePaymentMethod($user, $paymentMethodId, $setAsDefault = true);

// Create setup intent for card collection
$setupIntent = $customerService->createSetupIntent($user);
```

#### Product Service

```php
use EmmanuelSaleem\LaravelStripeManager\Services\ProductService;

$productService = app(ProductService::class);

// Create a product
$product = $productService->createProduct([
    'name' => 'Premium Subscription',
    'description' => 'Access to premium features',
    'active' => true
]);

// Create pricing for the product
$pricing = $productService->createProductPrice($product, [
    'unit_amount' => 1999, // $19.99 in cents
    'currency' => 'usd',
    'recurring' => [
        'interval' => 'month',
        'interval_count' => 1
    ],
    'nickname' => 'Monthly Premium'
]);
```

#### Subscription Service

```php
use EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService;

$subscriptionService = app(SubscriptionService::class);

// Create a subscription
$subscription = $subscriptionService->createSubscription($user, $pricing, [
    'trial_days' => 14,
    'payment_method' => $paymentMethodId
]);

// Cancel a subscription
$subscriptionService->cancelSubscription($subscription, $immediately = false);

// Resume a subscription
$subscriptionService->resumeSubscription($subscription);

// Change subscription plan
$subscriptionService->changePlan($subscription, $newPricing);
```

### Working with Models

#### StripeProduct

```php
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProduct;

$product = StripeProduct::with('pricing', 'subscriptions')->find(1);
```

#### StripeProductPricing

```php
use EmmanuelSaleem\LaravelStripeManager\Models\StripeProductPricing;

$pricing = StripeProductPricing::with('product', 'subscriptions')->find(1);
echo $pricing->formatted_price; // "USD 19.99 / month"
```

#### StripeSubscription

```php
use EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription;

$subscription = StripeSubscription::with('user', 'product', 'pricing', 'payments')->find(1);

// Check subscription status
if ($subscription->isActive()) {
    // Subscription is active
}

if ($subscription->onTrial()) {
    // Subscription is on trial
}

if ($subscription->cancelled()) {
    // Subscription is cancelled
}
```

#### StripeCard

```php
use EmmanuelSaleem\LaravelStripeManager\Models\StripeCard;

$cards = StripeCard::where('user_id', $userId)->get();

foreach ($cards as $card) {
    echo $card->masked_number; // "**** **** **** 1234"
    echo $card->formatted_expiry; // "12/2025"
    echo $card->brand_icon; // "fab fa-cc-visa"
}
```

## Webhook Setup

1. **Configure webhook endpoint in Stripe Dashboard**:
   - URL: `https://yourdomain.com/stripe/webhook`
   - Events to send:
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `customer.subscription.trial_will_end`

2. **The webhook automatically handles**:
   - Payment success/failure tracking
   - Subscription status updates
   - Local database synchronization

## Database Schema

The package creates the following tables:

- `stripe_products`: Stores Stripe products
- `stripe_product_pricing`: Stores product pricing information
- `stripe_subscriptions`: Stores subscription data
- `stripe_subscription_payments`: Tracks payment history
- `stripe_cards`: Stores customer payment methods

## Routes

### Web Interface Routes (requires authentication):
- `GET /stripe-manager` - Dashboard
- `GET /stripe-manager/customers` - Customer list
- `GET /stripe-manager/products` - Product list
- `GET /stripe-manager/subscriptions` - Subscription list

### API Routes:
- `POST /stripe/webhook` - Stripe webhook handler

## Events and Logging

The package logs all important events and errors. Check your Laravel logs for:
- Customer creation/updates
- Payment method changes
- Subscription lifecycle events
- Webhook processing
- Error handling

## Testing

```bash
# Run package tests
vendor/bin/phpunit packages/emmanuelsaleem/laravel-stripe-manager/tests
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For support, please create an issue on the GitHub repository or contact [emmanuelsaleem098765@gmail.com](mailto:emmanuel.saleem@example.com).

## Changelog

### v1.0.0
- Initial release
- Complete Stripe customer management
- Product and pricing management
- Subscription lifecycle management
- Webhook handling
- Payment tracking
- Web interface
