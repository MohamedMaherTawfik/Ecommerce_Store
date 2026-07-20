<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'payment_method_id',
        'gateway',
        'transaction_id',
        'gateway_payment_id',
        'gateway_order_id',
        'gateway_reference',
        'payment_url',
        'metadata',
        'amount',
        'currency',
        'status',
        'gateway_response',
        'paid_at',
        'failed_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
