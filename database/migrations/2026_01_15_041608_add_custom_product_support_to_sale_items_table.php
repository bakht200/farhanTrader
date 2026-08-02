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
            // Drop foreign key constraint first
            $table->dropForeign(['product_id']);
        });
        
        Schema::table('sale_items', function (Blueprint $table) {
            // Make product_id nullable to support custom products
            $table->unsignedBigInteger('product_id')->nullable()->change();
            // Add product_name for custom products
            $table->string('product_name')->nullable()->after('product_id');
        });
        
        Schema::table('sale_items', function (Blueprint $table) {
            // Re-add foreign key constraint (only for non-null values)
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Drop foreign key constraint
            $table->dropForeign(['product_id']);
        });
        
        Schema::table('sale_items', function (Blueprint $table) {
            // Remove product_name
            $table->dropColumn('product_name');
            // Make product_id required again (note: this might fail if there are null values)
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
        
        Schema::table('sale_items', function (Blueprint $table) {
            // Re-add foreign key constraint
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};
