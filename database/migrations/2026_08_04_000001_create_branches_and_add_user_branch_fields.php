<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed branch 1 as Phandu (existing system)
        DB::table('branches')->insert([
            'id' => 1,
            'name' => 'Phandu',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('branch_user')->after('password');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });

        $adminEmails = [
            'bakhtbiland@gmail.com',
            'farhan.akhtar90@yahoo.com',
            'admin@admin.com',
        ];

        DB::table('users')
            ->whereIn('email', $adminEmails)
            ->update([
                'role' => 'admin',
                'branch_id' => null,
            ]);

        DB::table('users')
            ->whereNotIn('email', $adminEmails)
            ->update([
                'role' => 'branch_user',
                'branch_id' => 1,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('branches');
    }
};
