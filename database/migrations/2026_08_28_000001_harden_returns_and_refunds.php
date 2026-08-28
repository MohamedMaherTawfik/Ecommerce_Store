<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->timestamp('stock_restored_at')->nullable()->after('rejected_at');
            $table->index(['status', 'stock_restored_at'], 'returns_status_stock_restored_index');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable()->after('return_request_id');
            $table->unique('idempotency_key', 'refunds_idempotency_key_unique');
            $table->index(['return_request_id', 'status'], 'refunds_return_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex('refunds_return_status_index');
            $table->dropUnique('refunds_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropIndex('returns_status_stock_restored_index');
            $table->dropColumn('stock_restored_at');
        });
    }
};
