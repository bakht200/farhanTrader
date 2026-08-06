<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_id_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('client_uuid')->unique();
            $table->string('entity', 64);
            $table->string('server_id', 64);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['entity', 'server_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_id_mappings');
    }
};
