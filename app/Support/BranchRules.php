<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class BranchRules
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        $rule = Rule::exists($table, $column);
        $branchId = CurrentBranch::id();

        if ($branchId) {
            $rule->where('branch_id', $branchId);
        }

        return $rule;
    }

    public static function existsVisibleProduct(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $exists = Product::query()
                ->visibleToBranch()
                ->whereKey($value)
                ->exists();

            if (! $exists) {
                $fail('The selected product is not available in this branch.');
            }
        };
    }
}
