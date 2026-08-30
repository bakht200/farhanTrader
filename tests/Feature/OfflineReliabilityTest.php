<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductLot;
use App\Models\SaleItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

/**
 * Automated coverage for shop PCs that must work online and offline.
 *
 * Manual check on each shop computer (airplane mode after one online login):
 * Shakirbinaziz@gmail.com (Notia), gul.chemical@gmail.com (Ashraf road),
 * farhan.akhtar90@yahoo.com (admin), bakhtbiland@gmail.com (admin).
 * Do not store those passwords in the repo.
 */
class OfflineReliabilityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    public function test_service_worker_keeps_login_and_does_not_follow_navigation_redirects(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertNotFalse($sw);
        $this->assertStringContainsString("cache.add('/login')", $sw);
        $this->assertStringContainsString("redirect: 'manual'", $sw);
        $this->assertStringContainsString("event.data?.type === 'LOGIN'", $sw);
        $this->assertStringContainsString("p === '/suppliers/anonymous-purchase'", $sw);
        $this->assertStringContainsString('data-ftpos-catalog="empty"', $sw);
        $this->assertStringContainsString("'/__ftpos_logged_out'", $sw);
        $this->assertStringContainsString('persistLoggedOut', $sw);
        $this->assertStringNotContainsString('client.navigate', $sw);
        $this->assertStringContainsString('NAV_TIMEOUT_ONLINE_MS = 2500', $sw);
        $this->assertStringContainsString('function browserIsOffline()', $sw);
        $this->assertStringContainsString('function redirectGoesToLogin', $sw);
        $this->assertStringContainsString('Never ask Laravel for a session then', $sw);
        $this->assertStringContainsString("'/__ftpos_vault_session'", $sw);
    }

    public function test_client_logout_keeps_the_service_worker_and_page_caches(): void
    {
        $session = file_get_contents(resource_path('js/offline/session.js'));

        $this->assertNotFalse($session);
        $this->assertStringContainsString('Keep the service worker, page caches, vault hashes, and catalog', $session);
        $this->assertStringNotContainsString('serviceWorker.getRegistrations()', $session);
        $this->assertStringContainsString('persistLastBranchId', $session);
        $this->assertStringContainsString('browserIsOffline', $session);
        $this->assertStringNotContainsString('window.location.reload()', $session);
    }

    public function test_login_page_is_precached_by_the_offline_runtime(): void
    {
        $prefetch = file_get_contents(resource_path('js/offline/prefetch.js'));

        $this->assertNotFalse($prefetch);
        $this->assertStringContainsString("'/login'", $prefetch);
        $this->assertStringContainsString("ftpos-pages-v9", $prefetch);
        $this->assertStringContainsString('uploadPendingToCloud', file_get_contents(resource_path('js/offline/sync.js')));
        $this->assertStringContainsString('Upload to cloud', file_get_contents(resource_path('js/offline/banner.js')));
    }

    public function test_enroll_vault_returns_the_signed_in_users_password_hash(): void
    {
        $branch = $this->makeBranch('Vault Shop');
        $user = $this->makeBranchUser($branch, [
            'email' => 'shop.offline@example.test',
        ]);

        $this->actingAs($user);
        $payload = $this->postJson(route('sync.enroll-vault'), [
            'password' => 'password',
        ])->assertOk()->json();

        $this->assertNotEmpty($payload['password_hash'] ?? null);
        $this->assertSame($user->email, $payload['user']['email'] ?? null);
        $this->assertSame($branch->id, (int) ($payload['user']['branch_id'] ?? 0));
    }

    public function test_admin_can_enroll_vault_before_selecting_a_branch(): void
    {
        $admin = \App\Models\User::factory()->admin()->create([
            'email' => 'admin.offline@example.test',
            'is_active' => true,
        ]);

        $this->actingAs($admin);
        $this->assertNull(\App\Support\CurrentBranch::id($admin));

        $payload = $this->postJson(route('sync.enroll-vault'), [
            'password' => 'password',
        ])->assertOk()->json();

        $this->assertNotEmpty($payload['password_hash'] ?? null);
        $this->assertSame($admin->email, $payload['user']['email'] ?? null);
    }

    public function test_sync_bootstrap_includes_product_lots_for_the_branch(): void
    {
        $branch = $this->makeBranch('Lot Cache');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Lot Supplier']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), [
            'type' => 'credit',
            'create_bill' => '1',
            'transaction_date' => now()->toDateString(),
            'bill_date' => now()->toDateString(),
            'amount' => 1000,
            'products' => [[
                'product_name' => 'OFFLINE LOT SUGAR',
                'quantity' => 5,
                'unit_price' => 200,
                'total' => 1000,
                'category_id' => $catalog['category']->id,
                'unit_id' => $catalog['unit']->id,
                'selling_type' => 'retail',
                'retail_price' => 300,
            ]],
        ])->assertRedirect();
        $this->post(route('suppliers.transactions.store', $supplier), [
            'type' => 'credit',
            'create_bill' => '1',
            'transaction_date' => now()->toDateString(),
            'bill_date' => now()->toDateString(),
            'amount' => 1600,
            'products' => [[
                'product_name' => 'OFFLINE LOT SUGAR',
                'quantity' => 4,
                'unit_price' => 400,
                'total' => 1600,
                'category_id' => $catalog['category']->id,
                'unit_id' => $catalog['unit']->id,
                'selling_type' => 'retail',
                'retail_price' => 500,
            ]],
        ])->assertRedirect();

        $product = Product::query()->where('name', 'OFFLINE LOT SUGAR')->first();
        $this->assertNotNull($product);
        $this->assertEquals(2, ProductLot::query()->where('product_id', $product->id)->count());

        $payload = $this->getJson('/sync/bootstrap')->assertOk()->json();
        $lots = collect($payload['product_lots'] ?? []);
        $this->assertCount(2, $lots->where('product_id', $product->id));
        $this->assertTrue($lots->contains(fn ($lot) => (int) $lot['product_id'] === $product->id && (float) $lot['purchase_price'] === 200.0));
        $this->assertTrue($lots->contains(fn ($lot) => (int) $lot['product_id'] === $product->id && (float) $lot['purchase_price'] === 400.0));
        $this->assertSame($branch->id, $payload['active_branch_id']);
        $this->assertSame(4, (int) ($payload['cache_version'] ?? 0));
    }

    public function test_offline_sale_push_decrements_the_selected_lot(): void
    {
        $branch = $this->makeBranch('Offline Sale Lots');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Lot Supplier']);
        $catalog = $this->catalog();

        $this->actingAs($user);
        $this->post(route('suppliers.transactions.store', $supplier), [
            'type' => 'credit',
            'create_bill' => '1',
            'transaction_date' => now()->toDateString(),
            'bill_date' => now()->toDateString(),
            'amount' => 1000,
            'products' => [[
                'product_name' => 'SYNC LOT SALE',
                'quantity' => 5,
                'unit_price' => 200,
                'total' => 1000,
                'category_id' => $catalog['category']->id,
                'unit_id' => $catalog['unit']->id,
                'selling_type' => 'retail',
                'retail_price' => 300,
            ]],
        ])->assertRedirect();
        $this->post(route('suppliers.transactions.store', $supplier), [
            'type' => 'credit',
            'create_bill' => '1',
            'transaction_date' => now()->toDateString(),
            'bill_date' => now()->toDateString(),
            'amount' => 1600,
            'products' => [[
                'product_name' => 'SYNC LOT SALE',
                'quantity' => 4,
                'unit_price' => 400,
                'total' => 1600,
                'category_id' => $catalog['category']->id,
                'unit_id' => $catalog['unit']->id,
                'selling_type' => 'retail',
                'retail_price' => 500,
            ]],
        ])->assertRedirect();

        $product = Product::query()->where('name', 'SYNC LOT SALE')->first();
        $cheapLot = ProductLot::query()->where('product_id', $product->id)->where('purchase_price', 200)->first();
        $dearLot = ProductLot::query()->where('product_id', $product->id)->where('purchase_price', 400)->first();

        $this->postJson(route('sync.push'), [
            'items' => [[
                'client_uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
                'entity' => 'sale',
                'op' => 'create',
                'payload' => [
                    'payment_method' => 'cash',
                    'paid_amount' => 900,
                    'comment' => 'Offline cheap lot',
                    'items' => [[
                        'product_id' => $product->id,
                        'product_lot_id' => $cheapLot->id,
                        'quantity' => 3,
                        'selling_price' => 300,
                    ]],
                ],
            ]],
        ])->assertOk()->assertJsonPath('results.0.status', 'ok');

        $this->assertEquals(2.0, (float) $cheapLot->fresh()->quantity);
        $this->assertEquals(4.0, (float) $dearLot->fresh()->quantity);
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'product_lot_id' => $cheapLot->id,
            'quantity' => 3,
        ]);
        $this->assertEquals(900.0, (float) SaleItem::query()->where('product_lot_id', $cheapLot->id)->value('total'));
    }

    public function test_offline_runtime_posts_online_login_and_shows_enroll_failure(): void
    {
        $runtime = file_get_contents(resource_path('js/offline/index.js'));

        $this->assertNotFalse($runtime);
        $this->assertStringContainsString('navigator.onLine !== false', $runtime);
        $this->assertStringContainsString('Offline not enabled on this PC', $runtime);
        $this->assertStringContainsString('notifyServiceWorkerLogin', $runtime);
        $this->assertStringContainsString('persistLastBranchId', $runtime);
        $this->assertStringContainsString('warmOfflineShells', $runtime);
        $this->assertStringContainsString('resolveOfflineBranchId', $runtime);
        $this->assertStringContainsString('expandCachedProductsToPosCards', file_get_contents(resource_path('views/pos/index.blade.php')));
        $this->assertStringContainsString('Pick a branch while online', file_get_contents(resource_path('views/pos/index.blade.php')));
        $this->assertStringContainsString('skipped_catalog', file_get_contents(resource_path('js/offline/sync.js')));
    }

    public function test_guest_login_page_opens_for_offline_shell(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign In')->assertSee('Upload to cloud');
        $html = file_get_contents(public_path('offline.html'));
        $this->assertNotFalse($html);
        $this->assertStringContainsString('You are offline', $html);
        $this->assertStringContainsString('href="/login"', $html);
    }

    public function test_offline_supplier_detail_reuses_the_online_show_layout(): void
    {
        $panel = file_get_contents(resource_path('js/offline/supplierPanel.js'));
        $prefetch = file_get_contents(resource_path('js/offline/prefetch.js'));
        $show = file_get_contents(resource_path('views/suppliers/show.blade.php'));
        $create = file_get_contents(resource_path('views/suppliers/transactions/create.blade.php'));

        $this->assertNotFalse($panel);
        $this->assertStringContainsString('data-ftpos-page="supplier-show"', $show);
        $this->assertStringContainsString('data-ftpos-page="supplier-transaction-create"', $create);
        $this->assertStringContainsString('pageShowsSupplier', $panel);
        $this->assertStringContainsString('hydrateSupplierShow', $panel);
        $this->assertStringContainsString('Supplier Information', $panel);
        $this->assertStringContainsString('Supplier Wallet', $panel);
        $this->assertStringContainsString('Add Transaction', $panel);
        $this->assertStringContainsString('Products Supplied', $panel);
        $this->assertStringContainsString('collectSupplierPageUrls', $prefetch);
        $this->assertStringNotContainsString('Offline — saved on this device', $panel);
        $this->assertStringNotContainsString('Save bill on this device', $panel);
        $this->assertStringNotContainsString('data-tab="add-bill"', $panel);
        $this->assertStringNotContainsString("document.addEventListener('click'", $panel);

        $branch = $this->makeBranch('Supplier Show Shop');
        $user = $this->makeBranchUser($branch);
        $supplier = $this->makeSupplierForBranch($branch, ['name' => 'Same Screen Supplier']);
        $this->actingAs($user);

        $this->get(route('suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Supplier Details')
            ->assertSee('Supplier Information')
            ->assertSee('Supplier Wallet')
            ->assertSee('Add Transaction')
            ->assertSee('data-ftpos-page="supplier-show"', false)
            ->assertSee('data-ftpos-supplier-id="'.$supplier->id.'"', false)
            ->assertDontSee('Offline — saved on this device');
    }
}
