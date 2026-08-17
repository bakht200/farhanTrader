<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Strict branch isolation
    |--------------------------------------------------------------------------
    |
    | When true, a branch can see a product only when a branch_product_stocks
    | membership row exists. When false (rollout / dual-read), membership OR
    | the legacy owner_branch_id shared-catalog rules still apply.
    |
    */

    'strict_isolation' => (bool) env('STRICT_BRANCH_ISOLATION', false),

    /*
    |--------------------------------------------------------------------------
    | Legacy default branch
    |--------------------------------------------------------------------------
    |
    | Historical branch id 1 (Phandu). Used only for sale-number formatting
    | and dual-read compatibility — never as a silent data-access fallback.
    |
    */

    'default_branch_id' => (int) env('DEFAULT_BRANCH_ID', 1),

];
