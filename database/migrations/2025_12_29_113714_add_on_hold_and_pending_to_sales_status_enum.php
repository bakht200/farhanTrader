<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // For MySQL, modify the enum
            DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('draft', 'pending', 'on_hold', 'completed', 'cancelled') DEFAULT 'draft'");
        } elseif ($driver === 'sqlite') {
            // SQLite doesn't support ENUM, so we'll just ensure the column accepts strings
            // The application logic will handle the validation
            // No action needed for SQLite as it stores as TEXT
        } else {
            // For other databases, try the MySQL syntax
            try {
                DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('draft', 'pending', 'on_hold', 'completed', 'cancelled') DEFAULT 'draft'");
            } catch (\Exception $e) {
                // If it fails, the application will handle validation
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql') {
            // Revert back to original enum values
            DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('draft', 'completed', 'cancelled') DEFAULT 'draft'");
        }
        // SQLite doesn't need rollback as it's just TEXT
    }
};
