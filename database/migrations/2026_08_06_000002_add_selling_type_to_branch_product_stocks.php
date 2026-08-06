<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table) {
            $table->string('selling_type', 32)->nullable()->after('stock_quantity');
        });

        // Backfill from global products.selling_type so each branch keeps current behavior
        DB::table('branch_product_stocks')
            ->join('products', 'products.id', '=', 'branch_product_stocks.product_id')
            ->update([
                'branch_product_stocks.selling_type' => DB::raw("COALESCE(NULLIF(products.selling_type, ''), 'both')"),
            ]);
    }

    public function down(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table) {
            $table->dropColumn('selling_type');
        });
    }
};
