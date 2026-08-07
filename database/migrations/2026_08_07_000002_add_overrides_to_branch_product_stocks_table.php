<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('selling_type');
            $table->decimal('purchase_price', 15, 2)->nullable()->after('display_name');
            $table->decimal('selling_price', 15, 2)->nullable()->after('purchase_price');
            $table->decimal('retail_price', 15, 2)->nullable()->after('selling_price');
            $table->decimal('wholesale_price', 15, 2)->nullable()->after('retail_price');
        });
    }

    public function down(): void
    {
        Schema::table('branch_product_stocks', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'purchase_price',
                'selling_price',
                'retail_price',
                'wholesale_price',
            ]);
        });
    }
};
