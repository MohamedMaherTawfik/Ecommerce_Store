<?php

namespace App\Policies;

use App\Models\User;

class TaxRulePolicy
{
    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
