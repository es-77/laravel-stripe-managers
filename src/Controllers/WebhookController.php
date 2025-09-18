<?php

namespace EmmanuelSaleem\LaravelStripeManager\Controllers;

use App\Http\Controllers\Controller;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscription;
use EmmanuelSaleem\LaravelStripeManager\Models\StripeSubscriptionPayment;
use EmmanuelSaleem\LaravelStripeManager\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Carbon\Carbon;

class WebhookController extends Controller
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        Stripe::setApiKey(config('stripe-manager.stripe.secret'));
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Handle Stripe webhook
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('stripe-manager.stripe.webhook.secret');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Invalid webhook payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Invalid webhook signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Log the webhook event
        Log::info('Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id
        ]);

        // Handle the event
        try {
            switch ($event->type) {
                case 'invoice.payment_succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;

                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($event->data->object);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;

                case 'customer.subscription.trial_will_end':
                    $this->handleTrialWillEnd($event->data->object);
                    break;

                case 'invoice.created':
                    $this->handleInvoiceCreated($event->data->object);
                    break;

                case 'invoice.finalized':
                    $this->handleInvoiceFinalized($event->data->object);
                    break;

                case 'payment_method.attached':
                    $this->handlePaymentMethodAttached($event->data->object);
                    break;

                case 'payment_method.detached':
                    $this->handlePaymentMethodDetached($event->data->object);
                    break;

                default:
                    Log::info('Unhandled webhook event type', ['type' => $event->type]);
            }

            return response('Webhook handled', 200);
        } catch (\Exception $e) {
            Log::error('Error handling webhook', [
                'type' => $event->type,
                'id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return response('Error handling webhook', 500);
        }
    }

    /**
     * Handle successful payment
     */
    protected function handlePaymentSucceeded($invoice)
    {
        try {
            if ($invoice->subscription) {
                $subscription = StripeSubscription::where('stripe_subscription_id', $invoice->subscription)->first();

                if ($subscription) {
                    // Record the payment
                    StripeSubscriptionPayment::create([
                        'subscription_id' => $subscription->id,
                        'stripe_invoice_id' => $invoice->id,
                        'stripe_payment_intent_id' => $invoice->payment_intent,
                        'amount' => $invoice->amount_paid,
                        'currency' => $invoice->currency,
                        'status' => 'paid',
                        'payment_date' => Carbon::createFromTimestamp($invoice->status_transitions->paid_at),
                        'period_start' => Carbon::createFromTimestamp($invoice->period_start),
                        'period_end' => Carbon::createFromTimestamp($invoice->period_end),
                        'metadata' => [
                            'invoice_number' => $invoice->number,
                            'hosted_invoice_url' => $invoice->hosted_invoice_url,
                            'invoice_pdf' => $invoice->invoice_pdf,
                        ]
                    ]);

                    Log::info('Payment recorded for subscription', [
                        'subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->amount_paid
                    ]);

                    // Sync subscription status
                    $this->subscriptionService->syncSubscription($invoice->subscription);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling payment succeeded', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle failed payment
     */
    protected function handlePaymentFailed($invoice)
    {
        try {
            if ($invoice->subscription) {
                $subscription = StripeSubscription::where('stripe_subscription_id', $invoice->subscription)->first();

                if ($subscription) {
                    // Record the failed payment
                    StripeSubscriptionPayment::create([
                        'subscription_id' => $subscription->id,
                        'stripe_invoice_id' => $invoice->id,
                        'stripe_payment_intent_id' => $invoice->payment_intent,
                        'amount' => $invoice->amount_due,
                        'currency' => $invoice->currency,
                        'status' => 'failed',
                        'payment_date' => null,
                        'period_start' => Carbon::createFromTimestamp($invoice->period_start),
                        'period_end' => Carbon::createFromTimestamp($invoice->period_end),
                        'metadata' => [
                            'invoice_number' => $invoice->number,
                            'hosted_invoice_url' => $invoice->hosted_invoice_url,
                            'attempt_count' => $invoice->attempt_count,
                            'next_payment_attempt' => $invoice->next_payment_attempt ? Carbon::createFromTimestamp($invoice->next_payment_attempt) : null,
                        ]
                    ]);

                    Log::warning('Payment failed for subscription', [
                        'subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                        'amount' => $invoice->amount_due,
                        'attempt_count' => $invoice->attempt_count
                    ]);

                    // Sync subscription status
                    $this->subscriptionService->syncSubscription($invoice->subscription);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling payment failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle subscription created
     */
    protected function handleSubscriptionCreated($subscription)
    {
        try {
            // Sync the subscription to local database
            $this->subscriptionService->syncSubscription($subscription->id);

            Log::info('Subscription created via webhook', [
                'stripe_subscription_id' => $subscription->id,
                'customer' => $subscription->customer,
                'status' => $subscription->status
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription created', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle subscription updated
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        try {
            // Sync the subscription to local database
            $this->subscriptionService->syncSubscription($subscription->id);

            Log::info('Subscription updated via webhook', [
                'stripe_subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'cancel_at_period_end' => $subscription->cancel_at_period_end
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription updated', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle subscription deleted/cancelled
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        try {
            $localSubscription = StripeSubscription::where('stripe_subscription_id', $subscription->id)->first();

            if ($localSubscription) {
                $localSubscription->update([
                    'stripe_status' => 'canceled',
                    'canceled_at' => now(),
                    'ends_at' => now(),
                ]);

                Log::info('Subscription cancelled via webhook', [
                    'subscription_id' => $localSubscription->id,
                    'stripe_subscription_id' => $subscription->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error handling subscription deleted', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle trial will end
     */
    protected function handleTrialWillEnd($subscription)
    {
        try {
            $localSubscription = StripeSubscription::where('stripe_subscription_id', $subscription->id)->first();

            if ($localSubscription) {
                Log::info('Trial will end for subscription', [
                    'subscription_id' => $localSubscription->id,
                    'stripe_subscription_id' => $subscription->id,
                    'trial_end' => Carbon::createFromTimestamp($subscription->trial_end)
                ]);

                // You can add notification logic here
                // e.g., send email to customer about trial ending
            }
        } catch (\Exception $e) {
            Log::error('Error handling trial will end', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle invoice created
     */
    protected function handleInvoiceCreated($invoice)
    {
        try {
            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription,
                'amount' => $invoice->amount_due,
                'status' => $invoice->status
            ]);

            // You can add custom logic here for invoice creation
        } catch (\Exception $e) {
            Log::error('Error handling invoice created', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle invoice finalized
     */
    protected function handleInvoiceFinalized($invoice)
    {
        try {
            Log::info('Invoice finalized', [
                'invoice_id' => $invoice->id,
                'subscription_id' => $invoice->subscription,
                'amount' => $invoice->amount_due,
                'status' => $invoice->status
            ]);

            // You can add custom logic here for invoice finalization
        } catch (\Exception $e) {
            Log::error('Error handling invoice finalized', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle payment method attached
     */
    protected function handlePaymentMethodAttached($paymentMethod)
    {
        try {
            Log::info('Payment method attached', [
                'payment_method_id' => $paymentMethod->id,
                'customer' => $paymentMethod->customer,
                'type' => $paymentMethod->type
            ]);

            // You can add custom logic here for payment method attachment
        } catch (\Exception $e) {
            Log::error('Error handling payment method attached', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle payment method detached
     */
    protected function handlePaymentMethodDetached($paymentMethod)
    {
        try {
            Log::info('Payment method detached', [
                'payment_method_id' => $paymentMethod->id,
                'customer' => $paymentMethod->customer,
                'type' => $paymentMethod->type
            ]);

            // You can add custom logic here for payment method detachment
        } catch (\Exception $e) {
            Log::error('Error handling payment method detached', [
                'payment_method_id' => $paymentMethod->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Show webhook management dashboard
     */
    public function index()
    {
        $webhookEndpoint = config('stripe-manager.stripe.webhook.endpoint');
        $webhookSecret = config('stripe-manager.stripe.webhook.secret');
        
        // Get recent webhook events from logs
        $recentEvents = $this->getRecentWebhookEvents();
        
        // Get webhook statistics
        $stats = $this->getWebhookStats();
        
        return view('stripe-manager::webhooks.index', compact(
            'webhookEndpoint', 
            'webhookSecret', 
            'recentEvents', 
            'stats'
        ));
    }

    /**
     * Test webhook endpoint
     */
    public function test(Request $request)
    {
        $request->validate([
            'event_type' => 'required|string|in:invoice.payment_succeeded,invoice.payment_failed,customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,customer.subscription.trial_will_end',
            'test_data' => 'nullable|array'
        ]);

        try {
            $stripeSecret = config('stripe.secret') ?: config('cashier.secret');
            
            if (!$stripeSecret) {
                return back()->with('error', 'Stripe secret key not configured.');
            }

            $stripe = new \Stripe\StripeClient($stripeSecret);
            
            // Create test event
            $testEvent = $this->createTestEvent($request->event_type, $request->test_data);
            
            // Send test webhook
            $response = $this->sendTestWebhook($testEvent);
            
            if ($response['success']) {
                return back()->with('success', 'Test webhook sent successfully! Check logs for details.');
            } else {
                return back()->with('error', 'Failed to send test webhook: ' . $response['error']);
            }

        } catch (\Exception $e) {
            Log::error('Webhook test error: ' . $e->getMessage());
            return back()->with('error', 'Error testing webhook: ' . $e->getMessage());
        }
    }

    /**
     * Get webhook logs
     */
    public function logs(Request $request)
    {
        $events = $this->getWebhookEvents($request->get('type'), $request->get('limit', 50));
        
        return response()->json([
            'success' => true,
            'events' => $events
        ]);
    }

    /**
     * Get recent webhook events from logs
     */
    protected function getRecentWebhookEvents($limit = 10)
    {
        try {
            // This is a simplified version - in production you might want to store webhook events in database
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return [];
            }

            $events = [];
            $lines = file($logFile);
            $recentLines = array_slice($lines, -1000); // Get last 1000 lines for performance

            foreach ($recentLines as $line) {
                if (strpos($line, 'Stripe webhook received') !== false) {
                    preg_match('/"type":"([^"]+)"/', $line, $typeMatch);
                    preg_match('/"id":"([^"]+)"/', $line, $idMatch);
                    preg_match('/\[([^\]]+)\]/', $line, $dateMatch);
                    
                    if ($typeMatch && $idMatch) {
                        $events[] = [
                            'type' => $typeMatch[1],
                            'id' => $idMatch[1],
                            'date' => $dateMatch[1] ?? 'Unknown',
                            'status' => 'processed'
                        ];
                    }
                }
            }

            return array_slice(array_reverse($events), 0, $limit);
        } catch (\Exception $e) {
            Log::error('Error getting recent webhook events: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get webhook statistics
     */
    protected function getWebhookStats()
    {
        try {
            $events = $this->getRecentWebhookEvents(100);
            
            $stats = [
                'total_events' => count($events),
                'events_by_type' => [],
                'recent_activity' => 0
            ];

            $oneDayAgo = now()->subDay();

            foreach ($events as $event) {
                // Count by type
                if (!isset($stats['events_by_type'][$event['type']])) {
                    $stats['events_by_type'][$event['type']] = 0;
                }
                $stats['events_by_type'][$event['type']]++;

                // Count recent activity
                if (strtotime($event['date']) > $oneDayAgo->timestamp) {
                    $stats['recent_activity']++;
                }
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Error getting webhook stats: ' . $e->getMessage());
            return [
                'total_events' => 0,
                'events_by_type' => [],
                'recent_activity' => 0
            ];
        }
    }

    /**
     * Create test event data
     */
    protected function createTestEvent($eventType, $testData = [])
    {
        $baseEvent = [
            'id' => 'evt_test_' . uniqid(),
            'object' => 'event',
            'api_version' => '2020-08-27',
            'created' => time(),
            'data' => [
                'object' => []
            ],
            'livemode' => false,
            'pending_webhooks' => 1,
            'request' => [
                'id' => 'req_test_' . uniqid(),
                'idempotency_key' => null
            ],
            'type' => $eventType
        ];

        // Add test data based on event type
        switch ($eventType) {
            case 'invoice.payment_succeeded':
                $baseEvent['data']['object'] = array_merge([
                    'id' => 'in_test_' . uniqid(),
                    'object' => 'invoice',
                    'amount_paid' => 999,
                    'currency' => 'usd',
                    'status' => 'paid',
                    'subscription' => 'sub_test_' . uniqid(),
                    'customer' => 'cus_test_' . uniqid(),
                    'payment_intent' => 'pi_test_' . uniqid(),
                    'status_transitions' => [
                        'paid_at' => time()
                    ],
                    'period_start' => time() - 86400,
                    'period_end' => time() + 2592000,
                    'number' => 'TEST-' . rand(1000, 9999),
                    'hosted_invoice_url' => 'https://invoice.stripe.com/i/test',
                    'invoice_pdf' => 'https://pay.stripe.com/invoice/test'
                ], $testData);
                break;

            case 'customer.subscription.created':
                $baseEvent['data']['object'] = array_merge([
                    'id' => 'sub_test_' . uniqid(),
                    'object' => 'subscription',
                    'status' => 'trialing',
                    'customer' => 'cus_test_' . uniqid(),
                    'current_period_start' => time(),
                    'current_period_end' => time() + 2592000,
                    'trial_start' => time(),
                    'trial_end' => time() + 604800,
                    'cancel_at_period_end' => false
                ], $testData);
                break;

            default:
                $baseEvent['data']['object'] = array_merge([
                    'id' => 'test_' . uniqid(),
                    'object' => 'test'
                ], $testData);
        }

        return $baseEvent;
    }

    /**
     * Send test webhook
     */
    protected function sendTestWebhook($event)
    {
        try {
            $webhookEndpoint = config('stripe-manager.stripe.webhook.endpoint');
            
            if (!$webhookEndpoint) {
                return ['success' => false, 'error' => 'Webhook endpoint not configured'];
            }

            // Simulate webhook call
            $payload = json_encode($event);
            $timestamp = time();
            $secret = config('stripe-manager.stripe.webhook.secret');
            
            if (!$secret) {
                return ['success' => false, 'error' => 'Webhook secret not configured'];
            }

            $signedPayload = $timestamp . '.' . $payload;
            $signature = hash_hmac('sha256', $signedPayload, $secret);

            // Log the test webhook
            Log::info('Test webhook sent', [
                'event_type' => $event['type'],
                'event_id' => $event['id'],
                'endpoint' => $webhookEndpoint
            ]);

            return ['success' => true, 'signature' => $signature];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get webhook events (simplified)
     */
    protected function getWebhookEvents($type = null, $limit = 50)
    {
        $events = $this->getRecentWebhookEvents($limit);
        
        if ($type) {
            $events = array_filter($events, function($event) use ($type) {
                return $event['type'] === $type;
            });
        }
        
        return $events;
    }
}
