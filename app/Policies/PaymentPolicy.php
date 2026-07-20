<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function view(User $user, Payment $payment): bool
    {
        return $user->role === 'admin' || $payment->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
