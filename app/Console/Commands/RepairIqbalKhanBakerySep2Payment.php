<?php

namespace App\Console\Commands;

use App\Services\RepairIqbalKhanBakerySep2PaymentService;
use Illuminate\Console\Command;

class RepairIqbalKhanBakerySep2Payment extends Command
{
    protected $signature = 'sales:repair-iqbal-khan-bakery-sep2
                            {--commit : Write the missing 2 Sep cash and extra payment}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the 197,000 cash Farhan entered for Iqbal Khan Bakery SALE-009879 (offline sync had capped it at 125,915).';

    public function handle(RepairIqbalKhanBakerySep2PaymentService $repairer): int
    {
        $dryRun = ! $this->option('commit');

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Apply the missing 197,000 payment on SALE-009879?')) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $result = $repairer->repair($dryRun);

        $this->line($result['message']);

        if ($result['remaining_after'] !== null) {
            $this->info('Wallet remaining: PKR '.number_format($result['remaining_after'], 2));
        }

        return $result['status'] === 'skipped' ? self::FAILURE : self::SUCCESS;
    }
}
