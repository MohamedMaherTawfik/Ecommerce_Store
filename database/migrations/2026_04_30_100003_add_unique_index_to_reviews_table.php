<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('
                DELETE FROM reviews
                WHERE id NOT IN (
                    SELECT MIN(id)
                    FROM reviews
                    WHERE user_id IS NOT NULL AND product_id IS NOT NULL
                    GROUP BY user_id, product_id
                    UNION
                    SELECT id
                    FROM reviews
                    WHERE user_id IS NULL OR product_id IS NULL
                )
            ');
        } else {
            DB::statement('
                DELETE r1 FROM reviews r1
                INNER JOIN reviews r2
                    ON r1.user_id = r2.user_id
                    AND r1.product_id = r2.product_id
                    AND r1.id > r2.id
                WHERE r1.user_id IS NOT NULL
                    AND r1.product_id IS NOT NULL
            ');
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id']);
        });
    }
};
