<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('paypal_order_id')->nullable()->unique()->after('payment_status');
            $table->string('transaction_id')->nullable()->index()->after('paypal_order_id');
            $table->string('payer_email')->nullable()->after('transaction_id');
            $table->json('gateway_response')->nullable()->after('payer_email');
            $table->timestamp('paid_at')->nullable()->after('gateway_response');
            $table->boolean('mail_sent')->default(false)->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['paypal_order_id']);
            $table->dropIndex(['transaction_id']);
            $table->dropColumn([
                'paypal_order_id',
                'transaction_id',
                'payer_email',
                'gateway_response',
                'paid_at',
                'mail_sent',
            ]);
        });
    }
};
