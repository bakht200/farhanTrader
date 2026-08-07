<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('owner_branch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('branches')
                ->nullOnDelete();
        });

        // Existing catalog is the shared admin catalog (branch 1)
        DB::table('products')->whereNull('owner_branch_id')->update(['owner_branch_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_branch_id');
        });
    }
};
