<?php

namespace App\Policies;

use App\Models\User;

class SiteSettingPolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
