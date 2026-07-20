<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->string('canonical_url')->nullable()->after('og_image');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->string('meta_title')->nullable()->after('description');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->string('canonical_url')->nullable()->after('og_image');
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->text('meta_keywords')->nullable()->after('meta_description');
            $table->string('canonical_url')->nullable()->after('twitter_image');
        });

        $this->backfillUniqueSlugs('products');
        $this->backfillUniqueSlugs('categories');

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'canonical_url',
            ]);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'description',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'og_title',
                'og_description',
                'og_image',
                'canonical_url',
            ]);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['meta_keywords', 'canonical_url']);
        });
    }

    private function backfillUniqueSlugs(string $table): void
    {
        $used = [];

        DB::table($table)
            ->select(['id', 'name', 'slug'])
            ->orderBy('id')
            ->get()
            ->each(function ($record) use ($table, &$used) {
                $base = Str::slug($record->slug ?: $record->name) ?: "{$table}-{$record->id}";
                $slug = $base;
                $suffix = 2;

                while (isset($used[$slug])) {
                    $slug = "{$base}-{$suffix}";
                    $suffix++;
                }

                $used[$slug] = true;
                DB::table($table)->where('id', $record->id)->update(['slug' => $slug]);
            });
    }
};
