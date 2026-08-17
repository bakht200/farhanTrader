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

    public function test_deleting_a_sale_restores_branch_stock(): void
    {
        $branch = $this->makeBranch('POS Restore');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, ['name' => 'Restore Item'], 10);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 40,
            'comment' => 'Sale to delete',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'selling_price' => 10,
            ]],
        ])->assertOk();

        $sale = Sale::query()->first();
        $this->assertEquals(6.0, (float) $product->fresh()->currentStock($branch->id));

        $this->delete(route('sales.destroy', $sale))->assertRedirect();

        $this->assertEquals(10.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
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
