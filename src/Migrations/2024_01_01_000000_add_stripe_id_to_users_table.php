<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Check if users table exists
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Check if stripe_id column doesn't exist before adding it
                if (!Schema::hasColumn('users', 'stripe_id')) {
                    $table->string('stripe_id')->nullable()->unique()->after('id');
                }
            });
        }
    }

    public function down()
    {
        // Check if users table exists
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                // Check if stripe_id column exists before dropping it
                if (Schema::hasColumn('users', 'stripe_id')) {
                    $table->dropColumn('stripe_id');
                }
            });
        }
    }
};
