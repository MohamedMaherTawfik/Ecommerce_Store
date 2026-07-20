<?php

namespace App\Policies;

use App\Models\Reviews;
use App\Models\User;

class ReviewPolicy
{
    public function manage(User $user, Reviews $review): bool
    {
        return $user->role === 'admin' || $review->user_id === $user->id;
    }
}
