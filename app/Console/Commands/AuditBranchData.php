<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditBranchData extends Command
{
    protected $signature = 'branches:audit {--json : Output JSON}';

    protected $description = 'Audit branch ownership gaps and print per-branch baselines (no writes).';

    /** @var list<string> */
    protected array $businessTables = [
        'customers',
        'suppliers',
        'sales',
        'orders',
        'expenses',
        'invoices',
        'sales_returns',
        'supplier_bills',
        'supplier_transactions',
        'customer_payment_logs',
        'product_history',
        'sale_items',
        'order_items',
        'supplier_bill_items',
        'sync_id_mappings',
        'user_activity_logs',
    ];

    public function handle(): int
    {
        $report = [
            'generated_at' => now()->toIso8601String(),
            'users_without_branch' => [],
            'inactive_user_branches' => [],
            'null_branch_counts' => [],
            'cross_branch_mismatches' => [],
            'products_without_membership' => 0,
            'baselines' => [],
        ];

        if (Schema::hasTable('users')) {
            $report['users_without_branch'] = DB::table('users')
                ->where('role', 'branch_user')
                ->whereNull('branch_id')
                ->get(['id', 'name', 'email', 'role'])
                ->all();

            $report['inactive_user_branches'] = DB::table('users')
                ->leftJoin('branches', 'branches.id', '=', 'users.branch_id')
                ->where('users.role', 'branch_user')
                ->whereNotNull('users.branch_id')
                ->where(function ($q) {
                    $q->whereNull('branches.id')->orWhere('branches.is_active', false);
                })
                ->get(['users.id', 'users.email', 'users.branch_id'])
                ->all();
        }

        foreach ($this->businessTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            $report['null_branch_counts'][$table] = (int) DB::table($table)->whereNull('branch_id')->count();
        }

        if (Schema::hasTable('sales') && Schema::hasTable('customers')) {
            $report['cross_branch_mismatches']['sales_customers'] = (int) DB::table('sales')
                ->join('customers', 'customers.id', '=', 'sales.customer_id')
                ->whereColumn('sales.branch_id', '!=', 'customers.branch_id')
                ->count();
        }

        if (Schema::hasTable('supplier_bills') && Schema::hasTable('suppliers')) {
            $report['cross_branch_mismatches']['bills_suppliers'] = (int) DB::table('supplier_bills')
                ->join('suppliers', 'suppliers.id', '=', 'supplier_bills.supplier_id')
                ->whereColumn('supplier_bills.branch_id', '!=', 'suppliers.branch_id')
                ->count();
        }

        if (Schema::hasTable('products') && Schema::hasTable('branch_product_stocks')) {
            $report['products_without_membership'] = (int) DB::table('products')
                ->leftJoin('branch_product_stocks', 'branch_product_stocks.product_id', '=', 'products.id')
                ->whereNull('branch_product_stocks.id')
                ->count();
        }

        if (Schema::hasTable('branches')) {
            $branches = DB::table('branches')->orderBy('id')->get(['id', 'name']);
            foreach ($branches as $branch) {
                $baseline = [
                    'branch_id' => $branch->id,
                    'name' => $branch->name,
                    'customers' => Schema::hasTable('customers') ? (int) DB::table('customers')->where('branch_id', $branch->id)->count() : 0,
                    'suppliers' => Schema::hasTable('suppliers') ? (int) DB::table('suppliers')->where('branch_id', $branch->id)->count() : 0,
                    'sales' => Schema::hasTable('sales') ? (int) DB::table('sales')->where('branch_id', $branch->id)->count() : 0,
                    'sales_total' => Schema::hasTable('sales') ? (float) DB::table('sales')->where('branch_id', $branch->id)->sum('total_amount') : 0,
                    'stock_rows' => Schema::hasTable('branch_product_stocks') ? (int) DB::table('branch_product_stocks')->where('branch_id', $branch->id)->count() : 0,
                    'stock_qty' => Schema::hasTable('branch_product_stocks') ? (float) DB::table('branch_product_stocks')->where('branch_id', $branch->id)->sum('stock_quantity') : 0,
                ];
                $report['baselines'][] = $baseline;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Branch isolation audit (read-only)');
        $this->line('Users without branch: '.count($report['users_without_branch']));
        $this->line('Users on missing/inactive branches: '.count($report['inactive_user_branches']));
        $this->line('Products without membership: '.$report['products_without_membership']);
        $this->table(['Table', 'Null branch_id'], collect($report['null_branch_counts'])->map(fn ($c, $t) => [$t, $c])->all());
        $this->table(
            ['Branch', 'Customers', 'Suppliers', 'Sales', 'Sales total', 'Stock rows', 'Stock qty'],
            collect($report['baselines'])->map(fn ($b) => [
                $b['name'], $b['customers'], $b['suppliers'], $b['sales'], $b['sales_total'], $b['stock_rows'], $b['stock_qty'],
            ])->all()
        );

        foreach ($report['cross_branch_mismatches'] as $label => $count) {
            $this->line("Mismatch {$label}: {$count}");
        }

        return self::SUCCESS;
    }
}
