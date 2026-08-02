<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the legacy unique key on (from_unit_id, to_unit_id) which still
 * exists in some installations even after migration
 * 2026_01_22_160059_add_product_id_to_unit_conversions_table.php tried to
 * drop it: that migration's drop is wrapped in try/catch and silently
 * swallowed the failure when MySQL refused because the from_unit_id
 * foreign key was relying on this index.
 *
 * We add a separate plain index on from_unit_id first so the FK has an
 * index to use, then drop the legacy unique safely.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('unit_conversions')) {
            return;
        }

        $hasFromIndex = DB::select(
            "SHOW INDEXES FROM unit_conversions WHERE Key_name = 'unit_conversions_from_unit_id_index'"
        );
        if (empty($hasFromIndex)) {
            try {
                Schema::table('unit_conversions', function (Blueprint $table) {
                    $table->index('from_unit_id', 'unit_conversions_from_unit_id_index');
                });
            } catch (\Throwable $e) {
                \Log::warning('Could not add from_unit_id index, may already exist via different name', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $hasLegacyUnique = DB::select(
            "SHOW INDEXES FROM unit_conversions WHERE Key_name = 'unit_conversions_from_unit_id_to_unit_id_unique'"
        );
        if (!empty($hasLegacyUnique)) {
            DB::statement('ALTER TABLE unit_conversions DROP INDEX unit_conversions_from_unit_id_to_unit_id_unique');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('unit_conversions')) {
            return;
        }

        $hasLegacyUnique = DB::select(
            "SHOW INDEXES FROM unit_conversions WHERE Key_name = 'unit_conversions_from_unit_id_to_unit_id_unique'"
        );
        if (empty($hasLegacyUnique)) {
            try {
                Schema::table('unit_conversions', function (Blueprint $table) {
                    $table->unique(['from_unit_id', 'to_unit_id'], 'unit_conversions_from_unit_id_to_unit_id_unique');
                });
            } catch (\Throwable $e) {
                // Cannot recreate (e.g. duplicate pairs across products). Acceptable for down().
            }
        }

        $hasFromIndex = DB::select(
            "SHOW INDEXES FROM unit_conversions WHERE Key_name = 'unit_conversions_from_unit_id_index'"
        );
        if (!empty($hasFromIndex)) {
            try {
                Schema::table('unit_conversions', function (Blueprint $table) {
                    $table->dropIndex('unit_conversions_from_unit_id_index');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};
