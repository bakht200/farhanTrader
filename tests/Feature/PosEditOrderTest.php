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
            ->assertSee((string) $sale->id, false);
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
        $this->assertStringContainsString('/sales/pos/edit-order', $pos);
        $this->assertStringContainsString('edit_order_id', $pos);
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
