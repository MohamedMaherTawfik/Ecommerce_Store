<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->index();
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index(['created_at', 'payment_status'], 'orders_created_payment_idx');
            $table->index(['user_id', 'payment_status'], 'orders_user_payment_idx');
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['product_id', 'order_id'], 'order_items_product_order_idx');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_product_order_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_created_payment_idx');
            $table->dropIndex('orders_user_payment_idx');
            $table->dropIndex('orders_status_created_idx');
        });

        if (Schema::hasColumn('categories', 'is_active')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('categories_is_active_index');
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
