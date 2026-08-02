<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearDatabaseExceptUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear-except-users {--force : Force the operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all database tables except users table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to clear all database tables except users? This action cannot be undone!')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting database cleanup...');

        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // List of tables to clear in correct order (child tables first, then parent tables)
        // This prevents foreign key constraint errors
        $tablesToClear = [
            // Step 1: Child tables (tables with foreign keys)
            'sale_items',
            'order_items',
            'supplier_bill_items',
            'product_units',
            'unit_conversions',
            'product_history',
            'supplier_transactions',
            'user_activity_logs',
            // Step 2: Intermediate tables
            'invoices',
            'sales_returns',
            'sales',
            'orders',
            'supplier_bills',
            // Step 3: Main entity tables
            'products',
            'customers',
            'suppliers',
            'categories',
            'units',
            'expenses',
        ];

        $clearedCount = 0;
        foreach ($tablesToClear as $table) {
            if (Schema::hasTable($table)) {
                try {
                    DB::table($table)->truncate();
                    $this->info("✓ Cleared: {$table}");
                    $clearedCount++;
                } catch (\Exception $e) {
                    $this->error("✗ Failed to clear {$table}: " . $e->getMessage());
                }
            } else {
                $this->warn("⚠ Table {$table} does not exist, skipping...");
            }
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // Verify users table
        $userCount = DB::table('users')->count();
        $this->info("\n✓ Users table preserved with {$userCount} user(s)");

        $this->info("\n✓ Database cleanup completed! {$clearedCount} table(s) cleared.");
        return 0;
    }
}

