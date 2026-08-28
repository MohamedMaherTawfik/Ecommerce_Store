<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavLink extends Model
{
    protected $table = 'nav_links';

    protected $fillable = [
        'key',
        'route',
        'icon',
        'location',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }
}
