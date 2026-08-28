<?php

use App\Models\Cart;
use App\Models\Products;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Cart::class, 'cart_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Products::class, 'product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('price', 10, 2);
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['cart_id', 'product_id', 'color', 'size']);
            $table->index('cart_id');
            $table->index('product_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
