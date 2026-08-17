<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Support\CurrentBranch;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || CurrentBranch::id($user) !== null;
    }

    public function view(User $user, Category $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin();
    }
}
