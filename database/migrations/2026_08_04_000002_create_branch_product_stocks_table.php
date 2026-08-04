<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_product_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('stock_quantity', 15, 6)->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        $branchIds = DB::table('branches')->pluck('id');
        $products = DB::table('products')->select('id', 'stock_quantity')->get();
        $now = now();

        $rows = [];
        foreach ($products as $product) {
            foreach ($branchIds as $branchId) {
                $rows[] = [
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'stock_quantity' => (int) $branchId === 1
                        ? (float) $product->stock_quantity
                        : 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rows) >= 500) {
                    DB::table('branch_product_stocks')->insert($rows);
                    $rows = [];
                }
            }
        }

        if ($rows !== []) {
            DB::table('branch_product_stocks')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_product_stocks');
    }
};
