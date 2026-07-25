<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orders extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'payment_method',
        'idempotency_key',
        'payment_status',
        'subtotal',
        'tax',
        'shipping_cost',
        'discount',
        'total',
        'phone',
        'address',
        'city',
        'country',
        'notes',
        'delivered_at',
        'transaction_id',
        'gateway_response',
        'paid_at',
        'mail_sent',
        'order_status',
        'shipping_status',
        'refund_status',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'tax_included',
        'currency',
        'shipping_address_snapshot',
        'billing_address_snapshot',
        'shipping_snapshot',
        'tax_snapshot',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'mail_sent' => 'boolean',
        'tax_included' => 'boolean',
        'shipping_address_snapshot' => 'array',
        'billing_address_snapshot' => 'array',
        'shipping_snapshot' => 'array',
        'tax_snapshot' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItems::class, 'order_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(OrderStatusLog::class, 'order_id')->latest();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class, 'order_id')->latestOfMany();
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class, 'order_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }

    public function taxes()
    {
        return $this->hasMany(OrderTax::class, 'order_id');
    }

    public function returns()
    {
        return $this->hasMany(ReturnRequest::class, 'order_id');
    }
}
