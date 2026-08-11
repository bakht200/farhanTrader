<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('open'); // open | closed
            $table->decimal('total_investment', 15, 2)->nullable();
            $table->decimal('revenue', 15, 2)->nullable();
            $table->decimal('gross_profit', 15, 2)->nullable();
            $table->decimal('total_expenses', 15, 2)->nullable();
            $table->decimal('net_profit', 15, 2)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'year', 'month']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('branch_share_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_share_id')->constrained('branch_shares')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('share_percent', 8, 4)->nullable();
            $table->decimal('profit_share', 15, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_share_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_share_investments');
        Schema::dropIfExists('branch_shares');
    }
};
