<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderTax extends Model
{
    protected $fillable = [
        'order_id',
        'tax_rule_id',
        'name',
        'rate',
        'amount',
        'price_includes_tax',
    ];

    protected $casts = [
        'price_includes_tax' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function rule()
    {
        return $this->belongsTo(TaxRule::class, 'tax_rule_id');
    }
}
