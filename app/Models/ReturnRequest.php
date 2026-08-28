<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'reason',
        'notes',
        'status',
        'admin_note',
        'approved_by',
        'approved_at',
        'rejected_at',
        'stock_restored_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'stock_restored_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnRequestItem::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
}
