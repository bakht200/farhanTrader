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
        // Check if product_id column already exists
        if (!Schema::hasColumn('unit_conversions', 'product_id')) {
            Schema::table('unit_conversions', function (Blueprint $table) {
                $table->foreignId('product_id')->nullable()->after('id')->constrained('products')->onDelete('cascade');
            });
        }
        
        // Drop old unique constraint if it exists
        try {
            DB::statement('ALTER TABLE unit_conversions DROP INDEX unit_conversions_from_unit_id_to_unit_id_unique');
        } catch (\Exception $e) {
            // Index might not exist or have different name, continue
        }
        
        // Check if new unique constraint already exists
        $indexes = DB::select("SHOW INDEXES FROM unit_conversions WHERE Key_name = 'unit_conversions_product_units_unique'");
        if (empty($indexes)) {
            Schema::table('unit_conversions', function (Blueprint $table) {
                // Add new unique constraint including product_id
                $table->unique(['product_id', 'from_unit_id', 'to_unit_id'], 'unit_conversions_product_units_unique');
            });
        }
        
        // Add index for faster lookups if it doesn't exist
        $productIdIndexes = DB::select("SHOW INDEXES FROM unit_conversions WHERE Column_name = 'product_id' AND Key_name != 'unit_conversions_product_units_unique'");
        if (empty($productIdIndexes)) {
            Schema::table('unit_conversions', function (Blueprint $table) {
                $table->index('product_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_conversions', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique('unit_conversions_product_units_unique');
            
            // Drop index
            $table->dropIndex(['product_id']);
            
            // Drop foreign key and column
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            
            // Restore old unique constraint
            $table->unique(['from_unit_id', 'to_unit_id']);
        });
    }
};
