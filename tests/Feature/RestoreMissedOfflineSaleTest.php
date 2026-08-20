<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use App\Services\RestoreMissedOfflineSaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class RestoreMissedOfflineSaleTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_payload_file_matches_the_printed_bill_total(): void
    {
        $path = database_path('data/missed_offline_sale_ead992f0.json');
        $this->assertFileExists($path);

        $record = json_decode(File::get($path), true);
        $this->assertIsArray($record);
        $this->assertSame('ead992f0-edd8-44f4-8535-84895287f56d', $record['client_uuid']);
        $this->assertSame(477, $record['customer_id']);
        $this->assertSame('PESHAWAR CHEMICAL & DISPOSIBLE', $record['customer_name']);
        $this->assertSame('2026-08-13', $record['sale_date']);
        $this->assertCount(56, $record['items']);

        $computed = app(RestoreMissedOfflineSaleService::class)->computeTotals($record['items']);
        $this->assertEquals(291562.0, $computed['total']);
        $this->assertEquals(291562.0, (float) $record['expected_total']);
    }

    public function test_dry_run_does_not_write_a_sale(): void
    {
        [$record] = $this->makeRecord();

        $result = app(RestoreMissedOfflineSaleService::class)->restore($record, true);

        $this->assertSame('dry_run', $result['status']);
        $this->assertSame(0, Sale::withoutGlobalScopes()->count());
    }

    public function test_commit_creates_the_sale_items_stock_and_uuid_mapping(): void
    {
        [$record, $product] = $this->makeRecord(stock: 50);

        $result = app(RestoreMissedOfflineSaleService::class)->restore($record, false);

        $this->assertSame('created', $result['status']);
        $this->assertNotNull($result['sale_number']);

        $sale = Sale::withoutGlobalScopes()->findOrFail($result['sale_id']);
        $this->assertSame($record['customer_id'], (int) $sale->customer_id);
        $this->assertSame('2026-08-13', $sale->sale_date->toDateString());
        $this->assertEquals(5400.0, (float) $sale->total_amount);
        $this->assertEquals(0.0, (float) $sale->paid_amount);
        $this->assertSame('partial', $sale->payment_status);
        $this->assertSame('completed', $sale->status);
        $this->assertSame('2026-08-13 16:49:59', $sale->created_at->format('Y-m-d H:i:s'));
        $this->assertCount(2, $sale->items);

        $custom = $sale->items->firstWhere('product_name', '1 POND DABA');
        $this->assertNotNull($custom);
        $this->assertNull($custom->product_id);
        $this->assertEquals(200.0, (float) $custom->quantity);
        $this->assertEquals(3800.0, (float) $custom->total);

        $catalog = $sale->items->firstWhere('product_id', $product->id);
        $this->assertNotNull($catalog);
        $this->assertEquals(1600.0, (float) $catalog->total);

        $this->assertEquals(30.0, $product->fresh()->currentStock($record['branch_id']));
        $this->assertDatabaseHas('sync_id_mappings', [
            'client_uuid' => $record['client_uuid'],
            'entity' => 'sale',
            'server_id' => (string) $sale->id,
        ]);
    }

    public function test_commit_is_idempotent(): void
    {
        [$record] = $this->makeRecord();

        $first = app(RestoreMissedOfflineSaleService::class)->restore($record, false);
        $second = app(RestoreMissedOfflineSaleService::class)->restore($record, false);

        $this->assertSame('created', $first['status']);
        $this->assertSame('already_restored', $second['status']);
        $this->assertSame($first['sale_id'], $second['sale_id']);
        $this->assertSame(1, Sale::withoutGlobalScopes()->count());
    }

    public function test_missing_catalog_product_is_stored_as_custom_and_sale_still_saves(): void
    {
        [$record] = $this->makeRecord();
        $record['items'][1]['product_id'] = 999999;

        $result = app(RestoreMissedOfflineSaleService::class)->restore($record, false);

        $this->assertSame('created', $result['status']);
        $sale = Sale::withoutGlobalScopes()->with('items')->findOrFail($result['sale_id']);
        $fallback = $sale->items->firstWhere('product_name', '2 PASTRI DABA');
        $this->assertNotNull($fallback);
        $this->assertNull($fallback->product_id);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_insufficient_stock_still_creates_the_bill(): void
    {
        [$record, $product] = $this->makeRecord(stock: 3);

        $result = app(RestoreMissedOfflineSaleService::class)->restore($record, false);

        $this->assertSame('created', $result['status']);
        $this->assertEquals(0.0, $product->fresh()->currentStock($record['branch_id']));
        $this->assertNotEmpty($result['warnings']);
        $sale = Sale::withoutGlobalScopes()->with('items')->findOrFail($result['sale_id']);
        $this->assertEquals(20.0, (float) $sale->items->firstWhere('product_id', $product->id)->quantity);
    }

    public function test_wrong_customer_name_is_rejected(): void
    {
        [$record] = $this->makeRecord();
        $record['customer_name'] = 'SOME OTHER CUSTOMER';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('expected');
        app(RestoreMissedOfflineSaleService::class)->restore($record, false);
    }

    public function test_artisan_dry_run_does_not_write(): void
    {
        [$record] = $this->makeRecord();
        $path = storage_path('framework/testing-missed-sale.json');
        File::put($path, json_encode($record));

        $exit = Artisan::call('sales:restore-missed-offline-sale', [
            '--file' => $path,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(0, Sale::withoutGlobalScopes()->count());
        File::delete($path);
    }

    /**
     * @return array{0: array<string, mixed>, 1: \App\Models\Product}
     */
    protected function makeRecord(float $stock = 50): array
    {
        $branch = $this->makeBranch('Restore Branch '.uniqid());
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $customer = $this->makeCustomerForBranch($branch, [
            'name' => 'PESHAWAR CHEMICAL & DISPOSIBLE',
        ]);
        $product = $this->makeProductForBranch($branch, ['name' => '2 PASTRI DABA'], $stock);
        $unit = $product->unit;

        $items = [
            [
                'product_id' => null,
                'product_name' => '1 POND DABA',
                'is_custom' => true,
                'quantity' => 200,
                'unit_id' => $unit->id,
                'selling_price' => 19,
                'discount' => 0,
                'discount_type' => 'percentage',
            ],
            [
                'product_id' => $product->id,
                'product_name' => '2 PASTRI DABA',
                'is_custom' => false,
                'quantity' => 20,
                'unit_id' => $unit->id,
                'selling_price' => 80,
                'discount' => 0,
                'discount_type' => 'percentage',
            ],
        ];

        $record = [
            'client_uuid' => 'ead992f0-edd8-44f4-8535-84895287f56d',
            'customer_id' => $customer->id,
            'customer_name' => 'PESHAWAR CHEMICAL & DISPOSIBLE',
            'branch_id' => $branch->id,
            'user_id' => $admin->id,
            'sale_date' => '2026-08-13',
            'created_at' => '2026-08-13 16:49:59',
            'paid_amount' => 0,
            'expected_total' => 5400.0,
            'notes' => 'Customer: PESHAWAR CHEMICAL & DISPOSIBLE',
            'items' => $items,
        ];

        $this->assertEquals(5400.0, app(RestoreMissedOfflineSaleService::class)->computeTotals($items)['total']);

        return [$record, $product];
    }
}
