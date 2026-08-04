<?php

namespace App\Models\Concerns;

use App\Support\CurrentBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->branch_id)) {
                $model->branch_id = CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID;
            }
        });

        static::addGlobalScope('branch', function (Builder $builder) {
            $branchId = CurrentBranch::id();

            if ($branchId) {
                $builder->where($builder->getModel()->getTable() . '.branch_id', $branchId);
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }
}
