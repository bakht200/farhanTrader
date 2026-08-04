<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CurrentBranch
{
    public const DEFAULT_BRANCH_ID = 1;

    public static function id(?User $user = null): ?int
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            return null;
        }

        if (!$user->isAdmin()) {
            return $user->branch_id ? (int) $user->branch_id : self::DEFAULT_BRANCH_ID;
        }

        $sessionBranchId = session('active_branch_id');

        if ($sessionBranchId && Branch::where('id', $sessionBranchId)->where('is_active', true)->exists()) {
            return (int) $sessionBranchId;
        }

        return self::DEFAULT_BRANCH_ID;
    }

    public static function get(?User $user = null): ?Branch
    {
        $branchId = self::id($user);

        if (!$branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    public static function setActive(int $branchId): void
    {
        session(['active_branch_id' => $branchId]);
    }
}
