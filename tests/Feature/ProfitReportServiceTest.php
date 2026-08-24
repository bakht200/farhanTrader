<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ProfitReportService;
use Carbon\Carbon;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProfitReportServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_gross_profit_uses_base_unit_cost_not_sold_quantity(): void
    {
        $branch = $this->makeBranch('Gul Test '.uniqid());
        $user = $this->makeBranchUser($branch);
        $this->actingAs($user);

        $product = $this->makeProductForBranch($branch, [
            'name' => 'RED PHALI (40KG)',
            'purchase_price' => 25840,
            'selling_price' => 27334,
        ], 10);

        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'sale_number' => 'SALE-GT000001',
            'sale_date' => '2026-08-20',
            'subtotal' => 8200.08,
            'total_amount' => 8200.08,
            'paid_amount' => 8200.08,
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'product_name' => 'RED PHALI (40KG)',
            'quantity' => 12,
            'quantity_in_base_unit' => 0.3,
            'unit_price' => 683.34,
            'discount' => 0,
            'total' => 8200.08,
        ]);

        Expense::query()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'name' => 'LUNCH',
            'expense_date' => '2026-08-20',
            'amount' => 200,
        ]);

        $summary = app(ProfitReportService::class)->summarize(
            Carbon::parse('2026-08-01'),
            Carbon::parse('2026-08-31'),
            $branch->id,
        );

        $this->assertEquals(8200.08, $summary['revenue']);
        $this->assertEquals(1, $summary['bill_count']);
        $this->assertEquals(200.0, $summary['total_expenses']);
        // 12 KG * 683.34 - 0.3 bag * 25840 = 448.08, not (683.34 - 25840) * 12
        $this->assertEquals(448.08, $summary['gross_profit']);
        $this->assertEquals(248.08, $summary['net_profit']);
        $this->assertGreaterThan(0, $summary['net_profit']);
    }
}
