<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = 'stocks';

    protected $fillable = [
        'product_id',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
