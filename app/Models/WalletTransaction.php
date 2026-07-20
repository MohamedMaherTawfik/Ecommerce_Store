<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Payment;

class WalletTransaction extends Model
{
    use SoftDeletes;
    protected $table='wallet_transactions';
    protected $fillable = [
        'wallet_id',
        'user_id',
        'payment_id',
        'amount',
        'type',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class,'wallet_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class,'payment_id');
    }

}
