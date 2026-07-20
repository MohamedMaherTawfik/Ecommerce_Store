<?php

namespace App\Policies;

use App\Models\ReturnRequest;
use App\Models\User;

class ReturnRequestPolicy
{
    public function view(User $user, ReturnRequest $returnRequest): bool
    {
        return $user->role === 'admin' || $returnRequest->user_id === $user->id;
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
