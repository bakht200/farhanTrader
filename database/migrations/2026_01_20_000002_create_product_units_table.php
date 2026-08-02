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
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->boolean('is_base_unit')->default(false)->comment('Only one base unit per product');
            $table->decimal('selling_price', 10, 2)->nullable()->comment('Price per unit for this specific unit');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique product-unit pairs
            $table->unique(['product_id', 'unit_id']);
            
            // Index for faster lookups
            $table->index(['product_id', 'is_base_unit']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};

