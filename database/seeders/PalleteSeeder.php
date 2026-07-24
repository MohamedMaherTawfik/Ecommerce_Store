<?php

namespace Database\Seeders;

use App\Models\WebsiteThemes;
use Illuminate\Database\Seeder;

class PalleteSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [

            [
                'name' => 'Luxury Rose',
                'slug' => 'luxury-rose',
                'primary' => '#D63384',
                'secondary' => '#F8BBD9',
                'accent' => '#FFD166',
                'background' => '#FFF8FB',
                'surface' => '#FFFFFF',
                'border' => '#F3D6E4',
                'text' => '#2B2B2B',
                'text_secondary' => '#6B7280',
                'success' => '#22C55E',
                'warning' => '#F59E0B',
                'danger' => '#EF4444',
                'info' => '#3B82F6',
                'hero_from' => '#FCE7F3',
                'hero_to' => '#FBCFE8',
                'is_dark' => false,
                'is_active' => true,
            ],

            [
                'name' => 'Ocean Fresh',
                'slug' => 'ocean-fresh',
                'primary' => '#0EA5E9',
                'secondary' => '#7DD3FC',
                'accent' => '#14B8A6',
                'background' => '#F8FCFF',
                'surface' => '#FFFFFF',
                'border' => '#D9F2FD',
                'text' => '#1E293B',
                'text_secondary' => '#64748B',
                'success' => '#22C55E',
                'warning' => '#F59E0B',
                'danger' => '#EF4444',
                'info' => '#38BDF8',
                'hero_from' => '#DBF4FF',
                'hero_to' => '#E0F7FA',
                'is_dark' => false,
                'is_active' => false,
            ],

            [
                'name' => 'Golden Elegance',
                'slug' => 'golden-elegance',
                'primary' => '#B8860B',
                'secondary' => '#E8C76A',
                'accent' => '#FFF3C4',
                'background' => '#FFFCF5',
                'surface' => '#FFFFFF',
                'border' => '#F5E7B2',
                'text' => '#2D2A26',
                'text_secondary' => '#78716C',
                'success' => '#22C55E',
                'warning' => '#F59E0B',
                'danger' => '#DC2626',
                'info' => '#2563EB',
                'hero_from' => '#FFF8E1',
                'hero_to' => '#FFE9A8',
                'is_dark' => false,
                'is_active' => false,
            ],

            [
                'name' => 'Midnight Beauty',
                'slug' => 'midnight-beauty',
                'primary' => '#8B5CF6',
                'secondary' => '#A78BFA',
                'accent' => '#F472B6',
                'background' => '#111827',
                'surface' => '#1F2937',
                'border' => '#374151',
                'text' => '#F9FAFB',
                'text_secondary' => '#D1D5DB',
                'success' => '#22C55E',
                'warning' => '#F59E0B',
                'danger' => '#EF4444',
                'info' => '#60A5FA',
                'hero_from' => '#1E1B4B',
                'hero_to' => '#312E81',
                'is_dark' => true,
                'is_active' => false,
            ],

        ];

        foreach ($themes as $theme) {
            WebsiteThemes::updateOrCreate(
                ['slug' => $theme['slug']],
                $theme
            );
        }
    }
}
