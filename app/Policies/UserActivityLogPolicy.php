<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserActivityLog;

class UserActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, UserActivityLog $userActivityLog): bool
    {
        return $user->isAdmin();
    }
}
