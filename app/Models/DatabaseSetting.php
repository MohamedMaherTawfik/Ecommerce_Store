<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class DatabaseSetting extends Model
{
    protected $fillable = [
        'driver',
        'host',
        'port',
        'database',
        'username',
        'password',
        'sqlite_path',
        'is_active',
        'last_tested_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value !== null && $value !== ''
            ? Crypt::encryptString($value)
            : null;
    }

    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
