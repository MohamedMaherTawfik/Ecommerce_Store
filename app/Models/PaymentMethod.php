<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'code',
        'provider',
        'is_active',
        'is_default',
        'mode',
        'credentials',
        'settings',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'credentials' => 'encrypted:array',
        'settings' => 'array',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
