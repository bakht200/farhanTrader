<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesBranchRecords
{
    protected function recordBranchId(Model $record): ?int
    {
        $branchId = $record->getAttribute('branch_id');

        return $branchId !== null ? (int) $branchId : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || CurrentBranch::id($user) !== null;
    }

    public function view(User $user, Model $record): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $recordBranch = $this->recordBranchId($record);

        return $recordBranch !== null && (int) $user->branch_id === $recordBranch;
    }

    public function create(User $user): bool
    {
        return CurrentBranch::id($user) !== null;
    }

    public function update(User $user, Model $record): bool
    {
        $recordBranch = $this->recordBranchId($record);

        if ($recordBranch === null) {
            return false;
        }

        if ($user->isAdmin()) {
            return CurrentBranch::id($user) === $recordBranch;
        }

        return (int) $user->branch_id === $recordBranch;
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->update($user, $record);
    }
}
