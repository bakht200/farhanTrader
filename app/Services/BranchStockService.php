<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\Product;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\DB;

class BranchStockService
{
    public function branchId(?int $branchId = null): int
    {
        return $branchId ?? CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID;
    }

    public function get(Product|int $product, ?int $branchId = null): float
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);

        $qty = BranchProductStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->value('stock_quantity');

        return (float) ($qty ?? 0);
    }

    public function set(Product|int $product, float $quantity, ?int $branchId = null): BranchProductStock
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);

        return BranchProductStock::query()->updateOrCreate(
            [
                'branch_id' => $branchId,
                'product_id' => $productId,
            ],
            [
                'stock_quantity' => max(0, $quantity),
            ]
        );
    }

    public function increment(Product|int $product, float $amount, ?int $branchId = null): float
    {
        return $this->adjust($product, abs($amount), $branchId);
    }

    public function decrement(Product|int $product, float $amount, ?int $branchId = null): float
    {
        return $this->adjust($product, -abs($amount), $branchId);
    }

    public function adjust(Product|int $product, float $delta, ?int $branchId = null): float
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);

        return DB::transaction(function () use ($productId, $branchId, $delta) {
            $stock = BranchProductStock::query()->firstOrCreate(
                [
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                ],
                [
                    'stock_quantity' => 0,
                ]
            );

            $stock = BranchProductStock::query()
                ->where('id', $stock->id)
                ->lockForUpdate()
                ->first();

            $newQty = max(0, (float) $stock->stock_quantity + $delta);
            $stock->update(['stock_quantity' => $newQty]);

            return $newQty;
        });
    }

    /**
     * Create stock rows for a product across all branches.
     * Current branch gets $initialQuantity; others get 0.
     */
    public function initializeProduct(Product|int $product, float $initialQuantity = 0, ?int $currentBranchId = null): void
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $currentBranchId = $this->branchId($currentBranchId);
        $now = now();

        $rows = Branch::query()->pluck('id')->map(function ($branchId) use ($productId, $currentBranchId, $initialQuantity, $now) {
            return [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'stock_quantity' => (int) $branchId === (int) $currentBranchId ? max(0, $initialQuantity) : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if ($rows !== []) {
            BranchProductStock::query()->upsert(
                $rows,
                ['branch_id', 'product_id'],
                ['stock_quantity', 'updated_at']
            );
        }
    }

    /**
     * Create zero stock rows for every product when a new branch is created.
     */
    public function initializeBranch(Branch|int $branch): void
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        $now = now();
        $productIds = Product::query()->pluck('id');

        $rows = [];
        foreach ($productIds as $productId) {
            $rows[] = [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'stock_quantity' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= 500) {
                BranchProductStock::query()->insertOrIgnore($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            BranchProductStock::query()->insertOrIgnore($rows);
        }
    }

    public function sumForBranch(?int $branchId = null): float
    {
        return (float) BranchProductStock::query()
            ->where('branch_id', $this->branchId($branchId))
            ->sum('stock_quantity');
    }
}
