<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
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
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            if (!Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->foreignId('branch_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('branches')
                        ->nullOnDelete();
                });
            }

            DB::table($table)->whereNull('branch_id')->update(['branch_id' => 1]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('branch_id');
            });
        }
    }
};
