<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('em_stripe_subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('em_stripe_subscriptions')->onDelete('cascade');
            $table->string('stripe_invoice_id')->unique();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->integer('amount'); // in cents
            $table->string('currency', 3)->default('usd');
            $table->enum('status', ['paid', 'failed', 'pending', 'canceled'])->default('pending');
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index(['stripe_invoice_id']);
            $table->index(['payment_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('em_stripe_subscription_payments');
    }
};
