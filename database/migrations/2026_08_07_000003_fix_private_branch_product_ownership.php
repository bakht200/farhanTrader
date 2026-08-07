<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-assign products created by non-admin branch users to their branch,
     * and remove stock rows on other branches for those private products.
     */
    public function up(): void
    {
        // Branch users (not on branch 1) → private catalog for their branch
        DB::statement("
            UPDATE products
            INNER JOIN users ON users.id = products.user_id
            SET products.owner_branch_id = users.branch_id
            WHERE users.role = 'branch_user'
              AND users.branch_id IS NOT NULL
              AND users.branch_id != 1
        ");

        // Private products should not have stock rows on other branches
        DB::statement("
            DELETE bps FROM branch_product_stocks bps
            INNER JOIN products p ON p.id = bps.product_id
            WHERE p.owner_branch_id IS NOT NULL
              AND p.owner_branch_id != 1
              AND bps.branch_id != p.owner_branch_id
        ");
    }

    public function down(): void
    {
        // Irreversible data correction
    }
};
