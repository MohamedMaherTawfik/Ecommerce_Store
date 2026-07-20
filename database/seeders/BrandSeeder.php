<?php

namespace Database\Seeders;

use App\Models\brands;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['name' => 'Aster & Co', 'image' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?w=900&auto=format&fit=crop'],
            ['name' => 'Northline Studio', 'image' => 'https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?w=900&auto=format&fit=crop'],
            ['name' => 'Urban Loom', 'image' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=900&auto=format&fit=crop'],
            ['name' => 'Maison Vale', 'image' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=900&auto=format&fit=crop'],
            ['name' => 'Thread Theory', 'image' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?w=900&auto=format&fit=crop'],
        ])->each(fn ($brand) => brands::updateOrCreate(
            ['name' => $brand['name']],
            ['slug' => Str::slug($brand['name']), 'image' => $brand['image']]
        ));
    }
}
