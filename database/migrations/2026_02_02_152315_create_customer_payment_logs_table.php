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
        Schema::create('customer_payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('log_type'); // 'payment', 'invoice_change', 'cash_received'
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference_number')->nullable(); // sale_number or invoice_number
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('previous_amount', 10, 2)->nullable(); // For invoice changes
            $table->decimal('new_amount', 10, 2)->nullable(); // For invoice changes
            $table->string('payment_status')->nullable(); // paid, partial, pending
            $table->text('description')->nullable();
            $table->text('changes')->nullable(); // JSON for invoice changes
            $table->timestamps();
            
            $table->index(['customer_id', 'created_at']);
            $table->index(['sale_id']);
            $table->index(['invoice_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_logs');
    }
};
