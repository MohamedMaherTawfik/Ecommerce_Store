<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSizes extends Model
{
    protected $table = 'product_sizes';

    protected $fillable = [
        'product_id',
        'size',
        'is_active',
    ];

    public function Product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
