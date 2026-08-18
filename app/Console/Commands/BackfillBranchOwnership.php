<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillBranchOwnership extends Command
{
    protected $signature = 'branches:backfill-ownership {--dry-run : Report only, do not write}';

    protected $description = 'Backfill child branch_id from parents and membership from usage. Does not assign orphans to branch 1.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? 'Dry run — no writes.' : 'Backfilling branch ownership…');

        $this->backfillChild('sale_items', 'sale_id', 'sales', $dry);
        $this->backfillChild('order_items', 'order_id', 'orders', $dry);
        $this->backfillChild('supplier_bill_items', 'supplier_bill_id', 'supplier_bills', $dry);
        $this->backfillChild('branch_share_investments', 'branch_share_id', 'branch_shares', $dry);

        $this->backfillMembershipFromUsage($dry);

        if (! $dry) {
            $restored = app(\App\Services\BranchStockService::class)->restoreMissingOwnerMembership();
            $this->line("Owner membership rows restored: {$restored}");
        }

        $this->info('Done. Re-run `php artisan branches:audit` and quarantine remaining nulls manually — they are not assigned to branch 1.');

        return self::SUCCESS;
    }

    private function backfillChild(string $table, string $parentKey, string $parentTable, bool $dry): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id') || ! Schema::hasTable($parentTable)) {
            return;
        }

        $count = (int) DB::table($table)->whereNull('branch_id')->count();
        $this->line("{$table}: {$count} rows missing branch_id");

        if ($dry || $count === 0) {
            return;
        }

        DB::statement("UPDATE {$table} SET branch_id = (SELECT branch_id FROM {$parentTable} WHERE {$parentTable}.id = {$table}.{$parentKey}) WHERE branch_id IS NULL");
    }

    private function backfillMembershipFromUsage(bool $dry): void
    {
        if (! Schema::hasTable('branch_product_stocks') || ! Schema::hasTable('products')) {
            return;
        }

        $pairs = collect();

        if (Schema::hasTable('sale_items') && Schema::hasTable('sales')) {
            $pairs = $pairs->merge(
                DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->whereNotNull('sale_items.product_id')
                    ->whereNotNull('sales.branch_id')
                    ->select('sales.branch_id', 'sale_items.product_id')
                    ->distinct()
                    ->get()
            );
        }

        if (Schema::hasTable('supplier_bill_items') && Schema::hasTable('supplier_bills')) {
            $pairs = $pairs->merge(
                DB::table('supplier_bill_items')
                    ->join('supplier_bills', 'supplier_bills.id', '=', 'supplier_bill_items.supplier_bill_id')
                    ->whereNotNull('supplier_bill_items.product_id')
                    ->whereNotNull('supplier_bills.branch_id')
                    ->select('supplier_bills.branch_id', 'supplier_bill_items.product_id')
                    ->distinct()
                    ->get()
            );
        }

        $pairs = $pairs->unique(fn ($row) => $row->branch_id.'-'.$row->product_id);
        $created = 0;

        foreach ($pairs as $row) {
            $exists = DB::table('branch_product_stocks')
                ->where('branch_id', $row->branch_id)
                ->where('product_id', $row->product_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $created++;
            if ($dry) {
                continue;
            }

            $sellingType = DB::table('products')->where('id', $row->product_id)->value('selling_type') ?: 'both';

            DB::table('branch_product_stocks')->insert([
                'branch_id' => $row->branch_id,
                'product_id' => $row->product_id,
                'stock_quantity' => 0,
                'selling_type' => $sellingType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->line("Membership rows to create from usage: {$created}");
    }
}
