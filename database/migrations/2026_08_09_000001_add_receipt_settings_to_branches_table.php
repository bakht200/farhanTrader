<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('receipt_title')->nullable()->after('is_active');
            $table->string('receipt_subtitle')->nullable()->after('receipt_title');
            $table->string('receipt_phone')->nullable()->after('receipt_subtitle');
            $table->string('receipt_mobile_1')->nullable()->after('receipt_phone');
            $table->string('receipt_mobile_2')->nullable()->after('receipt_mobile_1');
            $table->string('receipt_email')->nullable()->after('receipt_mobile_2');
            $table->string('receipt_address', 500)->nullable()->after('receipt_email');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'receipt_title',
                'receipt_subtitle',
                'receipt_phone',
                'receipt_mobile_1',
                'receipt_mobile_2',
                'receipt_email',
                'receipt_address',
            ]);
        });
    }
};
