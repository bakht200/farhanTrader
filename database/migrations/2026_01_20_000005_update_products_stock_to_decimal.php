<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Change stock_quantity to decimal to support fractional base units
            $table->decimal('stock_quantity', 15, 6)->default(0)->change();
            
            // Add base_unit_id for clarity (keeping unit_id for backward compatibility)
            // If you want to fully migrate, you can rename unit_id to base_unit_id
            // For now, we'll add base_unit_id and sync it with unit_id
            $table->foreignId('base_unit_id')->nullable()->after('unit_id')->constrained('units')->onDelete('set null');
        });
        
        // Sync base_unit_id with unit_id for existing records
        DB::statement('UPDATE products SET base_unit_id = unit_id WHERE base_unit_id IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->dropColumn('base_unit_id');
            $table->integer('stock_quantity')->default(0)->change();
        });
    }
};

