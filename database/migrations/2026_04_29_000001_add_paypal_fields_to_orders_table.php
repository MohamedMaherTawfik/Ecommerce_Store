<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->index()->after('payment_status');
            $table->json('gateway_response')->nullable()->after('transaction_id');
            $table->timestamp('paid_at')->nullable()->after('gateway_response');
            $table->boolean('mail_sent')->default(false)->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropColumn([
                'transaction_id',
                'gateway_response',
                'paid_at',
                'mail_sent',
            ]);
        });
    }
};
