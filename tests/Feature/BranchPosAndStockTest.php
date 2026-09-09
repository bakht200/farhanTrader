<?php

namespace Tests\Feature;

use App\Models\BranchProductStock;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class BranchPosAndStockTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_pos_checkout_decrements_only_current_branch_stock(): void
    {
        $branchA = $this->makeBranch('POS Alpha');
        $branchB = $this->makeBranch('POS Beta');
        $userA = $this->makeBranchUser($branchA);

        $product = $this->makeProductForBranch($branchA, ['name' => 'Checkout Item'], 10);
        BranchProductStock::query()->create([
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'stock_quantity' => 50,
            'selling_type' => 'retail',
        ]);

        $this->actingAs($userA);
        $response = $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 30,
            'comment' => 'Automation POS sale',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 3,
                'selling_price' => 10,
            ]],
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertEquals(7.0, (float) BranchProductStock::query()
            ->where('branch_id', $branchA->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));

        $this->assertEquals(50.0, (float) BranchProductStock::query()
            ->where('branch_id', $branchB->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));

        $this->assertDatabaseHas('sales', [
            'branch_id' => $branchA->id,
            'user_id' => $userA->id,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'source_type' => 'sale',
        ]);
    }

    public function test_pos_rejects_another_branch_product(): void
    {
        $branchA = $this->makeBranch('POS Own');
        $branchB = $this->makeBranch('POS Other');
        $userA = $this->makeBranchUser($branchA);
        $productB = $this->makeProductForBranch($branchB, ['name' => 'Foreign Item'], 9);

        $this->actingAs($userA);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 10,
            'comment' => 'Try other branch product',
            'items' => [[
                'product_id' => $productB->id,
                'quantity' => 1,
                'selling_price' => 10,
            ]],
        ])->assertStatus(422);

        $this->assertEquals(9.0, (float) BranchProductStock::query()
            ->where('branch_id', $branchB->id)
            ->where('product_id', $productB->id)
            ->value('stock_quantity'));
    }

    public function test_pos_rejects_insufficient_stock(): void
    {
        $branch = $this->makeBranch('POS Low');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Low Stock'], 1);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 50,
            'comment' => 'Oversell attempt',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'selling_price' => 10,
            ]],
        ])->assertStatus(400);

        $this->assertEquals(1.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
    }

    public function test_second_sale_cannot_oversell_remaining_stock(): void
    {
        $branch = $this->makeBranch('POS Sequential');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Last Units'], 2);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 20,
            'comment' => 'First sale takes all but one conceptually',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 10,
            'comment' => 'Second sale should fail',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'selling_price' => 10,
            ]],
        ])->assertStatus(400);

        $this->assertEquals(0.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(1, Sale::query()->count());
    }

    public function test_editing_completed_sale_does_not_double_deduct_stock(): void
    {
        $branch = $this->makeBranch('POS Edit Stock');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Edit Me'], 10);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 40,
            'comment' => 'Original sale',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $sale = Sale::query()->first();
        $this->assertEquals(6.0, (float) $product->fresh()->currentStock($branch->id));

        // Same qty edit — stock must stay 6 (restore 4, deduct 4).
        $this->postJson(route('sales.pos.process'), [
            'order_id' => $sale->id,
            'payment_method' => 'cash',
            'paid_amount' => 40,
            'comment' => 'Edit same qty',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'selling_price' => 10,
            ]],
        ])->assertOk()->assertJsonPath('is_edit', true);

        $this->assertEquals(6.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(1, Sale::query()->count());
        $this->assertEquals(1, $sale->fresh()->items()->count());

        // Increase qty by 2 — stock should become 4.
        $this->postJson(route('sales.pos.process'), [
            'order_id' => $sale->id,
            'payment_method' => 'cash',
            'paid_amount' => 60,
            'comment' => 'Edit add two',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 6,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $this->assertEquals(4.0, (float) $product->fresh()->currentStock($branch->id));

        // Reduce to 1 — stock should become 9.
        $this->postJson(route('sales.pos.process'), [
            'order_id' => $sale->id,
            'payment_method' => 'cash',
            'paid_amount' => 10,
            'comment' => 'Edit reduce',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $this->assertEquals(9.0, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(1, Sale::query()->count());
    }

    public function test_edit_allows_same_qty_when_no_free_stock_left(): void
    {
        $branch = $this->makeBranch('POS Edit Tight');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Tight Stock'], 5);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 50,
            'comment' => 'Takes all stock',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $sale = Sale::query()->first();
        $this->assertEquals(0.0, (float) $product->fresh()->currentStock($branch->id));

        $this->postJson(route('sales.pos.process'), [
            'order_id' => $sale->id,
            'payment_method' => 'cash',
            'paid_amount' => 50,
            'comment' => 'Re-save full stock sale',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $this->assertEquals(0.0, (float) $product->fresh()->currentStock($branch->id));
    }

    public function test_offline_sync_edit_does_not_double_deduct_stock(): void
    {
        $branch = $this->makeBranch('POS Offline Edit');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Offline Edit Item'], 10);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 30,
            'comment' => 'Online original',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 3,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $sale = Sale::query()->first();
        $this->assertEquals(7.0, (float) $product->fresh()->currentStock($branch->id));

        $response = $this->postJson(route('sync.push'), [
            'items' => [[
                'client_uuid' => (string) \Illuminate\Support\Str::uuid(),
                'entity' => 'sale',
                'op' => 'update',
                'branch_id' => $branch->id,
                'payload' => [
                    'order_id' => $sale->id,
                    'customer_name' => 'Walk-in Customer',
                    'payment_method' => 'cash',
                    'paid_amount' => 50,
                    'comment' => 'Offline edit bump qty',
                    'items' => [[
                        'product_id' => $product->id,
                        'is_custom' => '0',
                        'quantity' => 5,
                        'selling_price' => 10,
                        'discount' => 0,
                        'discount_type' => 'percentage',
                    ]],
                ],
            ]],
        ]);

        $response->assertOk();
        $this->assertSame('ok', $response->json('results.0.status'), json_encode($response->json('results.0')));
        $this->assertTrue((bool) $response->json('results.0.is_edit'));
        $this->assertSame($sale->id, (int) $response->json('results.0.server_id'));

        // Started 10, original sale took 3 (7 left). Edit to 5 => net -2 more => 5.
        $this->assertEquals(5.0, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(1, Sale::query()->count());
        $this->assertEquals(5.0, (float) $sale->fresh()->items()->sum('quantity'));
    }

    public function test_offline_runtime_includes_order_id_on_edit_queue(): void
    {
        $pos = file_get_contents(resource_path('views/pos/index.blade.php'));
        $outbox = file_get_contents(resource_path('js/offline/outbox.js'));

        $this->assertStringContainsString('order_id: (orderId && orderId > 0) ? orderId : null', $pos);
        $this->assertStringContainsString('previous_items:', $pos);
        $this->assertStringContainsString('applyOfflineStockDelta', $outbox);
        $this->assertStringContainsString("op: orderId > 0 ? 'update' : 'create'", $outbox);
    }

    public function test_sales_list_does_not_mix_branch_records(): void
    {
        $branchA = $this->makeBranch('Report A');
        $branchB = $this->makeBranch('Report B');
        $userA = $this->makeBranchUser($branchA);
        $userB = $this->makeBranchUser($branchB);

        $this->actingAs($userB);
        Sale::factory()->create([
            'branch_id' => $branchB->id,
            'user_id' => $userB->id,
            'sale_number' => 'SALE-RB000001',
            'notes' => 'Secret Beta Sale',
        ]);

        $this->actingAs($userA);
        $this->get(route('sales.index'))
            ->assertOk()
            ->assertDontSee('SALE-RB000001')
            ->assertDontSee('Secret Beta Sale');
    }

    public function test_admin_write_without_selected_branch_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin);
        $this->assertNull(CurrentBranch::id($admin));

        $this->post(route('customers.store'), [
            'name' => 'No Branch Customer',
            'phone' => '03001112222',
        ])->assertForbidden();
    }

    public function test_inventory_movement_is_written_for_pos_sale(): void
    {
        $branch = $this->makeBranch('Ledger Branch');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Ledger Item'], 5);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 20,
            'comment' => 'Ledger check',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $movement = InventoryMovement::query()->first();
        $this->assertNotNull($movement);
        $this->assertEquals($branch->id, (int) $movement->branch_id);
        $this->assertEquals($product->id, (int) $movement->product_id);
        $this->assertEquals(-2.0, (float) $movement->delta);
        $this->assertEquals(5.0, (float) $movement->qty_before);
        $this->assertEquals(3.0, (float) $movement->qty_after);
    }
}
