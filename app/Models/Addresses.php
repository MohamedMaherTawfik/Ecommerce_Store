<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addresses extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'country',
        'state',
        'type',
        'name',
        'email',
        'country_code',
        'city',
        'area',
        'street',
        'building_no',
        'apartment_no',
        'floor',
        'postal_code',
        'landmark',
        'latitude',
        'longitude',
        'is_default_shipping',
        'is_default_billing',
    ];

    protected $casts = [
        'is_default_shipping' => 'boolean',
        'is_default_billing' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function snapshot(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'state' => $this->state,
            'city' => $this->city,
            'area' => $this->area,
            'street' => $this->street,
            'building_no' => $this->building_no,
            'apartment_no' => $this->apartment_no,
            'floor' => $this->floor,
            'postal_code' => $this->postal_code,
            'landmark' => $this->landmark,
        ];
    }
}
