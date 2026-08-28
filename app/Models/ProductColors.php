<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductColors extends Model
{
    protected $table = 'product_colors';

    protected $fillable = [
        'product_id',
        'color',
        'is_active',
    ];

    public function Product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
