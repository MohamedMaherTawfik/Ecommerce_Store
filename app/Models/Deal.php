<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $table = 'deals';
    protected $fillable = [
        'name',
        'category',
        'icon',
        'discount',
        'sale_price',
        'original_price',
        'sold_percent',
        'sold_label',
        'sort_order',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'discount'       => 'integer',
        'sale_price'     => 'decimal:2',
        'original_price' => 'decimal:2',
        'sold_percent'   => 'integer',
        'expires_at'     => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
