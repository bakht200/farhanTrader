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
        Schema::create('product_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('supplier_bill_id')->nullable()->constrained()->onDelete('set null');
            $table->string('type'); // 'quantity_added', 'price_updated', 'created'
            $table->integer('quantity_added')->default(0);
            $table->decimal('old_price', 15, 2)->nullable();
            $table->decimal('new_price', 15, 2)->nullable();
            $table->integer('old_stock_quantity')->nullable();
            $table->integer('new_stock_quantity')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_history');
    }
};
