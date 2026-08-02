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
        Schema::table('sale_items', function (Blueprint $table) {
            // Add unit_id column first
            $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->onDelete('set null');
            // Add quantity_in_base_unit column
            $table->decimal('quantity_in_base_unit', 15, 6)->nullable()->after('quantity')->comment('Quantity converted to product base unit');
        });
        
        // Change quantity column type separately (requires doctrine/dbal)
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('quantity', 15, 6)->change()->comment('Quantity in the unit specified by unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['unit_id', 'quantity_in_base_unit']);
            $table->integer('quantity')->change();
        });
    }
};

