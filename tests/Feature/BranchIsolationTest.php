<?php

namespace Tests\Feature;

use App\Models\BranchProductStock;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\SaleItem;
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
        $this->from('/')->get(route('dashboard'))->assertOk();
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
}
