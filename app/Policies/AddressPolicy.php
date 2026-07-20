<?php

namespace App\Policies;

use App\Models\Addresses;
use App\Models\User;

class AddressPolicy
{
    public function manage(User $user, Addresses $address): bool
    {
        return $user->role === 'admin' || $address->user_id === $user->id;
    }
}
