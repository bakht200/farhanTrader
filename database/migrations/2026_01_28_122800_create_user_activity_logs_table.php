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
        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('ip_address', 45)->nullable();
            $table->string('browser')->nullable();
            $table->string('browser_version')->nullable();
            $table->string('platform')->nullable();
            $table->string('device_type')->nullable(); // desktop, mobile, tablet
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->string('method')->nullable(); // GET, POST, PUT, DELETE, etc.
            $table->string('page')->nullable(); // Page name or title
            $table->string('activity_type')->nullable(); // view, create, update, delete, login, logout, etc.
            $table->string('button')->nullable(); // Button clicked or action button
            $table->string('process')->nullable(); // Process name or description
            $table->text('description')->nullable(); // Detailed description
            $table->json('request_data')->nullable(); // Request data (sanitized)
            $table->json('response_data')->nullable(); // Response data if needed
            $table->string('status')->nullable(); // success, error, warning
            $table->integer('response_code')->nullable(); // HTTP response code
            $table->decimal('execution_time', 10, 4)->nullable(); // Time taken in seconds
            $table->date('date');
            $table->time('time');
            $table->string('session_id')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('user_id');
            $table->index('date');
            $table->index('time');
            $table->index('activity_type');
            $table->index('ip_address');
            $table->index(['date', 'time']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
