<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Footer extends Model
{
    protected $table = 'footers';

    protected $fillable = [
        'facebook',
        'twitter',
        'instagram',
        'linkedin',
        'youtube',
        'pinterest',
        'tiktok',
        'google',
        'whatsapp',
        'phone',
        'email',
        'address',
        'copyright',
        'logo',
        'favicon',
    ];
}