<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ProductLot;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ProfitReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

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

    public function test_gross_profit_prefers_sold_lot_purchase_price_like_pos(): void
    {
        $branch = $this->makeBranch('Meeta Profit '.uniqid());
        $user = $this->makeBranchUser($branch);
        $this->actingAs($user);

        // Catalog rate differs from the lot POS showed as Pur.Price.
        $product = $this->makeProductForBranch($branch, [
            'name' => 'MEETA SODA 25kg',
            'purchase_price' => 3170,
            'selling_price' => 3200,
            'retail_price' => 3200,
        ], 5);

        $lot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'purchase_price' => 3070,
            'retail_price' => 3200,
            'wholesale_price' => 3200,
            'selling_price' => 3200,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'sale_number' => 'SALE-AS000196',
            'sale_date' => '2026-10-01',
            'subtotal' => 3200,
            'total_amount' => 3200,
            'paid_amount' => 3200,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'product_lot_id' => $lot->id,
            'product_name' => 'MEETA SODA 25kg',
            'quantity' => 1,
            'quantity_in_base_unit' => 1,
            'unit_price' => 3200,
            'discount' => 0,
            'total' => 3200,
        ]);

        $service = app(ProfitReportService::class);
        $summary = $service->summarize(
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-01'),
            $branch->id,
        );

        // Same as POS: sell 3200 - lot cost 3070 = 130 (not catalog 3170 → 30).
        $this->assertEquals(3200.0, $summary['revenue']);
        $this->assertEquals(130.0, $summary['gross_profit']);
        $this->assertEquals(130.0, $summary['net_profit']);

        $bills = $service->bills(
            Carbon::parse('2026-10-01'),
            Carbon::parse('2026-10-01'),
            $branch->id,
        );

        $this->assertCount(1, $bills);
        $this->assertEquals(130.0, (float) $bills->first()->profit);
    }

    public function test_profit_loss_page_shows_bill_profit_column(): void
    {
        $branch = $this->makeBranch('Report UI '.uniqid());
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Report Widget',
            'purchase_price' => 100,
            'selling_price' => 150,
        ], 3);

        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'sale_number' => 'SALE-RP000001',
            'sale_date' => now()->toDateString(),
            'subtotal' => 150,
            'total_amount' => 150,
            'paid_amount' => 150,
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'quantity_in_base_unit' => 1,
            'unit_price' => 150,
            'discount' => 0,
            'total' => 150,
        ]);

        $this->actingAs($user)
            ->get(route('reports.profit-loss', [
                'mode' => 'daily',
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Profit')
            ->assertSee('SALE-RP000001')
            ->assertSee('PKR 50');
    }
}
