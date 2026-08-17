<?php

namespace Tests\Support;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentBranch;

trait CreatesBranchContext
{
    protected function makeBranch(string $name): Branch
    {
        return Branch::query()->create([
            'name' => $name,
            'is_active' => true,
        ]);
    }

    protected function makeBranchUser(Branch $branch, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_BRANCH_USER,
            'branch_id' => $branch->id,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeAdmin(?Branch $active = null): User
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        if ($active) {
            $this->actingAs($admin);
            CurrentBranch::setActive($active->id);
        }

        return $admin;
    }

    protected function catalog(): array
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        return compact('category', 'unit');
    }

    protected function makeProductForBranch(Branch $branch, array $overrides = [], float $qty = 10): Product
    {
        $catalog = $this->catalog();

        $product = Product::factory()->create(array_merge([
            'category_id' => $catalog['category']->id,
            'unit_id' => $catalog['unit']->id,
            'base_unit_id' => $catalog['unit']->id,
            'owner_branch_id' => $branch->id,
            'is_active' => true,
        ], $overrides));

        BranchProductStock::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'stock_quantity' => $qty,
            'selling_type' => 'retail',
        ]);

        return $product->fresh();
    }

    protected function makeCustomerForBranch(Branch $branch, array $overrides = []): Customer
    {
        return Customer::factory()->create(array_merge([
            'branch_id' => $branch->id,
        ], $overrides));
    }

    protected function makeSupplierForBranch(Branch $branch, array $overrides = []): Supplier
    {
        return Supplier::factory()->create(array_merge([
            'branch_id' => $branch->id,
        ], $overrides));
    }
}
