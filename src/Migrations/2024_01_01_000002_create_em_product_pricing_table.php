<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('em_stripe_product_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('em_stripe_products')->onDelete('cascade');
            $table->string('stripe_price_id')->unique();
            $table->string('nickname')->nullable();
            $table->integer('unit_amount'); // in cents
            $table->string('currency', 3)->default('usd');
            $table->enum('type', ['one_time', 'recurring'])->default('one_time');
            $table->enum('billing_period', ['day', 'week', 'month', 'year'])->nullable();
            $table->integer('billing_period_count')->nullable();
            $table->integer('trial_period_days')->nullable();
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'active']);
            $table->index('stripe_price_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('em_stripe_product_pricing');
    }
};
