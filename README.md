# Laravel Stripe Manager

A comprehensive Laravel package for managing Stripe customers, products, subscriptions, and payments with a complete web interface.

## 🚀 Quick Start

### Installation

1. **Install the package via Composer:**
```bash
composer require emmanuelsaleem/laravel-stripe-manager
```

2. **Install required dependencies:**
```bash
composer require laravel/cashier stripe/stripe-php
```

3. **Publish and run migrations:**
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelStripeManager\StripeManagerServiceProvider" --tag="migrations"
php artisan migrate
```

4. **Publish views (optional):**
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelStripeManager\StripeManagerServiceProvider" --tag="views"
```

5. **Publish configuration:**
```bash
php artisan vendor:publish --provider="EmmanuelSaleem\LaravelStripeManager\StripeManagerServiceProvider" --tag="config"
```


**Note:** The migration will automatically add a `stripe_id` column to your `users` table if it doesn't already exist.


### Configuration

1. **Add Stripe credentials to your `.env` file:**
```env
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
CASHIER_CURRENCY=usd
```

2. **Update your User model to use Cashier and Stripe Manager:**
```php
// In your User model
use Laravel\Cashier\Billable;
use EmmanuelSaleem\LaravelStripeManager\Traits\HasStripeId;

class User extends Authenticatable
{
    use Billable, HasStripeId;
}
```

## 🌐 Web Interface Access

Once installed, you can access the complete web interface at:

### Main Dashboard
```
http://yourdomain.com/stripe-manager
```

### Available Routes
- **Dashboard**: `/stripe-manager` - Overview with statistics
- **Products**: `/stripe-manager/products` - Manage products and pricing
- **Customers**: `/stripe-manager/customers` - Manage customers and payment methods
- **Subscriptions**: `/stripe-manager/subscriptions` - Manage subscriptions
- **Subscriptions Sync**: `/stripe-manager/subscriptions-sync` - Import existing Stripe subscriptions
- **Stripe Testing**: `/stripe-manager/testing/stripe` - Inspect Stripe customer data
- **Webhooks**: `/stripe-manager/webhooks` - Webhook management and logs

### Authentication Required
All routes require authentication. Make sure users are logged in before accessing the interface.

## 📋 Features

- ✅ **Customer Management**: Create, update, and manage Stripe customers
- ✅ **Card Storage**: Store and manage customer payment methods locally
- ✅ **Product Management**: Create and manage Stripe products with multiple pricing tiers
- ✅ **Pricing Management**: Assign and manage product pricing with recurring/one-time options
- ✅ **Subscription Management**: Create, update, cancel, and resume subscriptions
- ✅ **No Repeat Trial**: Re-subscribing to the same plan skips free trial and charges immediately
- ✅ **Webhook Handler**: Handle Stripe webhooks for payment events
- ✅ **Web Interface**: Complete UI for managing all Stripe resources
- ✅ **Payment Tracking**: Store and track subscription payments locally
- ✅ **Multiple Payment Methods**: Add, list, set default, and delete cards per customer
- ✅ **Product Ordering**: Drag-and-drop product ordering saved via `display_order`
- ✅ **Subscriptions Sync**: One-click sync of existing Stripe subscriptions into local DB, with skip report
- ✅ **Stripe Testing Panel**: Inspect Stripe customer, subs, invoices, PMs, next invoice, and recent charges
- ✅ **Polished UI**: Bootstrap 5 styling with a cohesive dark theme and improved pagination

## 🔌 API (Optional)

The package exposes a minimal REST API for integrating your frontend or other services. The base path and middleware are configurable.

### API Documentation

Explore and test the REST API using the Postman documentation:

- Postman Docs: [https://documenter.getpostman.com/view/21481838/2sB3QJNAb4](https://documenter.getpostman.com/view/21481838/2sB3QJNAb4)

### Configure

In `config/stripe-manager.php`:

```php
'api_routes' => [
    'prefix' => 'api/stripe-manager',
    'middleware' => ['api'], // e.g. ['api','auth:sanctum']
],
```

Base URL: `{APP_URL}/{prefix}` (default: `/api/stripe-manager`)

### Endpoints

1) GET {prefix}/plans
- Returns active products with active pricing from local DB (not Stripe).

Response
```json
{
  "data": [
    {
      "id": 1,
      "name": "Pro",
      "description": "...",
      "stripe_product_id": "prod_...",
      "pricing": [
        {
          "id": 10,
          "stripe_price_id": "price_...",
          "nickname": "Monthly",
          "unit_amount": 1999,
          "currency": "usd",
          "type": "recurring",
          "billing_period": "month",
          "billing_period_count": 1,
          "trial_period_days": 14
        }
      ]
    }
  ]
}
```

2) GET {prefix}/users/{userId}/subscription
- Returns latest subscription summary for a user (local DB), including next billing date and amount when available.

Response
```json
{
  "data": {
    "subscription_id": 5,
    "stripe_subscription_id": "sub_...",
    "status": "active",
    "product": "Pro",
    "price": {
      "nickname": "Monthly",
      "unit_amount": 1999,
      "currency": "usd",
      "billing_period": "month",
      "billing_period_count": 1
    },
    "current_period_start": "2025-10-01 12:00:00",
    "current_period_end": "2025-11-01 12:00:00",
    "next_billing_at": "2025-11-01 12:00:00",
    "next_billing_amount": 1999
  }
}
```

3) POST {prefix}/select-subscription-plan
- Create subscription for user with optional payment method.

Body
```json
{
  "user_id": 123,
  "pricing_id": 10,
  "payment_method": "pm_123" // optional
}
```
Response
```json
{ "data": { "id": 5 } }
```

Behavior
- No repeat trial: if the user previously subscribed to the same pricing, the trial is skipped and the user is charged immediately.

4) GET {prefix}/trial-info?user_id=123
- Returns latest trial info (if any) for the user.

5) DELETE {prefix}/cancel-subscription-plan
- Cancels the current subscription. Optional `immediately=true` charges behavior is respected by service.

Body
```json
{ "user_id": 123, "immediately": false }
```

6) GET {prefix}/user-payment-methods?user_id=123
- Lists card payment methods from Stripe for the user.

7) POST {prefix}/save-stripe-id
- Saves a known Stripe customer id on the user.

Body
```json
{ "user_id": 123, "stripe_id": "cus_..." }
```

8) POST {prefix}/set-default-payment-method
- Sets the user’s default payment method on Stripe.

Body
```json
{ "user_id": 123, "payment_method_id": "pm_..." }
```

### Notes
- Secure these endpoints by adding your preferred auth middleware (e.g. Sanctum) in `api_routes.middleware`.
- Amounts are in the smallest currency unit (e.g., cents).
- Trials are enforced locally and verified with Stripe when possible to prevent re-trial abuse.

## 💻 Programmatic Usage

### Customer Service
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

### Product Service
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

### Subscription Service
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
```

## 🔗 Webhook Setup

1. **Configure webhook endpoint in Stripe Dashboard:**
   - URL: `https://yourdomain.com/stripe-manager/webhooks/handle`
   - Events to send:
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `customer.subscription.trial_will_end`

2. **The webhook automatically handles:**
   - Payment success/failure tracking
   - Subscription status updates
   - Local database synchronization

## 🗄️ Database Schema

The package creates the following tables:
- `em_stripe_products` - Stores Stripe products
- `em_product_pricing` - Stores product pricing information
- `em_stripe_subscriptions` - Stores subscription data
- `em_subscription_payments` - Tracks payment history
- `em_stripe_cards` - Stores customer payment methods
  
Additional columns:
- `em_stripe_products.display_order` (uint, indexed) - for custom ordering

## 🧪 Testing

```bash
# Run package tests
vendor/bin/phpunit packages/emmanuelsaleem/laravel-stripe-manager/tests
```

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### Development Setup

1. **Fork the repository**
2. **Clone your fork:**
```bash
git clone https://github.com/yourusername/laravel-stripe-manager.git
cd laravel-stripe-manager
```

3. **Install dependencies:**
```bash
composer install
```

4. **Create a feature branch:**
```bash
git checkout -b feature/your-feature-name
```

5. **Make your changes and add tests**

6. **Run tests:**
```bash
vendor/bin/phpunit
```

7. **Commit your changes:**
```bash
git commit -m "Add your feature description"
```

8. **Push to your fork:**
```bash
git push origin feature/your-feature-name
```

9. **Create a Pull Request**

### Contribution Guidelines

- Follow PSR-12 coding standards
- Add tests for new features
- Update documentation as needed
- Ensure all tests pass
- Use meaningful commit messages

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

For support, please:
- Create an issue on the [GitHub repository](https://github.com/emmanuelsaleem/laravel-stripe-manager/issues)
- Contact [emmanuelsaleem098765@gmail.com](mailto:emmanuelsaleem098765@gmail.com)
- Connect on [LinkedIn](http://linkedin.com/in/es77)

## ⚙️ Advanced Configuration

Optional limits for Stripe API fetches (used by the testing panel):

```env
# Optional overrides (defaults shown)
STRIPE_LIST_LIMIT_SUBSCRIPTIONS=10
STRIPE_LIST_LIMIT_INVOICES=10
STRIPE_LIST_LIMIT_CHARGES=8
```

These map to `config/stripe-manager.php` under `stripe.limits`.

## 🎛 Usage Tips

- Customer create page supports search + pagination for large user sets
- Manage multiple cards on a customer’s detail page (add, default, delete)
- Drag-and-drop products on the products list to reorder
- Run Subscriptions Sync to import historical data; skipped subscriptions (no matching local user) are shown with reasons

## 📝 Changelog

### v1.0.0
- Initial release
- Complete Stripe customer management
- Product and pricing management
- Subscription lifecycle management
- Webhook handling
- Payment tracking
- Web interface
