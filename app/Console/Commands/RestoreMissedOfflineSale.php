<?php

namespace App\Console\Commands;

use App\Services\RestoreMissedOfflineSaleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreMissedOfflineSale extends Command
{
    protected $signature = 'sales:restore-missed-offline-sale
                            {--file= : JSON payload path}
                            {--commit : Write the sale}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Restore the 13 Aug PESHAWAR CHEMICAL customer bill that offline sync never committed.';

    public function handle(RestoreMissedOfflineSaleService $restorer): int
    {
        $path = $this->option('file')
            ?: database_path('data/missed_offline_sale_ead992f0.json');

        if (! File::exists($path)) {
            $this->error("Payload file not found: {$path}");

            return self::FAILURE;
        }

        $record = json_decode(File::get($path), true);
        if (! is_array($record)) {
            $this->error("Payload file is not valid JSON: {$path}");

            return self::FAILURE;
        }

        $this->info('Customer: '.($record['customer_name'] ?? '').' (#'.($record['customer_id'] ?? '').')');
        $this->info('Date: '.($record['sale_date'] ?? ''));
        $this->info('UUID: '.($record['client_uuid'] ?? ''));
        $this->info('Lines: '.count($record['items'] ?? []));
        $this->info('Total: PKR '.number_format((float) ($record['expected_total'] ?? 0), 2));

        $dryRun = ! $this->option('commit');

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('Create this historical sale on the customer ledger?')) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        try {
            $result = $restorer->restore($record, $dryRun);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line($result['message']);

        if ($result['sale_number']) {
            $this->info('Sale number: '.$result['sale_number']);
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }
}
