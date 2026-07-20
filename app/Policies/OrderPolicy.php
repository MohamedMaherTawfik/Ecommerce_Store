<?php

namespace App\Policies;

use App\Models\Orders;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Orders $order): bool
    {
        return $user->role === 'admin' || $order->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
