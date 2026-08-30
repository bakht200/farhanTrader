<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\CurrentBranch;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || CurrentBranch::id($user) !== null;
    }

    public function view(User $user, Product $product): bool
    {
        $branchId = CurrentBranch::id($user);

        if ($user->isAdmin() && $branchId === null) {
            return true;
        }

        if ($branchId === null) {
            return false;
        }

        return Product::query()
            ->visibleToBranch($branchId)
            ->whereKey($product->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return CurrentBranch::id($user) !== null;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product) && CurrentBranch::id($user) !== null;
    }

    public function delete(User $user, Product $product): bool
    {
        if (! $user->isAdmin() && $product->isPhanduCatalog()) {
            return false;
        }

        if ($user->isAdmin()) {
            return CurrentBranch::id($user) !== null;
        }

        $branchId = CurrentBranch::id($user);
        if ($branchId === null || ! $product->isAssignedToBranch($branchId)) {
            return false;
        }

        return $product->branchStocks()->count() <= 1;
    }

    public function assign(User $user, Product $product): bool
    {
        return $user->isAdmin() && CurrentBranch::id($user) !== null;
    }
}
