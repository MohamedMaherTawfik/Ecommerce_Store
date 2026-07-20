<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentSetting extends Model
{
    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];
}
