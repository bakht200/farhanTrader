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
        Schema::table('unit_conversions', function (Blueprint $table) {
            // Update conversion_factor to support 2 decimal places
            $table->decimal('conversion_factor', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_conversions', function (Blueprint $table) {
            // Revert back to 6 decimal places
            $table->decimal('conversion_factor', 15, 6)->change();
        });
    }
};

