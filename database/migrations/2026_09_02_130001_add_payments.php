<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        DB::table('settings')->insert([
            [
                'key' => 'payments_enabled',
                'value' => '0',
                'description' => 'Charge customers via Stripe when an order is confirmed (1 = on, 0 = off).',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'key' => 'default_payment_amount',
                'value' => null,
                'description' => 'Default charge in USD for confirmed orders when no per-order amount is set. Empty = no charge.',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('payment_amount', 8, 2)->nullable()->after('response_note');
            $table->string('payment_status', 16)->nullable()->after('payment_amount');
            $table->string('stripe_session_id')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('stripe_session_id');

            $table->index(['customer_id', 'payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'payment_status']);
            $table->dropColumn(['payment_amount', 'payment_status', 'stripe_session_id', 'paid_at']);
        });

        Schema::dropIfExists('settings');
    }
};
