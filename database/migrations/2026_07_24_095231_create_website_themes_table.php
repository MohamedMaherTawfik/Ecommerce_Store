<?php

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
        Schema::create('website_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('primary');
            $table->string('secondary');
            $table->string('accent');
            $table->string('background')->nullable();
            $table->string('surface')->nullable();
            $table->string('border')->nullable();
            $table->string('text')->nullable();
            $table->string('text_secondary')->nullable();
            $table->string('success')->nullable();
            $table->string('warning')->nullable();
            $table->string('danger')->nullable();
            $table->string('info')->nullable();
            $table->string('hero_from')->nullable();
            $table->string('hero_to')->nullable();
            $table->boolean('is_dark')->default(false);
            $table->boolean('is_active')->default(false);
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_themes');
    }
};
