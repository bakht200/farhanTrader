<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeBranchOperationalData extends Command
{
    protected $signature = 'branches:wipe-operational
        {--name=* : Branch name fragment to wipe (does not have to be exact)}
        {--force : Skip confirmation}';

    protected $description = 'Delete operational data for named branches. Never touches Phandu / branch id 1 or shared product rows.';

    public function handle(): int
    {
        $names = array_values(array_filter(array_map('trim', (array) $this->option('name'))));
        if ($names === []) {
            $this->error('Pass at least one --name=');

            return self::FAILURE;
        }

        $branches = DB::table('branches')->orderBy('id')->get(['id', 'name']);
        $targets = $branches->filter(function ($branch) use ($names) {
            foreach ($names as $name) {
                if (stripos((string) $branch->name, $name) !== false) {
                    return true;
                }
            }

            return false;
        });

        if ($targets->isEmpty()) {
            $this->error('No matching branches.');

            return self::FAILURE;
        }

        foreach ($targets as $branch) {
            if ((int) $branch->id === 1 || stripos((string) $branch->name, 'Phandu') !== false) {
                $this->error("Refusing to wipe protected branch {$branch->id} {$branch->name}");

                return self::FAILURE;
            }
        }

        $ids = $targets->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->table(['id', 'name'], $targets->map(fn ($b) => [$b->id, $b->name])->all());

        if (! $this->option('force') && ! $this->confirm('Delete all operational data for these branches? Phandu will not be changed.')) {
            return self::SUCCESS;
        }

        $protected = $this->snapshot(1);

        $deleted = DB::transaction(function () use ($ids, $protected) {
            $deleted = $this->wipe($ids);
            $after = $this->snapshot(1);

            foreach ($protected as $key => $count) {
                $changed = is_float($count)
                    ? abs((float) $after[$key] - (float) $count) > 0.0001
                    : (int) $after[$key] !== (int) $count;

                if ($changed) {
                    throw new \RuntimeException("Phandu {$key} changed from {$count} to {$after[$key]} — rolling back.");
                }
            }

            return $deleted;
        });

        $this->table(['table', 'deleted'], collect($deleted)->map(fn ($c, $t) => [$t, $c])->all());
        $this->info('Phandu snapshot unchanged.');

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $ids
     * @return array<string, int>
     */
    private function wipe(array $ids): array
    {
        $deleted = [];

        $childByParent = [
            'sale_items' => ['sale_id', 'sales'],
            'order_items' => ['order_id', 'orders'],
            'supplier_bill_items' => ['supplier_bill_id', 'supplier_bills'],
            'branch_share_investments' => ['branch_share_id', 'branch_shares'],
        ];

        foreach ($childByParent as $table => [$fk, $parent]) {
            $deleted[$table] = $this->deleteWhereIn($table, $fk, $parent, $ids);
        }

        foreach ([
            'inventory_movements',
            'invoices',
            'sales_returns',
            'customer_payment_logs',
            'sales',
            'orders',
            'supplier_transactions',
            'supplier_bills',
            'expenses',
            'product_history',
            'branch_shares',
            'sync_id_mappings',
            'user_activity_logs',
            'branch_product_stocks',
            'customers',
            'suppliers',
        ] as $table) {
            $deleted[$table] = $this->deleteByBranch($table, $ids);
        }

        return $deleted;
    }

    /**
     * @param  list<int>  $ids
     */
    private function deleteWhereIn(string $table, string $fk, string $parent, array $ids): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable($parent) || ! Schema::hasColumn($parent, 'branch_id')) {
            return 0;
        }

        return (int) DB::table($table)
            ->whereIn($fk, DB::table($parent)->whereIn('branch_id', $ids)->select('id'))
            ->delete();
    }

    /**
     * @param  list<int>  $ids
     */
    private function deleteByBranch(string $table, array $ids): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
            return 0;
        }

        return (int) DB::table($table)->whereIn('branch_id', $ids)->delete();
    }

    /**
     * @return array<string, int|float>
     */
    private function snapshot(int $branchId): array
    {
        return [
            'customers' => (int) DB::table('customers')->where('branch_id', $branchId)->count(),
            'suppliers' => (int) DB::table('suppliers')->where('branch_id', $branchId)->count(),
            'sales' => (int) DB::table('sales')->where('branch_id', $branchId)->count(),
            'sales_total' => (float) DB::table('sales')->where('branch_id', $branchId)->sum('total_amount'),
            'stock_rows' => (int) DB::table('branch_product_stocks')->where('branch_id', $branchId)->count(),
            'stock_qty' => (float) DB::table('branch_product_stocks')->where('branch_id', $branchId)->sum('stock_quantity'),
            'products' => (int) DB::table('products')->count(),
        ];
    }
}
