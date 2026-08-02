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
            $table->string('slug')->nullable()->after('name');
            $table->string('selling_type')->nullable()->after('unit_id');
            $table->string('total_units')->nullable()->after('selling_type');
            $table->string('supplier_name')->nullable()->after('description');
            $table->string('supplier_phone')->nullable()->after('supplier_name');
            $table->enum('product_type', ['single', 'variant'])->default('single')->after('supplier_phone');
            $table->string('quantity_alert')->nullable()->after('product_type');
            $table->string('manufacturer')->nullable()->after('low_stock_threshold');
            $table->date('manufactured_date')->nullable()->after('manufacturer');
            $table->date('expiry_date')->nullable()->after('manufactured_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'slug', 'selling_type', 'total_units', 'supplier_name', 
                'supplier_phone', 'product_type', 'quantity_alert',
                'manufacturer', 'manufactured_date', 'expiry_date'
            ]);
        });
    }
};
