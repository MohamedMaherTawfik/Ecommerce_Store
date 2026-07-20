<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUsReply extends Model
{
    protected $fillable = [
        'contact_us_id',
        'admin_id',
        'message',
    ];

    public function contactUs()
    {
        return $this->belongsTo(ContactUs::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
