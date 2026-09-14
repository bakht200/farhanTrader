<?php

namespace Tests\Feature;

use App\Models\CustomerPaymentLog;
use App\Models\Sale;
use App\Services\CustomerBalanceService;
use App\Services\RepairIqbalKhanBakerySep2PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class RepairIqbalKhanBakerySep2PaymentTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_repair_applies_the_missing_197000_and_is_idempotent(): void
    {
        [$customer, $sale] = $this->seedIqbalLedger();

        $repairer = app(RepairIqbalKhanBakerySep2PaymentService::class);

        $dry = $repairer->repair(true);
        $this->assertSame('dry_run', $dry['status']);
        $this->assertEquals(173460.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-008624')->value('paid_amount'));

        $first = $repairer->repair(false);
        $this->assertSame('repaired', $first['status']);
        $this->assertEquals(71085.0, $first['extra_applied']);

        $this->assertEquals(191750.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-008624')->value('paid_amount'));
        $this->assertEquals(49210.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-008625')->value('paid_amount'));
        $this->assertEquals(3585.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-008626')->value('paid_amount'));
        $this->assertEquals(160000.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-010513')->value('paid_amount'));

        $this->assertTrue(
            CustomerPaymentLog::withoutGlobalScopes()
                ->where('sale_id', $sale->id)
                ->where('log_type', 'cash_received')
                ->where('amount', 125915)
                ->exists()
        );

        $this->assertEquals(
            85135.0,
            app(CustomerBalanceService::class)->calculateCustomerBalanceSummary($customer->id)['unpaid_amount']
        );

        $second = $repairer->repair(false);
        $this->assertSame('already', $second['status']);
        $this->assertEquals(3585.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-008626')->value('paid_amount'));
    }

    public function test_artisan_commit_repairs_the_sale(): void
    {
        $this->seedIqbalLedger();

        $this->assertSame(0, Artisan::call('sales:repair-iqbal-khan-bakery-sep2', [
            '--commit' => true,
            '--force' => true,
        ]));

        $this->assertEquals(191750.0, (float) Sale::withoutGlobalScopes()->where('sale_number', 'SALE-008624')->value('paid_amount'));
    }

    /**
     * @return array{0: \App\Models\Customer, 1: Sale}
     */
    protected function seedIqbalLedger(): array
    {
        $branch = $this->makeBranch('Iqbal Repair Shop');
        $user = $this->makeBranchUser($branch);
        $customer = $this->makeCustomerForBranch($branch, [
            'name' => RepairIqbalKhanBakerySep2PaymentService::CUSTOMER_NAME,
        ]);

        $bills = [
            ['SALE-008624', 191750, 173460, 'partial', '2026-08-09 14:36:58'],
            ['SALE-008625', 49210, 0, 'partial', '2026-08-09 14:45:18'],
            ['SALE-008626', 33800, 0, 'partial', '2026-08-09 14:57:04'],
            ['SALE-009879', 125915, 125915, 'paid', '2026-09-02 11:45:37'],
            ['SALE-010513', 214920, 160000, 'partial', '2026-09-13 15:20:24'],
        ];

        $sep2 = null;
        foreach ($bills as [$number, $total, $paid, $status, $at]) {
            $sale = Sale::factory()->create([
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'user_id' => $user->id,
                'sale_number' => $number,
                'sale_date' => substr($at, 0, 10),
                'subtotal' => $total,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'payment_status' => $status,
                'status' => 'completed',
                'created_at' => $at,
                'updated_at' => $at,
            ]);

            if ($number === RepairIqbalKhanBakerySep2PaymentService::SALE_NUMBER) {
                $sep2 = $sale;
            }
        }

        return [$customer, $sep2];
    }
}
