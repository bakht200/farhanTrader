<?php

namespace Tests\Feature;

use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\UnitConversion;
use App\Services\UnitConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class UnitConversionSaleTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_pos_converts_packet_quantity_to_carton_stock(): void
    {
        [$branch, $user, $product, $carton, $packet] = $this->makeConvertibleProduct(stock: 10);

        $this->actingAs($user);
        $this->postJson(route('sales.pos.process'), [
            'payment_method' => 'cash',
            'paid_amount' => 624,
            'comment' => 'POS converted unit sale',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 24,
                'unit_id' => $packet->id,
                'selling_price' => 26,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEquals(9.5, (float) $product->fresh()->currentStock($branch->id));

        $sale = Sale::withoutGlobalScopes()->first();
        $item = $sale->items()->first();
        $this->assertEquals(24.0, (float) $item->quantity);
        $this->assertEquals(0.5, (float) $item->quantity_in_base_unit);
        $this->assertSame($packet->id, (int) $item->unit_id);
    }

    public function test_offline_sync_converts_before_stock_check_and_decrement(): void
    {
        [$branch, $user, $product, $carton, $packet] = $this->makeConvertibleProduct(stock: 1);

        $this->actingAs($user);
        $response = $this->postJson(route('sync.push'), [
            'items' => [[
                'client_uuid' => (string) Str::uuid(),
                'entity' => 'sale',
                'op' => 'create',
                'branch_id' => $branch->id,
                'payload' => [
                    'customer_name' => 'Walk-in Customer',
                    'payment_method' => 'cash',
                    'paid_amount' => 0,
                    'comment' => 'offline converted sale',
                    'items' => [[
                        'product_id' => $product->id,
                        'is_custom' => '0',
                        'quantity' => 24,
                        'unit_id' => $packet->id,
                        'selling_price' => 26,
                        'discount' => 0,
                        'discount_type' => 'percentage',
                    ]],
                ],
            ]],
        ]);

        $response->assertOk();
        $this->assertSame(
            'ok',
            $response->json('results.0.status'),
            json_encode($response->json('results.0'))
        );

        $this->assertEquals(0.5, (float) $product->fresh()->currentStock($branch->id));

        $item = Sale::withoutGlobalScopes()->first()->items()->first();
        $this->assertEquals(24.0, (float) $item->quantity);
        $this->assertEquals(0.5, (float) $item->quantity_in_base_unit);
    }

    public function test_conversion_factor_can_be_inferred_from_unit_prices(): void
    {
        $branch = $this->makeBranch('Infer Branch '.uniqid());
        $user = $this->makeBranchUser($branch);
        $this->actingAs($user);

        $carton = Unit::factory()->create(['name' => 'Carton', 'short_name' => 'CTN']);
        $packet = Unit::factory()->create(['name' => 'Packet', 'short_name' => 'PKT']);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'PRICED PACK',
            'unit_id' => $carton->id,
            'base_unit_id' => $carton->id,
            'selling_price' => 4800,
        ], 5);

        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $carton->id,
            'is_base_unit' => true,
            'selling_price' => 4800,
            'is_active' => true,
        ]);
        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $packet->id,
            'is_base_unit' => false,
            'selling_price' => 100,
            'is_active' => true,
        ]);

        $factor = app(UnitConversionService::class)->getConversionFactor($carton->id, $packet->id, $product->id);
        $this->assertEquals(48.0, $factor);

        $inBase = app(UnitConversionService::class)->toBaseQuantity($product, 96, $packet->id);
        $this->assertEquals(2.0, $inBase);
    }

    /**
     * @return array{0: \App\Models\Branch, 1: \App\Models\User, 2: \App\Models\Product, 3: Unit, 4: Unit}
     */
    protected function makeConvertibleProduct(float $stock): array
    {
        $branch = $this->makeBranch('Convert Branch '.uniqid());
        $user = $this->makeBranchUser($branch);
        $carton = Unit::factory()->create(['name' => 'Carton '.uniqid(), 'short_name' => 'CTN']);
        $packet = Unit::factory()->create(['name' => 'Packet '.uniqid(), 'short_name' => 'PKT']);
        $product = $this->makeProductForBranch($branch, [
            'name' => 'SP NUTOL TEST',
            'unit_id' => $carton->id,
            'base_unit_id' => $carton->id,
            'selling_price' => 1248,
        ], $stock);

        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $carton->id,
            'is_base_unit' => true,
            'selling_price' => 1248,
            'is_active' => true,
        ]);
        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $packet->id,
            'is_base_unit' => false,
            'selling_price' => 26,
            'is_active' => true,
        ]);
        UnitConversion::query()->create([
            'product_id' => $product->id,
            'from_unit_id' => $carton->id,
            'to_unit_id' => $packet->id,
            'conversion_factor' => 48,
            'is_active' => true,
        ]);

        return [$branch, $user, $product, $carton, $packet];
    }
}
