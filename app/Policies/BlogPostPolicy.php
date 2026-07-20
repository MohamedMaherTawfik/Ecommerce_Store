<?php

namespace App\Policies;

use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    public function manage(User $user, ?BlogPost $post = null): bool
    {
        return $user->hasPermission('blog.manage');
    }
}
