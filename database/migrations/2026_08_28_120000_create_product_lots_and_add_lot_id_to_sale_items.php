<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_lots')) {
            Schema::create('product_lots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->unsignedBigInteger('supplier_bill_id')->nullable();
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->decimal('quantity', 18, 6)->default(0);
                $table->decimal('purchase_price', 15, 2)->default(0);
                $table->decimal('extra_price', 15, 2)->default(0);
                $table->decimal('retail_price', 15, 2)->default(0);
                $table->decimal('wholesale_price', 15, 2)->default(0);
                $table->decimal('selling_price', 15, 2)->default(0);
                $table->string('selling_type', 20)->default('both');
                $table->timestamp('received_at')->nullable();
                $table->timestamps();

                $table->index(['branch_id', 'product_id']);
                $table->index(['product_id', 'quantity']);
            });
        }

        if (! Schema::hasColumn('sale_items', 'product_lot_id')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_lot_id')->nullable()->after('product_id');
            });
        }

        if (! Schema::hasColumn('supplier_bill_items', 'product_lot_id')) {
            Schema::table('supplier_bill_items', function (Blueprint $table) {
                $table->unsignedBigInteger('product_lot_id')->nullable()->after('product_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_bill_items', 'product_lot_id')) {
            Schema::table('supplier_bill_items', function (Blueprint $table) {
                $table->dropColumn('product_lot_id');
            });
        }
        if (Schema::hasColumn('sale_items', 'product_lot_id')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropColumn('product_lot_id');
            });
        }
        Schema::dropIfExists('product_lots');
    }
};
