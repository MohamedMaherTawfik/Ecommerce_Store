<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('icon')->nullable();
            $table->unsignedInteger('discount');
            $table->decimal('sale_price', 10, 2);
            $table->decimal('original_price', 10, 2);
            $table->unsignedInteger('sold_percent')->default(0);
            $table->string('sold_label')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
