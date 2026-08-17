<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentBranch;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || CurrentBranch::id($user) !== null;
    }

    public function view(User $user, Unit $unit): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->isAdmin();
    }
}
