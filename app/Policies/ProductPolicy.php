<?php

namespace App\Policies;

use App\Models\Products;
use App\Models\User;

class ProductPolicy
{
    public function view(?User $user, Products $product): bool
    {
        return (bool) $product->is_active || $user?->role === 'admin';
    }

    public function manage(User $user): bool
    {
        return $user->role === 'admin';
    }
}
