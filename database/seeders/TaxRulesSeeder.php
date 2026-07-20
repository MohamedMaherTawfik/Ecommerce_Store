<?php

namespace Database\Seeders;

use App\Models\TaxRule;
use Illuminate\Database\Seeder;

class TaxRulesSeeder extends Seeder
{
    public function run(): void
    {
        TaxRule::updateOrCreate(
            ['name' => 'Default VAT'],
            [
                'country' => null,
                'rate' => 0,
                'type' => 'percentage',
                'price_includes_tax' => false,
                'applies_to_shipping' => false,
                'is_active' => true,
                'priority' => 0,
            ]
        );
    }
}
