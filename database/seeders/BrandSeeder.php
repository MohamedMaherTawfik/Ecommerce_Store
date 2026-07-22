<?php

namespace Database\Seeders;

use App\Models\brands;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'L\'Oréal Paris',
                'slug' => 'loreal-paris',
                'image' => 'https://picsum.photos/seed/loreal/600/600',
            ],
            [
                'name' => 'Maybelline',
                'slug' => 'maybelline',
                'image' => 'https://picsum.photos/seed/maybelline/600/600',
            ],
            [
                'name' => 'Nivea',
                'slug' => 'nivea',
                'image' => 'https://picsum.photos/seed/nivea/600/600',
            ],
            [
                'name' => 'The Ordinary',
                'slug' => 'the-ordinary',
                'image' => 'https://picsum.photos/seed/the-ordinary/600/600',
            ],
            [
                'name' => 'CeraVe',
                'slug' => 'cerave',
                'image' => 'https://picsum.photos/seed/cerave/600/600',
            ],
            [
                'name' => 'La Roche-Posay',
                'slug' => 'la-roche-posay',
                'image' => 'https://picsum.photos/seed/la-roche-posay/600/600',
            ],
            [
                'name' => 'Garnier',
                'slug' => 'garnier',
                'image' => 'https://picsum.photos/seed/garnier/600/600',
            ],
            [
                'name' => 'Dove',
                'slug' => 'dove',
                'image' => 'https://picsum.photos/seed/dove/600/600',
            ],
        ];

        foreach ($brands as $brand) {
            brands::updateOrCreate(
                ['slug' => $brand['slug']],
                $brand
            );
        }
    }
}
