<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class PosEditOrderTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_pending_order_edit_embeds_items_on_pos(): void
    {
        [$user, $sale] = $this->makePendingSale();

        $this->actingAs($user)
            ->get(route('sales.pos.index', ['edit_order_id' => $sale->id]))
            ->assertOk()
            ->assertSee('CITRIC MOTA', false)
            ->assertSee((string) $sale->id, false)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_pos_edit_order_json_returns_the_sale_items(): void
    {
        [$user, $sale] = $this->makePendingSale();

        $this->actingAs($user)
            ->getJson(route('sales.pos.edit-order', $sale->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.id', $sale->id)
            ->assertJsonPath('order.items.0.product_name', 'CITRIC MOTA');
    }

    public function test_pos_page_fetches_edit_order_when_shell_is_blank(): void
    {
        $pos = file_get_contents(resource_path('views/pos/index.blade.php'));

        $this->assertStringContainsString('resolveEditOrderFromUrl', $pos);
        $this->assertStringContainsString('urlEditOrderId', $pos);
        $this->assertStringContainsString('/sales/pos/edit-order', $pos);
        $this->assertStringContainsString('edit_order_id', $pos);
        $this->assertStringContainsString('rebuildEditStockCredits', $pos);
        $this->assertStringContainsString('getEditStockCreditForItem', $pos);
        $this->assertStringContainsString('stock_was_deducted', $pos);
        $this->assertStringContainsString('edit_original_base_qty', $pos);
        $this->assertStringContainsString('current_stock', $pos);
    }

    public function test_edit_order_payload_marks_completed_sale_stock_credits(): void
    {
        [$user, $sale] = $this->makePendingSale();

        $this->actingAs($user)
            ->getJson(route('sales.pos.edit-order', $sale->id))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('order.stock_was_deducted', true)
            ->assertJsonPath('order.status', 'completed')
            ->assertJsonPath('order.items.0.quantity_in_base_unit', 2);
    }

    public function test_edit_order_payload_includes_base_unit_for_stock_checks(): void
    {
        $branch = $this->makeBranch('POS Edit Units');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'KEER DABA EDIT'], 1);
        $pcs = \App\Models\Unit::factory()->create(['name' => 'Pieces', 'short_name' => 'PCS']);
        $product->update(['base_unit_id' => $product->unit_id]);
        \App\Models\ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'is_base_unit' => true,
            'selling_price' => 100,
            'is_active' => true,
        ]);
        \App\Models\ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $pcs->id,
            'is_base_unit' => false,
            'selling_price' => 1,
            'is_active' => true,
        ]);

        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'total_amount' => 150,
            'paid_amount' => 0,
            'payment_status' => 'pending',
            'status' => 'completed',
        ]);
        SaleItem::create([
            'branch_id' => $branch->id,
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 150,
            'quantity_in_base_unit' => 1,
            'unit_id' => $pcs->id,
            'unit_price' => 1,
            'discount' => 0,
            'tax' => 0,
            'total' => 150,
        ]);

        $this->actingAs($user)
            ->getJson(route('sales.pos.edit-order', $sale->id))
            ->assertOk()
            ->assertJsonPath('order.items.0.base_unit_id', $product->base_unit_id ?? $product->unit_id)
            ->assertJsonPath('order.items.0.quantity', 150)
            ->assertJsonPath('order.items.0.quantity_in_base_unit', 1)
            ->assertJsonPath('order.items.0.current_stock', 1);
    }

    /**
     * @return array{0: \App\Models\User, 1: Sale}
     */
    protected function makePendingSale(): array
    {
        $branch = $this->makeBranch('POS Edit Shop');
        $user = $this->makeBranchUser($branch);
        $customer = $this->makeCustomerForBranch($branch, ['name' => 'Edit Customer']);

        $sale = Sale::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'sale_number' => 'SALE-013013',
            'total_amount' => 1000,
            'paid_amount' => 0,
            'payment_status' => 'pending',
            'status' => 'completed',
        ]);

        SaleItem::create([
            'branch_id' => $branch->id,
            'sale_id' => $sale->id,
            'product_id' => null,
            'product_name' => 'CITRIC MOTA',
            'quantity' => 2,
            'quantity_in_base_unit' => 2,
            'unit_price' => 500,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,
        ]);

        return [$user, $sale];
    }
}
