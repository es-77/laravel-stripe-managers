<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('em_stripe_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('stripe_payment_method_id')->unique();
            $table->string('brand');
            $table->string('last_four', 4);
            $table->integer('exp_month');
            $table->integer('exp_year');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
            $table->index('stripe_payment_method_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('em_stripe_cards');
    }
};
