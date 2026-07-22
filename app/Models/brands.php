<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class brands extends Model
{

    use HasFactory, SoftDeletes;
    protected $table = 'brands';
    protected $fillable = [
        'name',
        'image',
        'slug',
    ];

    public function products()
    {
        return $this->hasMany(Products::class,'brand_id');
    }
}
