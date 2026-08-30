<?php

namespace App\Console\Commands;

use App\Services\ProductLotService;
use Illuminate\Console\Command;

class RepairLotExtraPrices extends Command
{
    protected $signature = 'lots:repair-extra {--branch= : Branch id (defaults to none = all branches)}';

    protected $description = 'Fix product lots whose extra_price is out of sync with the product catalog (e.g. bad bill entry).';

    public function handle(ProductLotService $lots): int
    {
        $branchId = $this->option('branch');
        $branchId = $branchId !== null && $branchId !== '' ? (int) $branchId : null;

        $fixed = $lots->repairStaleLotExtraPrices($branchId);

        $this->info("Repaired {$fixed} product lot(s).");

        return self::SUCCESS;
    }
}
