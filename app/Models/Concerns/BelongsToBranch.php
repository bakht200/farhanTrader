<?php

namespace App\Models\Concerns;

use App\Support\CurrentBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::creating(function (Model $model) {
            if (! empty($model->branch_id)) {
                return;
            }

            $model->branch_id = CurrentBranch::requireId();
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = CurrentBranch::id();

            if ($branchId) {
                $builder->where($builder->getModel()->getTable().'.branch_id', $branchId);

                return;
            }

            $user = Auth::user();

            if ($user && ! $user->isAdmin()) {
                $builder->whereRaw('0 = 1');
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
