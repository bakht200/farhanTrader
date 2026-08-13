<?php

use App\Models\Branch;
use App\Support\CurrentBranch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convert existing non-admin branch sales from global SALE-008838 style
 * to per-branch SALE-LA000001 style (first two letters of branch name + sequence).
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaultBranchId = CurrentBranch::DEFAULT_BRANCH_ID;
        $branches = Branch::query()
            ->where('id', '!=', $defaultBranchId)
            ->get()
            ->keyBy('id');

        if ($branches->isEmpty()) {
            return;
        }

        $prefixes = ['SALE', 'HOLD', 'ADJ'];

        foreach ($branches as $branchId => $branch) {
            $code = $branch->saleNumberCode();

            foreach ($prefixes as $prefix) {
                $sales = DB::table('sales')
                    ->where('branch_id', $branchId)
                    ->where('sale_number', 'like', $prefix.'-%')
                    ->orderBy('id')
                    ->get(['id', 'sale_number']);

                $seq = 0;

                foreach ($sales as $sale) {
                    $saleNumber = (string) $sale->sale_number;
                    $parts = explode('-', $saleNumber, 2);

                    // Already branch-style for this code? Keep and track max seq.
                    if (count($parts) === 2 && str_starts_with($parts[1], $code)) {
                        $numeric = substr($parts[1], strlen($code));
                        if ($numeric !== '' && ctype_digit($numeric)) {
                            $seq = max($seq, (int) $numeric);
                        }
                        continue;
                    }

                    // Admin-style PREFIX-000001 (digits only) → renumber
                    if (count($parts) === 2 && $parts[0] === $prefix && ctype_digit($parts[1])) {
                        $seq++;
                        $newNumber = $prefix.'-'.$code.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

                        // Avoid unique collisions while swapping
                        if ($newNumber === $saleNumber) {
                            continue;
                        }

                        DB::table('sales')->where('id', $sale->id)->update([
                            'sale_number' => $newNumber,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Irreversible data fix — intentionally empty.
    }
};
