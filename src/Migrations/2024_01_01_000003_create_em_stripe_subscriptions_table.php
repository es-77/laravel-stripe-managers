<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('em_stripe_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained('em_stripe_products');
            $table->foreignId('pricing_id')->constrained('em_stripe_product_pricing');
            $table->string('stripe_subscription_id')->unique();
            $table->string('stripe_status');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('trial_start')->nullable();
            $table->timestamp('trial_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'stripe_status']);
            $table->index('stripe_subscription_id');
            $table->index(['product_id', 'pricing_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('em_stripe_subscriptions');
    }
};
