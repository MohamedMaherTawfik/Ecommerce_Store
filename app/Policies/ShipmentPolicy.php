<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->role === 'admin' || $shipment->order?->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
