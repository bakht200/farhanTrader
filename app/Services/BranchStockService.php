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

        $existing = BranchProductStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        $payload = [
            'stock_quantity' => max(0, $quantity),
        ];

        // Preserve selling_type on stock-only updates; seed from product if new row
        if (! $existing) {
            $defaultType = $product instanceof Product
                ? ($product->getAttributes()['selling_type'] ?? 'both')
                : (Product::query()->where('id', $productId)->value('selling_type') ?? 'both');
            $payload['selling_type'] = $defaultType ?: 'both';
        }

        return BranchProductStock::query()->updateOrCreate(
            [
                'branch_id' => $branchId,
                'product_id' => $productId,
            ],
            $payload
        );
    }

    public function getSellingType(Product|int $product, ?int $branchId = null): string
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);

        $type = BranchProductStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->value('selling_type');

        if ($type) {
            return $type;
        }

        if ($product instanceof Product) {
            return $product->getAttributes()['selling_type'] ?? 'retail';
        }

        return Product::query()->where('id', $productId)->value('selling_type') ?? 'retail';
    }

    public function setSellingType(Product|int $product, string $sellingType, ?int $branchId = null): BranchProductStock
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);
        $sellingType = in_array($sellingType, ['retail', 'wholesale', 'both'], true)
            ? $sellingType
            : 'both';

        $existing = BranchProductStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        return BranchProductStock::query()->updateOrCreate(
            [
                'branch_id' => $branchId,
                'product_id' => $productId,
            ],
            [
                'stock_quantity' => $existing ? (float) $existing->stock_quantity : 0,
                'selling_type' => $sellingType,
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

        return DB::transaction(function () use ($product, $productId, $branchId, $delta) {
            $defaultType = $product instanceof Product
                ? ($product->getAttributes()['selling_type'] ?? 'both')
                : (Product::query()->where('id', $productId)->value('selling_type') ?? 'both');

            $stock = BranchProductStock::query()->firstOrCreate(
                [
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                ],
                [
                    'stock_quantity' => 0,
                    'selling_type' => $defaultType ?: 'both',
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
     * All branches get the same initial selling_type (editable later per branch).
     */
    public function initializeProduct(Product|int $product, float $initialQuantity = 0, ?int $currentBranchId = null, ?string $sellingType = null): void
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $currentBranchId = $this->branchId($currentBranchId);
        $now = now();

        if ($sellingType === null) {
            $sellingType = $product instanceof Product
                ? ($product->getAttributes()['selling_type'] ?? 'both')
                : (Product::query()->where('id', $productId)->value('selling_type') ?? 'both');
        }
        $sellingType = $sellingType ?: 'both';

        $rows = Branch::query()->pluck('id')->map(function ($branchId) use ($productId, $currentBranchId, $initialQuantity, $sellingType, $now) {
            return [
                'branch_id' => $branchId,
                'product_id' => $productId,
                'stock_quantity' => (int) $branchId === (int) $currentBranchId ? max(0, $initialQuantity) : 0,
                'selling_type' => $sellingType,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        if ($rows !== []) {
            BranchProductStock::query()->upsert(
                $rows,
                ['branch_id', 'product_id'],
                ['stock_quantity', 'selling_type', 'updated_at']
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
        $products = Product::query()->get(['id', 'selling_type']);

        $rows = [];
        foreach ($products as $product) {
            $rows[] = [
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'stock_quantity' => 0,
                'selling_type' => $product->getAttributes()['selling_type'] ?? 'both',
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
