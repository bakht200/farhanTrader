<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'extra_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('extra_price', 15, 2)->default(0)->after('wholesale_price');
            });
        }

        if (! Schema::hasColumn('branch_product_stocks', 'extra_price')) {
            Schema::table('branch_product_stocks', function (Blueprint $table) {
                $table->decimal('extra_price', 15, 2)->nullable()->after('wholesale_price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'extra_price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('extra_price');
            });
        }

        if (Schema::hasColumn('branch_product_stocks', 'extra_price')) {
            Schema::table('branch_product_stocks', function (Blueprint $table) {
                $table->dropColumn('extra_price');
            });
        }
    }
};
