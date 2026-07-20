<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'provider',
        'carrier',
        'service',
        'rate_id',
        'easypost_shipment_id',
        'easypost_tracker_id',
        'tracking_number',
        'tracking_url',
        'label_url',
        'shipment_status',
        'cost',
        'currency',
        'raw_response',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function method()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}
