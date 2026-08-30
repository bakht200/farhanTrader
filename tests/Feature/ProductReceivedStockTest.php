<?php

namespace Tests\Feature;

use App\Models\BranchProductStock;
use App\Models\ProductLot;
use App\Models\ProductUnit;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class ProductReceivedStockTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_edit_product_adds_same_price_quantity_to_existing_lot(): void
    {
        $branch = $this->makeBranch('STOCK ADD');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Sugar Bag',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 10);

        $lot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 10,
            'purchase_price' => 200,
            'extra_price' => 0,
            'retail_price' => 250,
            'wholesale_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Add received stock')
            ->assertSee('Quantity received');

        $this->put(route('products.update', $product), $this->editPayload($product, [
            'stock_quantity' => 10,
            'add_received_qty' => 1,
            'add_received_unit_id' => $product->unit_id,
            'add_lot_id' => $lot->id,
        ]))->assertRedirect();

        $this->assertEquals(11.0, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(11.0, (float) $lot->fresh()->quantity);
    }

    public function test_edit_product_converts_extra_kg_into_base_bags(): void
    {
        $branch = $this->makeBranch('SUGAR KG');
        $user = $this->makeBranchUser($branch);
        $bag = Unit::factory()->create(['name' => 'Bag', 'short_name' => 'BAG']);
        $kg = Unit::factory()->create(['name' => 'Kilogram', 'short_name' => 'KG']);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Sugar',
            'unit_id' => $bag->id,
            'base_unit_id' => $bag->id,
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 10);

        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $bag->id,
            'is_base_unit' => true,
            'selling_price' => 250,
            'is_active' => true,
        ]);
        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $kg->id,
            'is_base_unit' => false,
            'selling_price' => 50,
            'is_active' => true,
        ]);
        UnitConversion::query()->create([
            'product_id' => $product->id,
            'from_unit_id' => $bag->id,
            'to_unit_id' => $kg->id,
            'conversion_factor' => 5,
            'is_active' => true,
        ]);

        $lot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $bag->id,
            'quantity' => 10,
            'purchase_price' => 200,
            'extra_price' => 0,
            'retail_price' => 250,
            'wholesale_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->put(route('products.update', $product), $this->editPayload($product, [
            'unit_id' => $bag->id,
            'base_unit_id' => $bag->id,
            'stock_quantity' => 10,
            'add_received_qty' => 2,
            'add_received_unit_id' => $kg->id,
            'add_lot_id' => $lot->id,
        ]))->assertRedirect();

        $this->assertEquals(10.4, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(10.4, (float) $lot->fresh()->quantity);
    }

    public function test_edit_product_requires_lot_when_multiple_rates_exist(): void
    {
        $branch = $this->makeBranch('TWO LOTS');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Two Rate Sugar',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 15);

        $oldLot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 10,
            'purchase_price' => 200,
            'extra_price' => 0,
            'retail_price' => 250,
            'wholesale_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);
        $newLot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 5,
            'purchase_price' => 400,
            'extra_price' => 0,
            'retail_price' => 500,
            'wholesale_price' => 500,
            'selling_price' => 500,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->from(route('products.edit', $product))
            ->put(route('products.update', $product), $this->editPayload($product, [
                'stock_quantity' => 15,
                'add_received_qty' => 1,
                'add_received_unit_id' => $product->unit_id,
            ]))
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors('add_lot_id');

        $this->assertEquals(10.0, (float) $oldLot->fresh()->quantity);
        $this->assertEquals(5.0, (float) $newLot->fresh()->quantity);
        $this->assertEquals(15.0, (float) $product->fresh()->currentStock($branch->id));

        $this->put(route('products.update', $product), $this->editPayload($product, [
            'stock_quantity' => 15,
            'add_received_qty' => 1,
            'add_received_unit_id' => $product->unit_id,
            'add_lot_id' => $oldLot->id,
        ]))->assertRedirect();

        $this->assertEquals(11.0, (float) $oldLot->fresh()->quantity);
        $this->assertEquals(5.0, (float) $newLot->fresh()->quantity);
        $this->assertEquals(16.0, (float) $product->fresh()->currentStock($branch->id));
    }

    public function test_editing_selling_price_updates_only_the_matching_purchase_lot(): void
    {
        $branch = $this->makeBranch('LOT SELLING');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'ASHRAFI (ORANGE)',
            'purchase_price' => 400,
            'retail_price' => 500,
            'selling_price' => 500,
            'selling_type' => 'retail',
        ], 15);

        $oldLot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 10,
            'purchase_price' => 200,
            'extra_price' => 0,
            'retail_price' => 250,
            'wholesale_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);
        $newLot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 5,
            'purchase_price' => 400,
            'extra_price' => 0,
            'retail_price' => 500,
            'wholesale_price' => 500,
            'selling_price' => 500,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->put(route('products.update', $product), $this->editPayload($product, [
            'stock_quantity' => 15,
            'purchase_price' => 400,
            'retail_price' => 550,
            'selling_price' => 550,
            'wholesale_price' => 550,
        ]))->assertRedirect();

        $this->assertEquals(250.0, (float) $oldLot->fresh()->retail_price);
        $this->assertEquals(250.0, (float) $oldLot->fresh()->selling_price);
        $this->assertEquals(10.0, (float) $oldLot->fresh()->quantity);
        $this->assertEquals(550.0, (float) $newLot->fresh()->retail_price);
        $this->assertEquals(550.0, (float) $newLot->fresh()->selling_price);
        $this->assertEquals(5.0, (float) $newLot->fresh()->quantity);

        $html = $this->get(route('sales.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Rate PKR 200.00', $html);
        $this->assertStringContainsString('Rate PKR 400.00', $html);
        $this->assertStringContainsString('250.00', $html);
        $this->assertStringContainsString('550.00', $html);
    }

    public function test_add_received_without_lots_creates_lot_including_existing_stock(): void
    {
        $branch = $this->makeBranch('NO LOT YET');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Unlotted Sugar',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 10);

        $this->assertEquals(0, ProductLot::query()->where('product_id', $product->id)->count());

        $this->actingAs($user);
        $this->put(route('products.update', $product), $this->editPayload($product, [
            'stock_quantity' => 10,
            'add_received_qty' => 1,
            'add_received_unit_id' => $product->unit_id,
        ]))->assertRedirect();

        $this->assertEquals(11.0, (float) $product->fresh()->currentStock($branch->id));
        $lot = ProductLot::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($lot);
        $this->assertEquals(11.0, (float) $lot->quantity);
        $this->assertEquals(200.0, (float) $lot->purchase_price);
    }

    public function test_edit_without_received_qty_does_not_change_stock_or_lot(): void
    {
        $branch = $this->makeBranch('NO ADD');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Keep Stock',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 10);
        $lot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 10,
            'purchase_price' => 200,
            'extra_price' => 0,
            'retail_price' => 250,
            'wholesale_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->put(route('products.update', $product), $this->editPayload($product, [
            'stock_quantity' => 10,
            'add_received_qty' => '',
        ]))->assertRedirect();

        $this->assertEquals(10.0, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(10.0, (float) $lot->fresh()->quantity);
    }

    public function test_decimal_bags_and_pos_remaining_update_after_add(): void
    {
        $branch = $this->makeBranch('DECIMAL BAG');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Decimal Sugar',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 0.73);
        $lot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 0.73,
            'purchase_price' => 200,
            'extra_price' => 0,
            'retail_price' => 250,
            'wholesale_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->put(route('products.update', $product), $this->editPayload($product, [
            'stock_quantity' => 0.73,
            'add_received_qty' => 2.73,
            'add_received_unit_id' => $product->unit_id,
            'add_lot_id' => $lot->id,
        ]))->assertRedirect();

        $this->assertEquals(3.46, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(3.46, (float) $lot->fresh()->quantity);

        $html = $this->get(route('sales.pos.index'))->assertOk()->getContent();
        $this->assertStringContainsString('Decimal Sugar', $html);
        $this->assertStringContainsString('3.46', $html);
        $this->assertStringContainsString('Rate PKR 200.00', $html);
    }

    public function test_cannot_add_received_stock_to_another_products_lot(): void
    {
        $branch = $this->makeBranch('FOREIGN LOT');
        $user = $this->makeBranchUser($branch);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Own Product',
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 10);
        $other = $this->makeProductForBranch($branch, [
            'name' => 'Other Product',
            'purchase_price' => 50,
            'retail_price' => 80,
            'selling_price' => 80,
            'selling_type' => 'retail',
        ], 4);
        $foreignLot = ProductLot::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $other->id,
            'unit_id' => $other->unit_id,
            'quantity' => 4,
            'purchase_price' => 50,
            'extra_price' => 0,
            'retail_price' => 80,
            'wholesale_price' => 80,
            'selling_price' => 80,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($user);
        $this->from(route('products.edit', $product))
            ->put(route('products.update', $product), $this->editPayload($product, [
                'stock_quantity' => 10,
                'add_received_qty' => 1,
                'add_received_unit_id' => $product->unit_id,
                'add_lot_id' => $foreignLot->id,
            ]))
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors('add_lot_id');

        $this->assertEquals(10.0, (float) $product->fresh()->currentStock($branch->id));
        $this->assertEquals(4.0, (float) $other->fresh()->currentStock($branch->id));
        $this->assertEquals(4.0, (float) $foreignLot->fresh()->quantity);
    }

    public function test_edit_page_lists_conversion_units_for_received_stock(): void
    {
        $branch = $this->makeBranch('UNITS UI');
        $user = $this->makeBranchUser($branch);
        $bag = Unit::factory()->create(['name' => 'Bag', 'short_name' => 'BAG']);
        $kg = Unit::factory()->create(['name' => 'Kilogram', 'short_name' => 'KG']);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'Sugar UI',
            'unit_id' => $bag->id,
            'base_unit_id' => $bag->id,
            'purchase_price' => 200,
            'retail_price' => 250,
            'selling_price' => 250,
            'selling_type' => 'retail',
        ], 10);
        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $bag->id,
            'is_base_unit' => true,
            'selling_price' => 250,
            'is_active' => true,
        ]);
        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $kg->id,
            'is_base_unit' => false,
            'selling_price' => 50,
            'is_active' => true,
        ]);
        UnitConversion::query()->create([
            'product_id' => $product->id,
            'from_unit_id' => $bag->id,
            'to_unit_id' => $kg->id,
            'conversion_factor' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Add received stock')
            ->assertSee('BAG')
            ->assertSee('KG')
            ->assertSee('Current stock');
    }

    public function test_branch_user_can_add_received_stock_on_assigned_phandu_catalog(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF RECEIVE');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA RECEIVE',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'selling_type' => 'retail',
        ], 100);
        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'stock_quantity' => 6,
            'selling_type' => 'retail',
        ]);
        $lot = ProductLot::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 6,
            'purchase_price' => 3070,
            'extra_price' => 0,
            'retail_price' => 4000,
            'wholesale_price' => 4000,
            'selling_price' => 4000,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($ashrafUser);
        $this->put(route('products.update', $product), [
            'name' => 'MEETA SODA RECEIVE',
            'selling_type' => 'retail',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'wholesale_price' => 4000,
            'selling_price' => 4000,
            'stock_quantity' => 6,
            'add_received_qty' => 2,
            'add_received_unit_id' => $product->unit_id,
            'add_lot_id' => $lot->id,
        ])->assertRedirect(route('products.index'));

        $this->assertEquals(8.0, (float) $product->fresh()->currentStock($ashraf->id));
        $this->assertEquals(8.0, (float) $lot->fresh()->quantity);
        $this->assertEquals(100.0, (float) BranchProductStock::query()
            ->where('branch_id', $phandu->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertSame('MEETA SODA RECEIVE', $product->fresh()->getAttributes()['name']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function editPayload(\App\Models\Product $product, array $overrides = []): array
    {
        $attrs = $product->getAttributes();

        return array_merge([
            'name' => $attrs['name'],
            'slug' => $attrs['slug'] ?? null,
            'sku' => $attrs['sku'] ?? null,
            'category_id' => $attrs['category_id'],
            'unit_id' => $attrs['unit_id'],
            'base_unit_id' => $attrs['base_unit_id'] ?? $attrs['unit_id'],
            'product_type' => $attrs['product_type'] ?? 'single',
            'selling_type' => $attrs['selling_type'] ?? 'retail',
            'purchase_price' => $attrs['purchase_price'],
            'retail_price' => $attrs['retail_price'],
            'wholesale_price' => $attrs['wholesale_price'] ?? $attrs['retail_price'],
            'selling_price' => $attrs['selling_price'] ?? $attrs['retail_price'],
            'low_stock_threshold' => $attrs['low_stock_threshold'] ?? 5,
            'stock_quantity' => 10,
        ], $overrides);
    }
}
