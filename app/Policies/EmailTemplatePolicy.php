<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    public function manage(User $user, ?EmailTemplate $template = null): bool
    {
        return $user->hasPermission('email_templates.manage');
    }
}
