<?php

namespace Tests\Feature;

use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\ProductLot;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use App\Services\BranchStockService;
use App\Support\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_branch_user_cannot_see_other_branch_customers_suppliers_or_expenses(): void
    {
        $branchA = $this->makeBranch('Alpha Store');
        $branchB = $this->makeBranch('Beta Store');
        $userA = $this->makeBranchUser($branchA);
        $userB = $this->makeBranchUser($branchB);

        $this->actingAs($userB);
        $customerB = $this->makeCustomerForBranch($branchB, ['name' => 'Customer B']);
        $supplierB = $this->makeSupplierForBranch($branchB, ['name' => 'Supplier B']);
        Expense::create([
            'branch_id' => $branchB->id,
            'name' => 'Rent B',
            'amount' => 50,
            'expense_date' => now()->toDateString(),
            'user_id' => $userB->id,
        ]);

        $this->actingAs($userA);
        $this->get(route('customers.index'))->assertOk()->assertDontSee('Customer B');
        $this->get(route('customers.show', $customerB))->assertNotFound();
        $this->get(route('suppliers.index'))->assertOk()->assertDontSee('Supplier B');
        $this->get(route('suppliers.show', $supplierB))->assertNotFound();
        $this->get(route('expenses.index'))->assertOk()->assertDontSee('Rent B');
    }

    public function test_pos_catalog_only_includes_membership_products(): void
    {
        $branchA = $this->makeBranch('Alpha POS');
        $branchB = $this->makeBranch('Beta POS');
        $userA = $this->makeBranchUser($branchA);

        $productA = $this->makeProductForBranch($branchA, ['name' => 'Alpha Widget'], 8);
        $this->makeProductForBranch($branchB, ['name' => 'Beta Widget'], 20);

        $this->actingAs($userA);
        $response = $this->get(route('sales.pos.index'));
        $response->assertOk();
        $response->assertSee('Alpha Widget');
        $response->assertDontSee('Beta Widget');
    }

    public function test_product_show_lists_quantities_for_assigned_branches_only(): void
    {
        $branchA = $this->makeBranch('Alpha Qty');
        $branchB = $this->makeBranch('Beta Qty');
        $userA = $this->makeBranchUser($branchA);

        $product = $this->makeProductForBranch($branchA, ['name' => 'Shared Gadget'], 4);
        BranchProductStock::query()->create([
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'stock_quantity' => 11,
            'selling_type' => 'retail',
        ]);

        $this->actingAs($userA);
        $response = $this->get(route('products.show', $product));
        $response->assertOk();
        $response->assertSee('Alpha Qty');
        $response->assertSee('Beta Qty');
        $response->assertSee('4.00');
        $response->assertSee('11.00');
    }

    public function test_product_units_api_rejects_unassigned_product(): void
    {
        $branchA = $this->makeBranch('Alpha Units');
        $branchB = $this->makeBranch('Beta Units');
        $userA = $this->makeBranchUser($branchA);
        $productB = $this->makeProductForBranch($branchB, ['name' => 'Secret'], 3);

        $this->actingAs($userA);
        $this->get(route('products.units', $productB))->assertForbidden();
    }

    public function test_invoice_item_update_cannot_target_another_sale_item(): void
    {
        $branchA = $this->makeBranch('Alpha Inv');
        $branchB = $this->makeBranch('Beta Inv');
        $userA = $this->makeBranchUser($branchA);
        $userB = $this->makeBranchUser($branchB);

        $productA = $this->makeProductForBranch($branchA, ['name' => 'A Item'], 20);
        $productB = $this->makeProductForBranch($branchB, ['name' => 'B Item'], 20);

        $this->actingAs($userB);
        $saleB = Sale::factory()->create([
            'branch_id' => $branchB->id,
            'user_id' => $userB->id,
            'sale_number' => 'SALE-BB000001',
        ]);
        $itemB = SaleItem::create([
            'sale_id' => $saleB->id,
            'branch_id' => $branchB->id,
            'product_id' => $productB->id,
            'product_name' => 'B Item',
            'quantity' => 2,
            'quantity_in_base_unit' => 2,
            'unit_price' => 10,
            'total' => 20,
        ]);

        $this->actingAs($userA);
        $saleA = Sale::factory()->create([
            'branch_id' => $branchA->id,
            'user_id' => $userA->id,
            'sale_number' => 'SALE-AA000001',
        ]);
        $itemA = SaleItem::create([
            'sale_id' => $saleA->id,
            'branch_id' => $branchA->id,
            'product_id' => $productA->id,
            'product_name' => 'A Item',
            'quantity' => 1,
            'quantity_in_base_unit' => 1,
            'unit_price' => 10,
            'total' => 10,
        ]);
        $invoiceA = Invoice::create([
            'branch_id' => $branchA->id,
            'invoice_number' => 'INV-AA1',
            'sale_id' => $saleA->id,
            'invoice_date' => now()->toDateString(),
            'subtotal' => 10,
            'total_amount' => 10,
            'status' => 'sent',
        ]);

        $this->put(route('sales.invoices.update', $invoiceA), [
            'items' => [
                [
                    'id' => $itemA->id,
                    'product_id' => $productA->id,
                    'product_name' => 'A Item',
                    'quantity' => 1,
                    'unit_price' => 10,
                    'discount' => 0,
                    'tax' => 0,
                ],
                [
                    'id' => $itemB->id,
                    'product_id' => $productA->id,
                    'product_name' => 'Hacked',
                    'quantity' => 99,
                    'unit_price' => 1,
                    'discount' => 0,
                    'tax' => 0,
                ],
            ],
            'change_comment' => 'try cross branch item',
        ]);

        $this->assertSame(2.0, (float) $itemB->fresh()->quantity);
        $this->assertSame('B Item', $itemB->fresh()->product_name);
        $this->assertSame(1.0, (float) $itemA->fresh()->quantity);
    }

    public function test_sync_push_ignores_client_branch_id(): void
    {
        $branchA = $this->makeBranch('Alpha Sync');
        $branchB = $this->makeBranch('Beta Sync');
        $userA = $this->makeBranchUser($branchA);

        $this->actingAs($userA);
        $response = $this->postJson(route('sync.push'), [
            'items' => [[
                'client_uuid' => (string) Str::uuid(),
                'entity' => 'customer',
                'op' => 'create',
                'branch_id' => $branchB->id,
                'payload' => [
                    'name' => 'Spoofed Customer',
                    'phone' => '03001234567',
                ],
            ]],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('customers', [
            'name' => 'Spoofed Customer',
            'branch_id' => $branchA->id,
        ]);
        $this->assertDatabaseMissing('customers', [
            'name' => 'Spoofed Customer',
            'branch_id' => $branchB->id,
        ]);
    }

    public function test_null_and_inactive_branch_users_are_denied(): void
    {
        $branch = $this->makeBranch('Live Branch');
        $inactive = $this->makeBranch('Dead Branch');
        $inactive->update(['is_active' => false]);

        $unassigned = User::factory()->create([
            'role' => User::ROLE_BRANCH_USER,
            'branch_id' => null,
            'is_active' => true,
        ]);
        $this->actingAs($unassigned);
        $this->get(route('dashboard'))->assertForbidden();

        $onInactive = $this->makeBranchUser($inactive);
        $this->actingAs($onInactive);
        $this->get(route('dashboard'))->assertForbidden();

        $this->post('/logout');
        $this->post('/login', [
            'email' => $unassigned->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_open_dashboard_without_selected_branch(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin);
        $this->from('/')->get('/')->assertRedirect(route('dashboard'));
        $this->from('/')->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Select a branch')
            ->assertSee('data-ft-branch-required', false)
            ->assertDontSee('Recent Transactions')
            ->assertDontSee('Total Customers');
    }

    public function test_admin_switch_mutates_only_selected_branch(): void
    {
        $branchA = $this->makeBranch('Admin A');
        $branchB = $this->makeBranch('Admin B');
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin);
        $this->post(route('branches.switch'), ['branch_id' => $branchA->id])->assertRedirect();

        Customer::factory()->create([
            'branch_id' => $branchA->id,
            'name' => 'Admin created A',
        ]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Admin created A',
            'branch_id' => $branchA->id,
        ]);

        $this->post(route('branches.switch'), ['branch_id' => $branchB->id]);
        $this->get(route('customers.index'))->assertOk()->assertDontSee('Admin created A');
    }

    public function test_admin_can_open_cross_branch_inventory_overview(): void
    {
        $branchA = $this->makeBranch('Overview A');
        $this->makeProductForBranch($branchA, ['name' => 'Overview Widget'], 6);
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin);
        $this->get(route('products.branch-inventory'))
            ->assertOk()
            ->assertSee('Overview Widget')
            ->assertSee('Overview A');
    }

    public function test_stock_adjust_rejects_insufficient_quantity(): void
    {
        $branch = $this->makeBranch('Stock Branch');
        $user = $this->makeBranchUser($branch);
        $this->actingAs($user);
        CurrentBranch::setActive($branch->id);

        $product = $this->makeProductForBranch($branch, ['name' => 'Finite'], 2);
        $service = app(BranchStockService::class);

        $this->expectException(\App\Exceptions\InsufficientStockException::class);
        $service->decrement($product, 5, $branch->id);
    }

    public function test_activity_log_is_admin_only(): void
    {
        $branch = $this->makeBranch('Logs Branch');
        $user = $this->makeBranchUser($branch);
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($user);
        $this->get(route('user-activities.index'))->assertForbidden();

        $this->actingAs($admin);
        $this->get(route('user-activities.index'))->assertOk();
    }

    public function test_branch_user_cannot_create_categories(): void
    {
        $branch = $this->makeBranch('Cat Branch');
        $user = $this->makeBranchUser($branch);

        $this->actingAs($user);
        $this->post(route('categories.store'), [
            'name' => 'Hacked Category',
        ])->assertForbidden();
    }

    public function test_wipe_operational_data_leaves_phandu_intact_and_hides_admin_products(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $this->assertSame('Phandu', $phandu->name);

        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $peshawar = $this->makeBranch('PESHAWAR CHEMICAL & DISPOSIBLE');
        $phanduUser = $this->makeBranchUser($phandu);
        $ashrafUser = $this->makeBranchUser($ashraf);

        $this->makeCustomerForBranch($phandu, ['name' => 'Keep Customer']);
        $this->makeSupplierForBranch($phandu, ['name' => 'Keep Supplier']);
        $keepProduct = $this->makeProductForBranch($phandu, ['name' => 'Keep Product'], 10);
        Sale::factory()->create([
            'branch_id' => $phandu->id,
            'user_id' => $phanduUser->id,
            'sale_number' => 'SALE-KEEP1',
            'total_amount' => 50,
        ]);

        $this->makeCustomerForBranch($ashraf, ['name' => 'Wipe Customer']);
        $this->makeSupplierForBranch($ashraf, ['name' => 'Wipe Supplier']);
        $this->makeSupplierForBranch($peshawar, ['name' => 'Peshawar Supplier']);
        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $keepProduct->id,
            'stock_quantity' => 4,
            'selling_type' => 'retail',
        ]);
        BranchProductStock::query()->create([
            'branch_id' => $peshawar->id,
            'product_id' => $keepProduct->id,
            'stock_quantity' => 2,
            'selling_type' => 'retail',
        ]);
        Sale::factory()->create([
            'branch_id' => $ashraf->id,
            'user_id' => $ashrafUser->id,
            'sale_number' => 'SALE-WIPE1',
        ]);

        $this->artisan('branches:wipe-operational', [
            '--name' => ['ASHRAF ROAD', 'PESHAWAR CHEMICAL'],
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('customers', ['name' => 'Keep Customer', 'branch_id' => 1]);
        $this->assertDatabaseHas('suppliers', ['name' => 'Keep Supplier', 'branch_id' => 1]);
        $this->assertDatabaseHas('sales', ['sale_number' => 'SALE-KEEP1', 'branch_id' => 1]);
        $this->assertDatabaseHas('products', ['id' => $keepProduct->id, 'name' => 'Keep Product']);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => 1,
            'product_id' => $keepProduct->id,
        ]);
        $this->assertDatabaseMissing('customers', ['name' => 'Wipe Customer']);
        $this->assertDatabaseMissing('suppliers', ['name' => 'Wipe Supplier']);
        $this->assertDatabaseMissing('suppliers', ['name' => 'Peshawar Supplier']);
        $this->assertDatabaseMissing('sales', ['sale_number' => 'SALE-WIPE1']);
        $this->assertSame(0, BranchProductStock::query()->whereIn('branch_id', [$ashraf->id, $peshawar->id])->count());

        $this->actingAs($ashrafUser);
        $this->get(route('products.index'))->assertOk()->assertDontSee('Keep Product');

        $this->artisan('branches:wipe-operational', [
            '--name' => ['Phandu'],
            '--force' => true,
        ])->assertFailed();
        $this->assertDatabaseHas('sales', ['sale_number' => 'SALE-KEEP1']);
    }

    public function test_branch_user_cannot_change_phandu_catalog_product_name_or_price(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);

        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'wholesale_price' => 3250,
            'selling_type' => 'both',
        ], 100);

        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'stock_quantity' => 6,
            'selling_type' => 'both',
        ]);

        $this->actingAs($ashrafUser);
        $this->put(route('products.update', $product), $this->branchProductEditPayload([
            'name' => 'MEETA SODA HACKED',
            'purchase_price' => 1,
            'retail_price' => 2,
            'wholesale_price' => 2,
            'selling_price' => 2,
            'stock_quantity' => 6,
            'selling_type' => 'both',
        ]))->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertSame('MEETA SODA', $product->getAttributes()['name']);
        $this->assertEquals(3070.0, (float) $product->getAttributes()['purchase_price']);
        $this->assertEquals(4000.0, (float) $product->getAttributes()['retail_price']);

        $override = BranchProductStock::query()
            ->where('branch_id', $ashraf->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertSame('MEETA SODA HACKED', $override->display_name);
        $this->assertEquals(1.0, (float) $override->purchase_price);
        $this->assertEquals(2.0, (float) $override->retail_price);
    }

    public function test_sole_membership_on_phandu_catalog_still_does_not_write_master(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);

        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'selling_type' => 'retail',
        ], 100);

        BranchProductStock::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $phandu->id)
            ->delete();

        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'stock_quantity' => 6,
            'selling_type' => 'retail',
        ]);

        $this->assertSame(1, $product->branchStocks()->count());
        $this->assertTrue($product->isPhanduCatalog());

        $this->actingAs($ashrafUser);
        $this->put(route('products.update', $product), $this->branchProductEditPayload([
            'name' => 'BRANCH ONLY NAME',
            'purchase_price' => 99,
            'retail_price' => 120,
            'selling_price' => 120,
            'stock_quantity' => 6,
        ]))->assertRedirect(route('products.index'));

        $this->assertSame('MEETA SODA', $product->fresh()->getAttributes()['name']);
        $this->assertDatabaseHas('branch_product_stocks', [
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'display_name' => 'BRANCH ONLY NAME',
        ]);
    }

    public function test_branch_user_can_still_rename_their_own_exclusive_product(): void
    {
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $product = $this->makeProductForBranch($ashraf, [
            'name' => 'Local Soda',
            'purchase_price' => 10,
            'retail_price' => 20,
            'selling_price' => 20,
            'selling_type' => 'retail',
        ], 5);

        $this->actingAs($ashrafUser);
        $this->put(route('products.update', $product), $this->masterProductEditPayload($product, [
            'name' => 'Local Soda Renamed',
            'stock_quantity' => 5,
        ]))->assertRedirect();

        $this->assertSame('Local Soda Renamed', $product->fresh()->getAttributes()['name']);
    }

    public function test_admin_can_still_update_phandu_catalog_master(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $admin = $this->makeAdmin($phandu);
        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'selling_type' => 'retail',
        ], 100);

        $this->actingAs($admin);
        CurrentBranch::setActive($phandu->id);
        $this->put(route('products.update', $product), $this->masterProductEditPayload($product, [
            'name' => 'MEETA SODA (1*25KG)',
            'purchase_price' => 3300,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'stock_quantity' => 100,
        ]))->assertRedirect();

        $fresh = $product->fresh();
        $this->assertSame('MEETA SODA (1*25KG)', $fresh->getAttributes()['name']);
        $this->assertEquals(3300.0, (float) $fresh->getAttributes()['purchase_price']);
    }

    public function test_branch_user_cannot_write_phandu_catalog_extra_units_stock_or_lots(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $kg = Unit::factory()->create(['name' => 'Kilogram', 'short_name' => 'KG']);

        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'extra_price' => 12,
            'selling_type' => 'retail',
        ], 100);
        ProductUnit::query()->create([
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'is_base_unit' => true,
            'selling_price' => 4000,
            'is_active' => true,
        ]);
        $phanduLot = ProductLot::query()->create([
            'branch_id' => $phandu->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 100,
            'purchase_price' => 3070,
            'extra_price' => 12,
            'retail_price' => 4000,
            'wholesale_price' => 4000,
            'selling_price' => 4000,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);
        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'stock_quantity' => 6,
            'selling_type' => 'retail',
        ]);
        $ashrafLot = ProductLot::query()->create([
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
        $this->put(route('products.update', $product), $this->branchProductEditPayload([
            'name' => 'MEETA SODA HACKED',
            'purchase_price' => 1,
            'retail_price' => 2,
            'selling_price' => 2,
            'stock_quantity' => 999,
            'add_received_qty' => 4,
            'add_received_unit_id' => $product->unit_id,
            'add_lot_id' => $ashrafLot->id,
            'units' => [[
                'unit_id' => $kg->id,
                'is_base_unit' => 0,
                'retail_price' => 1,
            ]],
            'conversions' => [[
                'from_unit_id' => $product->unit_id,
                'to_unit_id' => $kg->id,
                'factor' => 99,
            ]],
        ]))->assertRedirect(route('products.index'));

        $fresh = $product->fresh();
        $this->assertSame('MEETA SODA', $fresh->getAttributes()['name']);
        $this->assertEquals(3070.0, (float) $fresh->getAttributes()['purchase_price']);
        $this->assertEquals(12.0, (float) $fresh->getAttributes()['extra_price']);
        $this->assertEquals(100.0, (float) BranchProductStock::query()
            ->where('branch_id', $phandu->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(10.0, (float) BranchProductStock::query()
            ->where('branch_id', $ashraf->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(100.0, (float) $phanduLot->fresh()->quantity);
        $this->assertEquals(10.0, (float) $ashrafLot->fresh()->quantity);
        $this->assertSame(1, ProductUnit::query()->where('product_id', $product->id)->count());
        $this->assertDatabaseMissing('unit_conversions', ['product_id' => $product->id]);
    }

    public function test_branch_user_cannot_add_received_stock_onto_admin_lot(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
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
        $phanduLot = ProductLot::query()->create([
            'branch_id' => $phandu->id,
            'product_id' => $product->id,
            'unit_id' => $product->unit_id,
            'quantity' => 100,
            'purchase_price' => 3070,
            'extra_price' => 0,
            'retail_price' => 4000,
            'wholesale_price' => 4000,
            'selling_price' => 4000,
            'selling_type' => 'retail',
            'received_at' => now(),
        ]);

        $this->actingAs($ashrafUser);
        $this->from(route('products.edit', $product))
            ->put(route('products.update', $product), $this->branchProductEditPayload([
                'name' => 'MEETA SODA',
                'purchase_price' => 3070,
                'retail_price' => 4000,
                'selling_price' => 4000,
                'stock_quantity' => 6,
                'add_received_qty' => 5,
                'add_received_unit_id' => $product->unit_id,
                'add_lot_id' => $phanduLot->id,
            ]))
            ->assertRedirect(route('products.edit', $product))
            ->assertSessionHasErrors('add_lot_id');

        $this->assertEquals(100.0, (float) $phanduLot->fresh()->quantity);
        $this->assertEquals(100.0, (float) BranchProductStock::query()
            ->where('branch_id', $phandu->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(6.0, (float) BranchProductStock::query()
            ->where('branch_id', $ashraf->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
    }

    public function test_branch_supplier_bill_extra_does_not_change_phandu_catalog_extra(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $product = $this->makeProductForBranch($phandu, [
            'name' => 'MEETA SODA',
            'purchase_price' => 3070,
            'retail_price' => 4000,
            'selling_price' => 4000,
            'extra_price' => 8,
            'selling_type' => 'retail',
        ], 100);
        $supplier = $this->makeSupplierForBranch($ashraf, ['name' => 'Ashraf Supplier']);

        $this->actingAs($ashrafUser);
        $this->post(route('suppliers.transactions.store', $supplier), [
            'type' => 'credit',
            'create_bill' => '1',
            'transaction_date' => now()->toDateString(),
            'bill_date' => now()->toDateString(),
            'products' => [[
                'product_id' => $product->id,
                'product_name' => 'MEETA SODA BRANCH',
                'quantity' => 2,
                'unit_price' => 3500,
                'extra_price' => 99.5,
                'total' => 7000,
            ]],
        ])->assertRedirect(route('suppliers.show', $supplier));

        $fresh = $product->fresh();
        $this->assertSame('MEETA SODA', $fresh->getAttributes()['name']);
        $this->assertEquals(3070.0, (float) $fresh->getAttributes()['purchase_price']);
        $this->assertEquals(8.0, (float) $fresh->getAttributes()['extra_price']);
        $this->assertEquals(100.0, (float) BranchProductStock::query()
            ->where('branch_id', $phandu->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(2.0, (float) BranchProductStock::query()
            ->where('branch_id', $ashraf->id)
            ->where('product_id', $product->id)
            ->value('stock_quantity'));
        $this->assertEquals(99.5, (float) BranchProductStock::query()
            ->where('branch_id', $ashraf->id)
            ->where('product_id', $product->id)
            ->value('extra_price'));
        $this->assertEquals(0, ProductLot::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $phandu->id)
            ->count());
    }

    public function test_branch_user_cannot_change_or_see_admin_suppliers_categories_or_units(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $adminSupplier = $this->makeSupplierForBranch($phandu, ['name' => 'Phandu Supplier']);
        $category = Category::factory()->create(['name' => 'Admin Category']);
        $unit = Unit::factory()->create(['name' => 'Admin Unit', 'short_name' => 'AU']);

        $this->actingAs($ashrafUser);
        $this->put(route('suppliers.update', $adminSupplier), [
            'name' => 'Hacked Supplier',
            'phone' => '03001111111',
        ])->assertNotFound();
        $this->delete(route('suppliers.destroy', $adminSupplier))->assertNotFound();
        $this->assertSame('Phandu Supplier', $adminSupplier->fresh()->name);

        $this->post(route('categories.store'), ['name' => 'Branch Category', 'is_active' => 1])->assertForbidden();
        $this->put(route('categories.update', $category), ['name' => 'Hacked Category'])->assertForbidden();
        $this->assertSame('Admin Category', $category->fresh()->name);

        $this->post(route('units.store'), ['name' => 'Branch Unit', 'short_name' => 'BU', 'is_active' => 1])->assertForbidden();
        $this->put(route('units.update', $unit), ['name' => 'Hacked Unit', 'short_name' => 'HU'])->assertForbidden();
        $this->assertSame('Admin Unit', $unit->fresh()->name);
    }

    public function test_branch_user_cannot_delete_phandu_catalog_product(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $product = $this->makeProductForBranch($phandu, ['name' => 'MEETA SODA'], 100);
        BranchProductStock::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $phandu->id)
            ->delete();
        BranchProductStock::query()->create([
            'branch_id' => $ashraf->id,
            'product_id' => $product->id,
            'stock_quantity' => 6,
            'selling_type' => 'retail',
        ]);

        $this->actingAs($ashrafUser);
        $this->delete(route('products.destroy', $product))->assertForbidden();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'MEETA SODA']);
    }

    public function test_branch_owned_product_and_supplier_do_not_appear_on_phandu(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $ashraf = $this->makeBranch('ASHRAF ROAD');
        $ashrafUser = $this->makeBranchUser($ashraf);
        $admin = $this->makeAdmin($phandu);

        $this->actingAs($ashrafUser);
        $localProduct = $this->makeProductForBranch($ashraf, ['name' => 'ASHRAF ONLY SUGAR'], 3);
        $localSupplier = $this->makeSupplierForBranch($ashraf, ['name' => 'ASHRAF ONLY SUPPLIER']);

        $this->actingAs($admin);
        CurrentBranch::setActive($phandu->id);
        $this->get(route('products.index'))->assertOk()->assertDontSee('ASHRAF ONLY SUGAR');
        $this->get(route('suppliers.index'))->assertOk()->assertDontSee('ASHRAF ONLY SUPPLIER');
        $this->assertSame($ashraf->id, (int) $localProduct->fresh()->owner_branch_id);
        $this->assertSame($ashraf->id, (int) $localSupplier->fresh()->branch_id);
    }

    protected function branchProductEditPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Renamed',
            'selling_type' => 'retail',
            'purchase_price' => 10,
            'retail_price' => 15,
            'wholesale_price' => 12,
            'selling_price' => 15,
            'stock_quantity' => 1,
        ], $overrides);
    }

    protected function masterProductEditPayload(\App\Models\Product $product, array $overrides = []): array
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
