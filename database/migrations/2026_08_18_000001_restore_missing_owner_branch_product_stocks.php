<?php

use App\Services\BranchStockService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(BranchStockService::class)->restoreMissingOwnerMembership();
    }

    public function down(): void
    {
        // Data restore is not reversed.
    }
};
