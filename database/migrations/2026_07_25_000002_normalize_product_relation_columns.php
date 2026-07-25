<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('brands')->nullOnDelete();
            }
        });

        $legacyCategoryColumn = 'catego'.'rey_id';
        $legacyPluralCategoryColumn = 'categor'.'ies_id';
        $legacyPluralBrandColumn = 'bran'.'ds_id';

        if (Schema::hasColumn('products', $legacyCategoryColumn)) {
            DB::table('products')
                ->whereNull('category_id')
                ->update(['category_id' => DB::raw($legacyCategoryColumn)]);
        }

        foreach ([$legacyPluralCategoryColumn => 'category_id', $legacyPluralBrandColumn => 'brand_id'] as $old => $new) {
            if (Schema::hasColumn('products', $old)) {
                DB::table('products')
                    ->whereNull($new)
                    ->update([$new => DB::raw($old)]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) {
                $table->dropConstrainedForeignId('category_id');
            }

            if (Schema::hasColumn('products', 'brand_id')) {
                $table->dropConstrainedForeignId('brand_id');
            }
        });
    }
};
