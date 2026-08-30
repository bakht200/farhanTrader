<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Models\ProductLot;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\Unit;
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

    public function test_supplier_bill_extra_price_shows_on_pos(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'RANGEEN SONF EXTRA',
            'quantity' => 2,
            'unit_price' => 240,
            'extra_price' => 18.5,
            'total' => 480,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $product = Product::query()->where('name', 'RANGEEN SONF EXTRA')->first();
        $this->assertNotNull($product);
        $this->assertEquals(18.5, (float) $product->fresh()->extra_price);

        $html = $this->get(route('sales.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('RANGEEN SONF EXTRA', $html);
        $this->assertMatchesRegularExpression('/extra_price.{0,24}18\.5/', $html);
    }

    public function test_second_bill_at_new_rate_shows_both_lots_on_pos(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'RANGEEN SONF LOTS',
            'quantity' => 1,
            'unit_price' => 200,
            'total' => 200,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'RANGEEN SONF LOTS',
            'quantity' => 2,
            'unit_price' => 400,
            'total' => 800,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $product = Product::query()->where('name', 'RANGEEN SONF LOTS')->first();
        $this->assertNotNull($product);
        $this->assertEquals(3.0, (float) $product->fresh()->stock_quantity);

        $lots = \App\Models\ProductLot::query()->where('product_id', $product->id)->orderBy('purchase_price')->get();
        $this->assertCount(2, $lots);
        $this->assertEquals(200.0, (float) $lots[0]->purchase_price);
        $this->assertEquals(1.0, (float) $lots[0]->quantity);
        $this->assertEquals(400.0, (float) $lots[1]->purchase_price);
        $this->assertEquals(2.0, (float) $lots[1]->quantity);

        $html = $this->get(route('sales.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('RANGEEN SONF LOTS', $html);
        $this->assertStringContainsString('Rate PKR 200.00', $html);
        $this->assertStringContainsString('Rate PKR 400.00', $html);
    }

    public function test_existing_catalog_stock_keeps_old_rate_when_bill_uses_new_price(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'ASHRAFI (ORANGE)',
            'purchase_price' => 3300,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'wholesale_price' => 3600,
            'selling_type' => 'both',
        ], 22);

        $this->assertEquals(0, ProductLot::query()->where('product_id', $product->id)->count());

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_id' => $product->id,
            'product_name' => 'ASHRAFI (ORANGE)',
            'quantity' => 1,
            'unit_price' => 3500,
            'retail_price' => 4200,
            'wholesale_price' => 3600,
            'total' => 3500,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->assertEquals(23.0, (float) $product->fresh()->currentStock($branch->id));
        $lots = ProductLot::query()->where('product_id', $product->id)->orderBy('purchase_price')->get();
        $this->assertCount(2, $lots);
        $this->assertEquals(3300.0, (float) $lots[0]->purchase_price);
        $this->assertEquals(22.0, (float) $lots[0]->quantity);
        $this->assertEquals(4000.0, (float) $lots[0]->retail_price);
        $this->assertEquals(3500.0, (float) $lots[1]->purchase_price);
        $this->assertEquals(1.0, (float) $lots[1]->quantity);
        $this->assertEquals(4200.0, (float) $lots[1]->retail_price);

        $html = $this->get(route('sales.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('ASHRAFI (ORANGE)', $html);
        $this->assertStringContainsString('Rate PKR 3,300.00', $html);
        $this->assertStringContainsString('Rate PKR 3,500.00', $html);
        $this->assertStringContainsString('4,000.00', $html);
        $this->assertStringContainsString('4,200.00', $html);
    }

    public function test_second_bill_at_same_rate_adds_to_existing_lot(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'SAME RATE SUGAR',
            'quantity' => 10,
            'unit_price' => 200,
            'total' => 2000,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'SAME RATE SUGAR',
            'quantity' => 3,
            'unit_price' => 200,
            'total' => 600,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $product = Product::query()->where('name', 'SAME RATE SUGAR')->first();
        $this->assertNotNull($product);
        $this->assertEquals(13.0, (float) $product->fresh()->stock_quantity);
        $lots = ProductLot::query()->where('product_id', $product->id)->get();
        $this->assertCount(1, $lots);
        $this->assertEquals(13.0, (float) $lots->first()->quantity);
        $this->assertEquals(200.0, (float) $lots->first()->purchase_price);
    }

    public function test_extra_price_is_not_added_to_bill_amount_and_is_kept_on_lot(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'EXTRA LOT SUGAR',
            'quantity' => 2,
            'unit_price' => 240,
            'extra_price' => 18.5,
            'total' => 480,
            'amount' => 480,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $product = Product::query()->where('name', 'EXTRA LOT SUGAR')->first();
        $this->assertNotNull($product);
        $this->assertEquals(18.5, (float) $product->fresh()->extra_price);
        $this->assertDatabaseHas('supplier_bills', [
            'supplier_id' => $supplier->id,
            'bill_amount' => 480,
        ]);
        $lot = ProductLot::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($lot);
        $this->assertEquals(18.5, (float) $lot->extra_price);
        $this->assertEquals(240.0, (float) $lot->purchase_price);
    }

    public function test_editing_supplier_bill_updates_lot_extra_price(): void
    {
        $branch = $this->makeBranch('PESHAWAR CHEMICAL');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'EXTRA FIX SUGAR',
            'quantity' => 2,
            'unit_price' => 240,
            'extra_price' => 11000,
            'total' => 480,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'both',
        ]))->assertRedirect(route('suppliers.show', $supplier));

        $product = Product::query()->where('name', 'EXTRA FIX SUGAR')->first();
        $bill = SupplierBill::query()->where('supplier_id', $supplier->id)->latest('id')->first();
        $lot = ProductLot::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($bill);
        $this->assertNotNull($lot);
        $this->assertEquals(11000.0, (float) $lot->extra_price);

        $this->put(route('suppliers.bills.update', [$supplier, $bill]), [
            'bill_date' => now()->toDateString(),
            'bill_amount' => 480,
            'products' => [[
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'unit_id' => $catalog['unit']->id,
                'quantity' => 2,
                'unit_price' => 240,
                'extra_price' => 250,
                'discount' => 0,
                'tax' => 0,
                'total' => 480,
            ]],
        ])->assertRedirect(route('suppliers.show', $supplier));

        $this->assertEquals(250.0, (float) $lot->fresh()->extra_price);
        $this->assertEquals(250.0, (float) $product->fresh()->getAttributes()['extra_price']);
    }

    public function test_pos_sale_decrements_only_the_selected_lot(): void
    {
        $branch = $this->makeBranch('POS LOTS');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'TWO RATE SALE',
            'quantity' => 5,
            'unit_price' => 200,
            'total' => 1000,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'retail',
            'retail_price' => 300,
        ]))->assertRedirect();

        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'TWO RATE SALE',
            'quantity' => 4,
            'unit_price' => 400,
            'total' => 1600,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'retail',
            'retail_price' => 500,
        ]))->assertRedirect();

        $product = Product::query()->where('name', 'TWO RATE SALE')->first();
        $cheapLot = ProductLot::query()->where('product_id', $product->id)->where('purchase_price', 200)->first();
        $dearLot = ProductLot::query()->where('product_id', $product->id)->where('purchase_price', 400)->first();
        $this->assertNotNull($cheapLot);
        $this->assertNotNull($dearLot);

        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 900,
            'comment' => 'Sell cheap lot only',
            'items' => [[
                'product_id' => $product->id,
                'product_lot_id' => $cheapLot->id,
                'quantity' => 3,
                'selling_price' => 300,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEquals(2.0, (float) $cheapLot->fresh()->quantity);
        $this->assertEquals(4.0, (float) $dearLot->fresh()->quantity);
        $this->assertEquals(6.0, (float) $product->fresh()->currentStock($branch->id));
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'product_lot_id' => $cheapLot->id,
            'quantity' => 3,
        ]);
        $saleTotal = (float) SaleItem::query()->where('product_id', $product->id)->value('total');
        $this->assertEquals(900.0, $saleTotal);
    }

    public function test_pos_rejects_selling_more_than_the_selected_lot(): void
    {
        $branch = $this->makeBranch('POS OVERSELL LOT');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'OVERSELL LOT',
            'quantity' => 2,
            'unit_price' => 200,
            'total' => 400,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'retail',
            'retail_price' => 300,
        ]))->assertRedirect();
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'OVERSELL LOT',
            'quantity' => 5,
            'unit_price' => 400,
            'total' => 2000,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'retail',
            'retail_price' => 500,
        ]))->assertRedirect();

        $product = Product::query()->where('name', 'OVERSELL LOT')->first();
        $cheapLot = ProductLot::query()->where('product_id', $product->id)->where('purchase_price', 200)->first();

        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 900,
            'comment' => 'Try oversell cheap lot',
            'items' => [[
                'product_id' => $product->id,
                'product_lot_id' => $cheapLot->id,
                'quantity' => 3,
                'selling_price' => 300,
            ]],
        ])->assertStatus(400)->assertJsonPath('success', false);

        $this->assertEquals(2.0, (float) $cheapLot->fresh()->quantity);
        $this->assertEquals(7.0, (float) $product->fresh()->currentStock($branch->id));
    }

    public function test_deleting_a_sale_restores_the_selected_lot(): void
    {
        $branch = $this->makeBranch('POS DELETE LOT');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Farhan Ullah']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), $this->billPayload([
            'product_name' => 'DELETE LOT SALE',
            'quantity' => 5,
            'unit_price' => 200,
            'total' => 1000,
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'selling_type' => 'retail',
            'retail_price' => 300,
        ]))->assertRedirect();

        $product = Product::query()->where('name', 'DELETE LOT SALE')->first();
        $lot = ProductLot::query()->where('product_id', $product->id)->first();

        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 600,
            'comment' => 'Sell then delete',
            'items' => [[
                'product_id' => $product->id,
                'product_lot_id' => $lot->id,
                'quantity' => 2,
                'selling_price' => 300,
            ]],
        ])->assertOk();

        $this->assertEquals(3.0, (float) $lot->fresh()->quantity);
        $sale = \App\Models\Sale::query()->first();
        $this->delete(route('sales.destroy', $sale))->assertRedirect();

        $this->assertEquals(5.0, (float) $lot->fresh()->quantity);
        $this->assertEquals(5.0, (float) $product->fresh()->currentStock($branch->id));
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

    public function test_bootstrap_includes_supplier_bills_and_payments(): void
    {
        $branch = $this->makeBranch('OFFLINE SUPPLIERS');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Local Cash Supplier']);

        $this->actingAs($user);
        $bill = $supplier->bills()->create([
            'bill_number' => 'B-100',
            'bill_amount' => 500,
            'bill_date' => now()->toDateString(),
            'description' => 'Test bill',
        ]);
        $supplier->transactions()->create([
            'type' => 'credit',
            'amount' => 500,
            'transaction_date' => now()->toDateString(),
            'supplier_bill_id' => $bill->id,
        ]);
        $supplier->transactions()->create([
            'type' => 'debit',
            'amount' => 200,
            'transaction_date' => now()->toDateString(),
            'supplier_bill_id' => $bill->id,
            'description' => 'Partial pay',
        ]);

        $payload = $this->getJson('/sync/bootstrap')->assertOk()->json();
        $this->assertNotEmpty($payload['supplier_bills'] ?? []);
        $this->assertTrue(collect($payload['supplier_bills'])->contains('id', $bill->id));
        $this->assertTrue(collect($payload['supplier_transactions'])->contains('amount', 200));
        $this->assertSame($supplier->id, (int) collect($payload['supplier_bills'])->firstWhere('id', $bill->id)['supplier_id']);
    }

    public function test_offline_supplier_bill_and_payment_push_to_server(): void
    {
        $branch = $this->makeBranch('OFFLINE PUSH');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Push Supplier']);

        $this->actingAs($user);
        $billUuid = '11111111-1111-4111-8111-111111111111';
        $payUuid = '22222222-2222-4222-8222-222222222222';

        $this->postJson(route('sync.push'), [
            'items' => [
                [
                    'client_uuid' => $billUuid,
                    'entity' => 'supplier_bill',
                    'op' => 'create',
                    'payload' => [
                        'supplier_id' => $supplier->id,
                        'bill_amount' => 1000,
                        'bill_date' => now()->toDateString(),
                        'paid_amount' => 100,
                        'description' => 'Offline bill',
                    ],
                ],
                [
                    'client_uuid' => $payUuid,
                    'entity' => 'supplier_payment',
                    'op' => 'create',
                    'payload' => [
                        'supplier_id' => $supplier->id,
                        'amount' => 50,
                        'transaction_date' => now()->toDateString(),
                        'description' => 'Offline payment',
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('supplier_bills', [
            'supplier_id' => $supplier->id,
            'bill_amount' => 1000,
        ]);
        $this->assertEquals(1000.0, (float) $supplier->transactions()->where('type', 'credit')->sum('amount'));
        $this->assertEquals(150.0, (float) $supplier->transactions()->where('type', 'debit')->sum('amount'));
    }

    public function test_anonymous_button_opens_cash_purchase_form(): void
    {
        $branch = $this->makeBranch('ANON BRANCH');
        $user = $this->makeBranchUser($branch);

        $this->actingAs($user);
        $this->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Anonymous')
            ->assertSee(route('suppliers.anonymous'), false);

        $this->post(route('suppliers.anonymous'))
            ->assertRedirect();

        $supplier = Supplier::query()->where('is_anonymous', true)->first();
        $this->assertNotNull($supplier);
        $this->assertSame('Anonymous', $supplier->name);
        $this->assertSame($branch->id, (int) $supplier->branch_id);

        $this->post(route('suppliers.anonymous'))
            ->assertRedirect(route('suppliers.transactions.create', $supplier));
        $this->assertEquals(1, Supplier::query()->where('is_anonymous', true)->count());

        $this->get(route('suppliers.anonymous'))
            ->assertOk()
            ->assertSee('Product information')
            ->assertSee('Unit Conversions')
            ->assertSee('From Unit')
            ->assertSee('To Unit')
            ->assertSee('Conversion Factor')
            ->assertSee('+ Add Conversion Factor')
            ->assertSee('Description')
            ->assertDontSee('Transaction Type')
            ->assertDontSee('Paid in cash')
            ->assertDontSee('Create bill for this amount');

        $this->get(route('suppliers.transactions.create', $supplier))
            ->assertOk()
            ->assertSee('Product information')
            ->assertSee('Unit Conversions')
            ->assertSee('From Unit')
            ->assertSee('To Unit')
            ->assertSee('Conversion Factor')
            ->assertSee('+ Add Conversion Factor')
            ->assertSee('Description')
            ->assertDontSee('Transaction Type')
            ->assertDontSee('Paid in cash');

        $this->from(route('suppliers.index'))
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect();
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_anonymous' => true,
        ]);
    }

    public function test_anonymous_cash_bill_creates_new_product_even_when_name_exists(): void
    {
        $branch = $this->makeBranch('ANON SUGAR');
        $user = $this->makeBranchUser($branch);
        $catalog = $this->catalog();
        $kg = Unit::factory()->create(['name' => 'Kilogram', 'short_name' => 'KG']);
        $existing = $this->makeProductForBranch($branch, [
            'name' => 'Sugar',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'unit_id' => $catalog['unit']->id,
            'base_unit_id' => $catalog['unit']->id,
        ], 10);

        $this->actingAs($user);
        $this->post(route('suppliers.anonymous'))->assertRedirect();
        $anonymous = Supplier::query()->where('is_anonymous', true)->first();

        $this->post(route('suppliers.transactions.store', $anonymous), [
            'amount' => 540,
            'description' => 'Bought from the bazaar',
            'products' => [[
                'product_name' => 'Sugar',
                'product_id' => $existing->id,
                'quantity' => 3,
                'unit_price' => 180,
                'retail_price' => 220,
                'wholesale_price' => 200,
                'total' => 540,
                'category_id' => $catalog['category']->id,
                'unit_id' => $catalog['unit']->id,
                'selling_type' => 'both',
                'conversions' => [[
                    'from_unit_id' => $catalog['unit']->id,
                    'to_unit_id' => $kg->id,
                    'factor' => 50,
                ]],
            ]],
        ])->assertRedirect(route('suppliers.index'));

        $this->assertEquals(2, Product::query()->where('name', 'Sugar')->count());
        $this->assertEquals(10.0, (float) $existing->fresh()->currentStock($branch->id));

        $created = Product::query()->where('name', 'Sugar')->where('id', '!=', $existing->id)->first();
        $this->assertNotNull($created);
        $this->assertEquals(3.0, (float) $created->currentStock($branch->id));
        $this->assertEquals(180.0, (float) $created->purchase_price);
        $this->assertEquals(220.0, (float) $created->retail_price);
        $this->assertEquals(200.0, (float) $created->wholesale_price);
        $this->assertNotSame($existing->sku, $created->sku);
        $this->assertDatabaseHas('unit_conversions', [
            'product_id' => $created->id,
            'from_unit_id' => $catalog['unit']->id,
            'to_unit_id' => $kg->id,
            'conversion_factor' => 50,
        ]);
        $this->assertEquals(540.0, (float) $anonymous->transactions()->where('type', 'credit')->sum('amount'));
        $this->assertEquals(540.0, (float) $anonymous->transactions()->where('type', 'debit')->sum('amount'));
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
