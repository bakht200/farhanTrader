<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('suppliers', 'is_anonymous')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->boolean('is_anonymous')->default(false)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('suppliers', 'is_anonymous')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropColumn('is_anonymous');
            });
        }
    }
};
