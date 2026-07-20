<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'payment_id',
        'return_request_id',
        'user_id',
        'amount',
        'currency',
        'gateway',
        'gateway_refund_id',
        'status',
        'reason',
        'admin_note',
        'gateway_response',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
    ];
}
