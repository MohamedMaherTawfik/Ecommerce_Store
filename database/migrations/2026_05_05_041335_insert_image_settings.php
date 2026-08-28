<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'navbar_image', 'value' => ''],
            ['key' => 'footer_image', 'value' => ''],
            ['key' => 'register_image', 'value' => ''],
        ];

        foreach ($settings as $setting) {
            DB::table('site_settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', ['navbar_image', 'footer_image', 'register_image'])->delete();
    }
};
