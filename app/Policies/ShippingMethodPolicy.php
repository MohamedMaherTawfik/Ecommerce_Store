<?php

namespace App\Policies;

use App\Models\User;

class ShippingMethodPolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
