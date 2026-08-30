<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesBranchContext;
use Tests\TestCase;

class OfflinePagesTest extends TestCase
{
    use RefreshDatabase;
    use CreatesBranchContext;

    /**
     * Pages the service worker precaches for offline navigation.
     *
     * @return list<string>
     */
    private function offlineShellPaths(): array
    {
        return [
            '/dashboard',
            '/profile',
            '/products',
            '/products/create',
            '/products/low-stocks',
            '/categories',
            '/units',
            '/expenses',
            '/expenses/create',
            '/sales',
            '/sales/pos',
            '/sales/invoices',
            '/orders',
            '/orders/completed',
            '/orders/pending',
            '/orders/on-hold',
            '/customers',
            '/customers/create',
            '/suppliers',
            '/suppliers/create',
            '/shares',
            '/reports',
            '/reports/profit-loss',
            '/reports/sales-report',
            '/reports/invoice-report',
            '/branches/receipt-settings/edit',
        ];
    }

    public function test_branch_user_can_open_every_offline_shell_page(): void
    {
        $branch = $this->makeBranch('Offline Shop');
        $user = $this->makeBranchUser($branch);
        $this->actingAs($user);

        foreach ($this->offlineShellPaths() as $path) {
            $response = $this->get($path);
            $this->assertTrue(
                in_array($response->status(), [200, 302], true),
                "Offline shell {$path} returned HTTP {$response->status()}"
            );
        }

        $this->get('/')->assertRedirect();
    }

    public function test_admin_pos_and_low_stocks_open_without_selected_branch(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $this->actingAs($admin);
        $this->assertNull(CurrentBranch::id($admin));

        $this->get('/sales/pos')->assertOk()->assertSee('data-ftpos-catalog="no-branch"', false)->assertSee('Upload to cloud');
        $this->get('/products/low-stocks')->assertOk()->assertSee('Upload to cloud');
        $this->get('/branches/receipt-settings/edit')->assertOk();
    }

    public function test_branch_pos_marks_catalog_ready_and_shows_version(): void
    {
        $branch = $this->makeBranch('POS Shop');
        $this->makeProductForBranch($branch, ['name' => 'POS Widget'], 4);
        $user = $this->makeBranchUser($branch);
        $this->actingAs($user);

        $this->get('/sales/pos')
            ->assertOk()
            ->assertSee('data-ftpos-catalog="ok"', false)
            ->assertSee('POS Widget');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Version 2.3');
    }

    public function test_sync_bootstrap_only_includes_the_current_branch_catalog(): void
    {
        $branchA = $this->makeBranch('Cache A');
        $branchB = $this->makeBranch('Cache B');
        $productA = $this->makeProductForBranch($branchA, ['name' => 'Only A Widget'], 8);
        $productB = $this->makeProductForBranch($branchB, ['name' => 'Only B Widget'], 3);
        $userA = $this->makeBranchUser($branchA);

        $this->actingAs($userA);
        $payload = $this->getJson('/sync/bootstrap')->assertOk()->json();

        $ids = collect($payload['products'] ?? [])->pluck('id')->all();
        $this->assertContains($productA->id, $ids);
        $this->assertNotContains($productB->id, $ids);
        $this->assertSame($branchA->id, $payload['active_branch_id']);
    }

    public function test_admin_bootstrap_without_switch_does_not_use_phandu_catalog(): void
    {
        $phandu = \App\Models\Branch::query()->findOrFail(1);
        $other = $this->makeBranch('Other Cache');
        $keep = $this->makeProductForBranch($phandu, ['name' => 'Phandu Offline Item'], 5);
        $hidden = $this->makeProductForBranch($other, ['name' => 'Other Offline Item'], 2);
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin);
        $payload = $this->getJson('/sync/bootstrap')->assertOk()->json();
        $ids = collect($payload['products'] ?? [])->pluck('id')->all();

        $this->assertNotContains($keep->id, $ids);
        $this->assertNotContains($hidden->id, $ids);
        $this->assertNull($payload['active_branch_id']);
        $this->assertTrue(\App\Models\Product::query()->whereKey($keep->id)->exists());
    }

    public function test_admin_bootstrap_uses_the_selected_branch_catalog(): void
    {
        $notia = $this->makeBranch('Notia');
        $ashraf = $this->makeBranch('Ashraf road');
        $notiaProduct = $this->makeProductForBranch($notia, ['name' => 'Notia Item'], 3);
        $ashrafProduct = $this->makeProductForBranch($ashraf, ['name' => 'Ashraf Item'], 3);
        $this->makeAdmin($notia);

        $payload = $this->getJson('/sync/bootstrap')->assertOk()->json();
        $ids = collect($payload['products'] ?? [])->pluck('id')->all();

        $this->assertContains($notiaProduct->id, $ids);
        $this->assertNotContains($ashrafProduct->id, $ids);
        $this->assertSame($notia->id, $payload['active_branch_id']);
    }

    public function test_offline_fallback_page_retries_the_shop_connection(): void
    {
        $html = file_get_contents(public_path('offline.html'));

        $this->assertNotFalse($html);
        $this->assertStringContainsString("fetch('/up'", $html);
        $this->assertStringContainsString('tryRecover()', $html);
        $this->assertStringContainsString('CONNECTIVITY', $html);
    }
}
