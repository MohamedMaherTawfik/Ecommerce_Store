<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('addresses')) {
            Schema::table('addresses', function (Blueprint $table) {
                $this->stringColumn($table, 'type', 'shipping');
                $this->stringColumn($table, 'name');
                $this->stringColumn($table, 'email');
                $this->stringColumn($table, 'country_code');
                $this->stringColumn($table, 'city');
                $this->stringColumn($table, 'area');
                $this->stringColumn($table, 'street');
                $this->stringColumn($table, 'building_no');
                $this->stringColumn($table, 'apartment_no');
                $this->stringColumn($table, 'floor');
                $this->stringColumn($table, 'postal_code');
                $this->textColumn($table, 'landmark');
                $this->decimalColumn($table, 'latitude', 10, 7);
                $this->decimalColumn($table, 'longitude', 10, 7);
                $this->booleanColumn($table, 'is_default_shipping');
                $this->booleanColumn($table, 'is_default_billing');
            });
        }

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('provider')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('mode')->default('test');
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('provider')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods')->cascadeOnDelete();
            $table->foreignId('shipping_zone_id')->nullable()->constrained('shipping_zones')->nullOnDelete();
            $table->string('name');
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_order_amount', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->default(0);
            $table->boolean('is_percentage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->decimal('rate', 10, 4)->default(0);
            $table->string('type')->default('percentage');
            $table->boolean('price_includes_tax')->default(false);
            $table->boolean('applies_to_shipping')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('material')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_variant_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->string('image');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $this->integerColumn($table, 'stock_quantity');
                $this->integerColumn($table, 'low_stock_threshold', null);
                $this->booleanColumn($table, 'manage_stock', true);
                $this->stringColumn($table, 'stock_status', 'in_stock');
                $this->booleanColumn($table, 'allow_backorder');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $this->stringColumn($table, 'order_status', 'pending');
                $this->stringColumn($table, 'shipping_status', 'pending');
                $this->stringColumn($table, 'refund_status', 'none');
                $this->decimalColumn($table, 'discount_amount', 10, 2, 0);
                $this->decimalColumn($table, 'shipping_amount', 10, 2, 0);
                $this->decimalColumn($table, 'tax_amount', 10, 2, 0);
                $this->booleanColumn($table, 'tax_included');
                $this->stringColumn($table, 'currency', 'USD');
                $this->jsonColumn($table, 'shipping_address_snapshot');
                $this->jsonColumn($table, 'billing_address_snapshot');
                $this->jsonColumn($table, 'shipping_snapshot');
                $this->jsonColumn($table, 'tax_snapshot');
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $this->foreignColumn($table, 'product_variant_id', 'product_variants');
                $this->stringColumn($table, 'product_name');
                $this->stringColumn($table, 'sku');
                $this->decimalColumn($table, 'unit_price', 10, 2, 0);
                $this->decimalColumn($table, 'total_price', 10, 2, 0);
                $this->jsonColumn($table, 'options');
            });
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('gateway');
            $table->string('transaction_id')->nullable()->index();
            $table->string('gateway_reference')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status')->default('pending');
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();
            $table->string('provider')->default('manual');
            $table->string('carrier')->nullable();
            $table->string('service')->nullable();
            $table->string('rate_id')->nullable();
            $table->string('easypost_shipment_id')->nullable()->index();
            $table->string('easypost_tracker_id')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->string('tracking_url')->nullable();
            $table->string('label_url')->nullable();
            $table->string('shipment_status')->default('pending');
            $table->decimal('cost', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->json('raw_response')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('tax_rule_id')->nullable()->constrained('tax_rules')->nullOnDelete();
            $table->string('name');
            $table->decimal('rate', 10, 4)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->boolean('price_includes_tax')->default(false);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('company_name')->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_tax_number')->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone');
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('return_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_request_id')->constrained('return_requests')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('return_request_id')->nullable()->constrained('return_requests')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('gateway')->default('manual');
            $table->string('gateway_refund_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->text('admin_note')->nullable();
            $table->json('gateway_response')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('return_request_items');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_taxes');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('product_variant_images');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('tax_rules');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_methods');
        Schema::dropIfExists('payment_methods');
    }

    private function stringColumn(Blueprint $table, string $column, ?string $default = null): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $definition = $table->string($column)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        }
    }

    private function textColumn(Blueprint $table, string $column): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $table->text($column)->nullable();
        }
    }

    private function integerColumn(Blueprint $table, string $column, ?int $default = 0): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $definition = $table->integer($column)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        }
    }

    private function booleanColumn(Blueprint $table, string $column, bool $default = false): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $table->boolean($column)->default($default);
        }
    }

    private function decimalColumn(Blueprint $table, string $column, int $precision, int $scale, mixed $default = null): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $definition = $table->decimal($column, $precision, $scale)->nullable();
            if ($default !== null) {
                $definition->default($default);
            }
        }
    }

    private function jsonColumn(Blueprint $table, string $column): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $table->json($column)->nullable();
        }
    }

    private function foreignColumn(Blueprint $table, string $column, string $foreignTable): void
    {
        if (!Schema::hasColumn($table->getTable(), $column)) {
            $table->foreignId($column)->nullable()->constrained($foreignTable)->nullOnDelete();
        }
    }
};
