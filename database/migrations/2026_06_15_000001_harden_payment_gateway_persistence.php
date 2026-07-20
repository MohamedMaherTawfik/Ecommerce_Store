<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_payment_id')->nullable()->after('gateway')->index();
            $table->string('gateway_order_id')->nullable()->after('gateway_payment_id')->index();
            $table->text('payment_url')->nullable()->after('gateway_reference');
            $table->json('metadata')->nullable()->after('gateway_response');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('failed_at');
            $table->unique(['gateway', 'gateway_payment_id'], 'payments_gateway_payment_unique');
        });

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway');
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('status')->default('received');
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'event_id'], 'webhook_gateway_event_unique');
            $table->index(['gateway', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_gateway_payment_unique');
            $table->dropIndex(['gateway_payment_id']);
            $table->dropIndex(['gateway_order_id']);
            $table->dropColumn([
                'gateway_payment_id',
                'gateway_order_id',
                'payment_url',
                'metadata',
                'failed_at',
                'cancelled_at',
            ]);
        });
    }
};
