<?php

namespace Tests\Feature;

use App\Models\CustomerPaymentLog;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class OfflineSalePaymentSyncTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_offline_sync_keeps_sale_paid_capped_and_applies_extra_to_older_bills(): void
    {
        $branch = $this->makeBranch('Offline Extra Pay Shop');
        $user = $this->makeBranchUser($branch);
        $customer = $this->makeCustomerForBranch($branch, [
            'name' => 'IQBAL KHAN BAKERY / OCH NAIR',
        ]);

        $oldBill = Sale::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'sale_number' => 'SALE-008624',
            'total_amount' => 191750,
            'paid_amount' => 173460,
            'payment_status' => 'partial',
            'status' => 'completed',
        ]);

        $this->actingAs($user);
        $unit = $this->catalog()['unit'];

        $response = $this->postJson(route('sync.push'), [
            'items' => [[
                'client_uuid' => 'f9405e31-d77d-44d2-96cf-9ee61804bbbb',
                'entity' => 'sale',
                'op' => 'create',
                'payload' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'payment_method' => 'cash',
                    'paid_amount' => 197000,
                    'comment' => 'pos order',
                    'items' => [[
                        'is_custom' => '1',
                        'product_name' => 'CITRIC MOTA',
                        'quantity' => 1,
                        'unit_id' => $unit->id,
                        'selling_price' => 125915,
                        'discount_type' => 'percentage',
                        'discount' => 0,
                    ]],
                ],
            ]],
        ]);

        $response->assertOk();
        $this->assertSame('ok', $response->json('results.0.status'), json_encode($response->json('results.0')));

        $newSale = Sale::withoutGlobalScopes()
            ->where('customer_id', $customer->id)
            ->where('sale_number', 'not like', 'ADJ-%')
            ->where('id', '!=', $oldBill->id)
            ->first();

        $this->assertNotNull($newSale);
        $this->assertEquals(125915.0, (float) $newSale->total_amount);
        $this->assertEquals(125915.0, (float) $newSale->paid_amount);
        $this->assertSame('paid', $newSale->payment_status);

        $oldBill->refresh();
        $this->assertEquals(191750.0, (float) $oldBill->paid_amount);
        $this->assertSame('paid', $oldBill->payment_status);

        $this->assertTrue(
            CustomerPaymentLog::withoutGlobalScopes()
                ->where('sale_id', $newSale->id)
                ->where('log_type', 'cash_received')
                ->where('amount', 125915)
                ->exists()
        );

        $this->assertTrue(
            CustomerPaymentLog::withoutGlobalScopes()
                ->where('sale_id', $oldBill->id)
                ->where('description', 'like', '%Previous balance payment from Sale: '.$newSale->sale_number.'%')
                ->where('amount', 18290)
                ->exists()
        );

        $this->assertTrue(
            Sale::withoutGlobalScopes()
                ->where('customer_id', $customer->id)
                ->where('sale_number', 'like', 'ADJ-%')
                ->where('notes', 'like', '%Extra payment from Sale: '.$newSale->sale_number.'%')
                ->where('paid_amount', 71085)
                ->exists()
        );
    }
}
