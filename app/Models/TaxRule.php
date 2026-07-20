<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRule extends Model
{
    protected $fillable = [
        'name',
        'country',
        'state',
        'city',
        'rate',
        'type',
        'price_includes_tax',
        'applies_to_shipping',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'price_includes_tax' => 'boolean',
        'applies_to_shipping' => 'boolean',
        'is_active' => 'boolean',
    ];
}
