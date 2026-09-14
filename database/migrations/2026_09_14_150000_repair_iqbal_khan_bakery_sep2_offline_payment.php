<?php

use App\Services\RepairIqbalKhanBakerySep2PaymentService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(RepairIqbalKhanBakerySep2PaymentService::class)->repair(false);
    }

    public function down(): void
    {
        // Historical cash correction is not reversed.
    }
};
