<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Services\BranchStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class SupplierBillProductsTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_branch_supplier_bill_creates_product_visible_on_catalog_and_pos(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'RANGEEN SONF',
            'quantity' => 5,
            'unit_price' => 240,
            'total' => 1200,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $product = Product::query()->where('name', 'RANGEEN SONF')->first();
        $this->assertNotNull($product);
        $this->assertSame($branch->id, (int) $product->owner_branch_id);
        $this->assertEquals(240.0, (float) $product->getAttributes()['purchase_price']);
        $this->assertGreaterThan(0, (float) $product->getAttributes()['selling_price']);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ]);
        $this->assertEquals(5.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));

        $this->get(route('products.index'))->assertOk()->assertSee('RANGEEN SONF');
        $this->get(route('sales.pos.index'))->assertOk()->assertSee('RANGEEN SONF');
    }

    public function test_bill_line_without_product_id_reuses_existing_catalog_product(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'RANGEEN SONF',
            'sku' => 'RS-EXISTING-1',
            'purchase_price' => 240,
        ], 2);

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'RANGEEN SONF',
            'product_sku' => 'RS-EXISTING-1',
            'quantity' => 3,
            'unit_price' => 250,
            'total' => 750,
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->assertEquals(1, Product::query()->where('name', 'RANGEEN SONF')->count());
        $this->assertEquals(5.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertDatabaseCount('supplier_bills', 1);
    }

    public function test_overpaid_bill_does_not_leave_a_partial_save(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->from(route('suppliers.transactions.create', $supplier))
            ->post(route('suppliers.transactions.store', $supplier), array_merge($this->billPayload([
                'product_name' => 'RANGEEN SONF',
                'quantity' => 5,
                'unit_price' => 240,
                'total' => 1200,
                'category_id' => $catalog['category']->id,
                'unit_id' => $catalog['unit']->id,
            ]), [
                'amount' => 1200,
                'paid_amount' => 5000,
            ]))
            ->assertRedirect(route('suppliers.transactions.create', $supplier))
            ->assertSessionHasErrors('paid_amount');

        $this->assertDatabaseCount('supplier_bills', 0);
        $this->assertDatabaseCount('supplier_transactions', 0);
        $this->assertSame(0, Product::query()->where('name', 'RANGEEN SONF')->count());
    }

    public function test_owned_orphan_without_stock_row_is_restored_and_visible(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $catalog = $this->catalog();

        $product = Product::factory()->create([
            'name' => 'RANGEEN SONF',
            'sku' => 'RS-20260812-2597',
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'base_unit_id' => $catalog['unit']->id,
            'owner_branch_id' => $branch->id,
            'purchase_price' => 240,
            'selling_price' => 0,
            'retail_price' => 0,
            'wholesale_price' => 0,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);

        $this->assertFalse($product->isAssignedToBranch($branch->id));

        $this->actingAs($user);
        $this->get(route('products.index'))->assertOk()->assertDontSee('RANGEEN SONF');

        $restored = app(BranchStockService::class)->restoreMissingOwnerMembership();
        $this->assertSame(1, $restored);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
        ]);
        $this->assertGreaterThan(0, (float) $product->fresh()->getAttributes()['selling_price']);

        $this->get(route('products.index'))->assertOk()->assertSee('RANGEEN SONF');
        $this->get(route('sales.pos.index'))->assertOk()->assertSee('RANGEEN SONF');
    }

    public function test_supplier_bill_restores_owned_orphan_and_increments_stock(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $product = Product::factory()->create([
            'name' => 'RANGEEN SONF',
            'sku' => 'RS-20260812-2597',
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'base_unit_id' => $catalog['unit']->id,
            'owner_branch_id' => $branch->id,
            'purchase_price' => 240,
            'selling_price' => 0,
            'retail_price' => 0,
            'wholesale_price' => 0,
            'is_active' => true,
            'supplier_name' => 'Farhan Ullah',
        ]);

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_id' => $product->id,
            'product_name' => 'RANGEEN SONF',
            'quantity' => 8,
            'unit_price' => 240,
            'total' => 1920,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->assertEquals(8.0, (float) BranchProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->get(route('products.index'))->assertOk()->assertSee('RANGEEN SONF');
        $this->get(route('sales.pos.index'))->assertOk()->assertSee('RANGEEN SONF');
    }

    public function test_picking_phandu_catalog_on_bill_assigns_membership_without_renaming_master(): void
    {
        $phandu = Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $user = $this->makeBranchUser($ashraf);
        $supplier = $this->makeSupplierForBranch($ashraf, ['name' => 'Local Supplier']);

        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
        ], 100);

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_id' => $product->id,
            'product_name' => 'MEETA SODA BRANCH NAME',
            'quantity' => 4,
            'unit_price' => 3100,
            'total' => 12400,
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->assertSame('MEETA SODA', $product->fresh()->getAttributes()['name']);
        $this->assertEquals(3070.0, (float) $product->fresh()->getAttributes()['purchase_price']);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
        ]);
        $this->assertEquals(4.0, (float) BranchProductStock::query()
            ->where('branch_id', $ashraf->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->get(route('products.index'))->assertOk()->assertSee('MEETA SODA');
    }

    public function test_picking_another_branch_owned_product_creates_a_new_local_product(): void
    {
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $peshawar = $this->makeBranch('PESHAWAR CHEMICAL');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $foreign = $this->makeProductForBranch($peshawar, ['name' => 'RANGEEN SONF'], 12);
        $supplier = $this->makeSupplierForBranch($ashraf, ['name' => 'Ashraf Supplier']);
        $catalog = $this->catalog();

        $this->actingAs($ashrafUser);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_id' => $foreign->id,
            'product_name' => 'RANGEEN SONF',
            'quantity' => 2,
            'unit_price' => 240,
            'total' => 480,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->assertSame($peshawar->id, (int) $foreign->fresh()->owner_branch_id);
        $this->assertFalse($foreign->fresh()->isAssignedToBranch($ashraf->id));
        $this->assertSame(12.0, (float) BranchProductStock::query()
            ->where('branch_id', $peshawar->id)
            ->where('product_id', $foreign->id)
            ->value('stock_quantity'));

        $local = Product::query()
            ->where('name', 'RANGEEN SONF')
            ->where('owner_branch_id', $ashraf->id)
            ->first();
        $this->assertNotNull($local);
        $this->assertNotSame($foreign->id, $local->id);
        $this->get(route('products.index'))->assertOk()->assertSee('RANGEEN SONF');
    }

    public function test_wipe_keeps_owned_stock_and_removes_assigned_catalog_stock(): void
    {
        $phandu = Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);

        $catalogProduct = $this->makeProductForBranch($phandu, ['name' => 'Keep Product'], 10);
        $owned = $this->makeProductForBranch($ashraf, ['name' => 'RANGEEN SONF'], 7);
        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $catalogProduct->id,
            'stock_quantity' => 4,
            'selling_type' => 'retail',
        ]);

        $this->artisan('branches:wipe-operational', [
            '--name' => ['ASHRAF ROAD'],
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $ashraf->id,
            'product_id' => $owned->id,
        ]);
        $this->assertDatabaseMissing('branch_product_stocks', [
            'branch_id' => $ashraf->id,
            'product_id' => $catalogProduct->id,
        ]);

        $this->actingAs($ashrafUser);
        $this->get(route('products.index'))->assertOk()
            ->assertSee('RANGEEN SONF')
            ->assertDontSee('Keep Product');
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function billPayload(array $product): array
    {
        return [
            'type' => 'credit',
            'create_bill' => '1',
            'transaction_date' => now()->toDateString(),
            'bill_date' => now()->toDateString(),
            'products' => [$product],
        ];
    }
}
