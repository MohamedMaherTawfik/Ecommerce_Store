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
        Schema::table('orders', function (Blueprint $table) {
            $table->date('created_date')->nullable()->index();
            $table->string('created_month', 7)->nullable()->index();
            $table->string('created_year', 4)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_date']);
            $table->dropIndex(['created_month']);
            $table->dropIndex(['created_year']);
            $table->dropColumn(['created_date', 'created_month', 'created_year']);
        });
    }
};
