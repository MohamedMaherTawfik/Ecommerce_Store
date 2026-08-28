<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
