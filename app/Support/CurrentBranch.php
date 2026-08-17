<?php

namespace App\Support;

use App\Exceptions\MissingBranchContextException;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CurrentBranch
{
    /** Historical Phandu branch — sale-number format only, never a data fallback. */
    public const DEFAULT_BRANCH_ID = 1;

    public static function strictIsolation(): bool
    {
        return (bool) config('branches.strict_isolation', false);
    }

    public static function id(?User $user = null): ?int
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return null;
        }

        if (! $user->isAdmin()) {
            if (! $user->is_active) {
                return null;
            }

            if (! $user->branch_id) {
                return null;
            }

            $exists = Branch::query()
                ->where('id', $user->branch_id)
                ->where('is_active', true)
                ->exists();

            return $exists ? (int) $user->branch_id : null;
        }

        $sessionBranchId = session('active_branch_id');

        if ($sessionBranchId && Branch::query()->where('id', $sessionBranchId)->where('is_active', true)->exists()) {
            return (int) $sessionBranchId;
        }

        return null;
    }

    public static function requireId(?User $user = null): int
    {
        $branchId = self::id($user);

        if (! $branchId) {
            throw new MissingBranchContextException();
        }

        return $branchId;
    }

    public static function get(?User $user = null): ?Branch
    {
        $branchId = self::id($user);

        if (! $branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    public static function setActive(int $branchId): void
    {
        session(['active_branch_id' => $branchId]);
    }
}
