<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function view(User $user, Refund $refund): bool
    {
        return $user->role === 'admin' || $refund->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
