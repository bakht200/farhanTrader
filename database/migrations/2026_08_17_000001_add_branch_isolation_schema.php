<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $indexedTables = [
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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('branch_id');
            });
        }

        if (! Schema::hasTable('inventory_movements')) {
            Schema::create('inventory_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->decimal('delta', 15, 6);
                $table->decimal('qty_before', 15, 6);
                $table->decimal('qty_after', 15, 6);
                $table->string('source_type', 64)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('idempotency_key')->nullable()->unique();
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'product_id', 'created_at'], 'inv_mov_branch_product_created_idx');
                $table->index(['source_type', 'source_id'], 'inv_mov_source_idx');
            });
        }

        $this->addBranchIdColumn('sale_items', 'sale_id', 'sales');
        $this->addBranchIdColumn('order_items', 'order_id', 'orders');
        $this->addBranchIdColumn('supplier_bill_items', 'supplier_bill_id', 'supplier_bills');
        $this->addBranchIdColumn('branch_share_investments', 'branch_share_id', 'branch_shares');

        if (Schema::hasTable('sync_id_mappings') && ! Schema::hasColumn('sync_id_mappings', 'branch_id')) {
            Schema::table('sync_id_mappings', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
                $table->index(['branch_id', 'client_uuid'], 'sync_map_branch_uuid_idx');
            });
        }

        if (Schema::hasTable('user_activity_logs') && ! Schema::hasColumn('user_activity_logs', 'branch_id')) {
            Schema::table('user_activity_logs', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('user_id')->constrained('branches')->nullOnDelete();
                $table->index(['branch_id', 'created_at'], 'ual_branch_created_idx');
            });
        }

        foreach ($this->indexedTables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            $indexName = $table.'_branch_created_idx';
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->index(['branch_id', 'created_at'], $indexName);
            });
        }

        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'customer_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->index(['branch_id', 'customer_id'], 'sales_branch_customer_idx');
            });
        }

        if (Schema::hasTable('supplier_bills') && Schema::hasColumn('supplier_bills', 'supplier_id')) {
            Schema::table('supplier_bills', function (Blueprint $table) {
                $table->index(['branch_id', 'supplier_id'], 'supplier_bills_branch_supplier_idx');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexedTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table.'_branch_created_idx');
            });
        }

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropIndex('sales_branch_customer_idx');
            });
        }

        if (Schema::hasTable('supplier_bills')) {
            Schema::table('supplier_bills', function (Blueprint $table) {
                $table->dropIndex('supplier_bills_branch_supplier_idx');
            });
        }

        $this->dropBranchIdColumn('sale_items');
        $this->dropBranchIdColumn('order_items');
        $this->dropBranchIdColumn('supplier_bill_items');
        $this->dropBranchIdColumn('branch_share_investments');

        if (Schema::hasTable('sync_id_mappings') && Schema::hasColumn('sync_id_mappings', 'branch_id')) {
            Schema::table('sync_id_mappings', function (Blueprint $table) {
                $table->dropIndex('sync_map_branch_uuid_idx');
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        if (Schema::hasTable('user_activity_logs') && Schema::hasColumn('user_activity_logs', 'branch_id')) {
            Schema::table('user_activity_logs', function (Blueprint $table) {
                $table->dropIndex('ual_branch_created_idx');
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        Schema::dropIfExists('inventory_movements');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }

    private function addBranchIdColumn(string $table, string $parentKey, string $parentTable): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreignId('branch_id')->nullable()->constrained('branches')->restrictOnDelete();
        });

        DB::statement("UPDATE {$table} SET branch_id = (SELECT branch_id FROM {$parentTable} WHERE {$parentTable}.id = {$table}.{$parentKey})");
    }

    private function dropBranchIdColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropConstrainedForeignId('branch_id');
        });
    }
};
