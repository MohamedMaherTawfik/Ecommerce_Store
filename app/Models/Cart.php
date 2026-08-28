<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'carts';

    protected $fillable = [
        'user_id',
        'coupon_id',
        'discount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItems::class, 'cart_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
