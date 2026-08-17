<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $businessTables = [
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
    ];

    public function up(): void
    {
        foreach (['sale_items', 'order_items', 'supplier_bill_items'] as $table) {
            $this->enforceNotNullRestrict($table);
        }

        foreach ($this->businessTables as $table) {
            $this->enforceNotNullRestrict($table);
        }
    }

    public function down(): void
    {
        // Non-null / restrict enforcement is not reversed automatically.
    }

    private function enforceNotNullRestrict(string $table, string $column = 'branch_id'): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $remaining = DB::table($table)->whereNull($column)->count();
        if ($remaining > 0) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->dropForeign([$column]);
        });

        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NOT NULL");

        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->foreign($column)->references('id')->on('branches')->restrictOnDelete();
        });
    }
};
