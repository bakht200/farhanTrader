<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingBranchContextException;
use App\Models\Branch;
use App\Models\BranchProductStock;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Support\CurrentBranch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BranchStockService
{
    public function branchId(?int $branchId = null): int
    {
        $resolved = $branchId ?? CurrentBranch::id();

        if (! $resolved) {
            throw new MissingBranchContextException();
        }

        return $resolved;
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

        if (! $existing) {
            $payload['selling_type'] = $this->defaultSellingType($product, $productId);
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

        return $this->defaultSellingType($product, $productId, 'retail');
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

    /**
     * @param  array{
     *     display_name?: ?string,
     *     purchase_price?: ?float,
     *     selling_price?: ?float,
     *     retail_price?: ?float,
     *     wholesale_price?: ?float,
     *     stock_quantity?: ?float,
     *     selling_type?: ?string,
     * }  $overrides
     */
    public function setOverrides(Product|int $product, array $overrides, ?int $branchId = null): BranchProductStock
    {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);

        $existing = BranchProductStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        $defaultType = $this->defaultSellingType($product, $productId);

        $payload = [
            'stock_quantity' => array_key_exists('stock_quantity', $overrides)
                ? max(0, (float) $overrides['stock_quantity'])
                : ($existing ? (float) $existing->stock_quantity : 0),
            'selling_type' => array_key_exists('selling_type', $overrides) && $overrides['selling_type']
                ? (in_array($overrides['selling_type'], ['retail', 'wholesale', 'both'], true)
                    ? $overrides['selling_type']
                    : 'both')
                : ($existing?->selling_type ?: ($defaultType ?: 'both')),
        ];

        foreach (['display_name', 'purchase_price', 'selling_price', 'retail_price', 'wholesale_price'] as $key) {
            if (array_key_exists($key, $overrides)) {
                $payload[$key] = $overrides[$key];
            }
        }

        return BranchProductStock::query()->updateOrCreate(
            [
                'branch_id' => $branchId,
                'product_id' => $productId,
            ],
            $payload
        );
    }

    public function increment(
        Product|int $product,
        float $amount,
        ?int $branchId = null,
        array $movement = []
    ): float {
        return $this->adjust($product, abs($amount), $branchId, $movement);
    }

    public function decrement(
        Product|int $product,
        float $amount,
        ?int $branchId = null,
        array $movement = []
    ): float {
        return $this->adjust($product, -abs($amount), $branchId, $movement);
    }

    /**
     * @param  array{source_type?: ?string, source_id?: ?int, reason?: ?string, idempotency_key?: ?string}  $movement
     */
    public function adjust(
        Product|int $product,
        float $delta,
        ?int $branchId = null,
        array $movement = []
    ): float {
        $productId = $product instanceof Product ? $product->id : $product;
        $branchId = $this->branchId($branchId);

        return DB::transaction(function () use ($product, $productId, $branchId, $delta, $movement) {
            if (! empty($movement['idempotency_key']) && Schema::hasTable('inventory_movements')) {
                $existing = InventoryMovement::query()
                    ->where('idempotency_key', $movement['idempotency_key'])
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    return (float) $existing->qty_after;
                }
            }

            $stock = BranchProductStock::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                if ($delta < 0) {
                    $name = $product instanceof Product ? $product->name : (string) $productId;
                    throw new InsufficientStockException($productId, $name, 0, abs($delta));
                }

                $stock = BranchProductStock::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'stock_quantity' => 0,
                    'selling_type' => $this->defaultSellingType($product, $productId),
                ]);

                $stock = BranchProductStock::query()->whereKey($stock->id)->lockForUpdate()->first();
            }

            $current = (float) $stock->stock_quantity;
            $newQty = $current + $delta;

            if ($newQty < -0.000001) {
                $name = $product instanceof Product
                    ? $product->name
                    : (string) (Product::query()->whereKey($productId)->value('name') ?? $productId);
                throw new InsufficientStockException($productId, $name, $current, abs($delta));
            }

            $newQty = max(0, $newQty);
            $stock->update(['stock_quantity' => $newQty]);

            if (Schema::hasTable('inventory_movements')) {
                InventoryMovement::query()->create([
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'delta' => $delta,
                    'qty_before' => $current,
                    'qty_after' => $newQty,
                    'source_type' => $movement['source_type'] ?? null,
                    'source_id' => $movement['source_id'] ?? null,
                    'user_id' => Auth::id(),
                    'idempotency_key' => $movement['idempotency_key'] ?? null,
                    'reason' => $movement['reason'] ?? null,
                ]);
            }

            return $newQty;
        });
    }

    /**
     * Assign a product to a branch without changing existing quantity.
     */
    public function ensureMembership(Product|int $product, int $branchId, float $initialQuantity = 0): BranchProductStock
    {
        $productId = $product instanceof Product ? $product->id : $product;

        $existing = BranchProductStock::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            return $existing;
        }

        return BranchProductStock::query()->create([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'stock_quantity' => max(0, $initialQuantity),
            'selling_type' => $this->defaultSellingType($product, $productId),
        ]);
    }

    /**
     * Create membership + stock for the current branch only.
     */
    public function initializeProduct(
        Product|int $product,
        float $initialQuantity = 0,
        ?int $currentBranchId = null,
        ?string $sellingType = null,
        bool $sharedCatalog = false
    ): void {
        unset($sharedCatalog);

        $productId = $product instanceof Product ? $product->id : $product;
        $currentBranchId = $this->branchId($currentBranchId);

        if ($sellingType === null) {
            $sellingType = $this->defaultSellingType($product, $productId);
        }
        $sellingType = $sellingType ?: 'both';

        BranchProductStock::query()->updateOrCreate(
            [
                'branch_id' => $currentBranchId,
                'product_id' => $productId,
            ],
            [
                'stock_quantity' => max(0, $initialQuantity),
                'selling_type' => $sellingType,
            ]
        );
    }

    /**
     * Recreate branch_product_stocks for products whose owner branch has no row
     * (e.g. RANGEEN SONF after a branch operational wipe).
     */
    public function restoreMissingOwnerMembership(): int
    {
        if (! Schema::hasTable('branch_product_stocks') || ! Schema::hasColumn('products', 'owner_branch_id')) {
            return 0;
        }

        $orphans = DB::table('products as p')
            ->leftJoin('branch_product_stocks as s', function ($join) {
                $join->on('s.product_id', '=', 'p.id')
                    ->on('s.branch_id', '=', 'p.owner_branch_id');
            })
            ->whereNotNull('p.owner_branch_id')
            ->whereNull('s.id')
            ->select(
                'p.id',
                'p.owner_branch_id',
                'p.selling_type',
                'p.purchase_price',
                'p.selling_price',
                'p.retail_price',
                'p.wholesale_price'
            )
            ->get();

        $now = now();
        $restored = 0;

        foreach ($orphans as $row) {
            $purchase = (float) $row->purchase_price;
            $retail = (float) $row->retail_price;
            $wholesale = (float) $row->wholesale_price;
            $selling = (float) $row->selling_price;
            if ($retail <= 0 && $purchase > 0) {
                $retail = $purchase;
            }
            if ($wholesale <= 0 && $purchase > 0) {
                $wholesale = $purchase;
            }
            if ($selling <= 0) {
                $selling = $retail ?: $wholesale ?: $purchase;
            }

            BranchProductStock::query()->create([
                'branch_id' => (int) $row->owner_branch_id,
                'product_id' => (int) $row->id,
                'stock_quantity' => 0,
                'selling_type' => $row->selling_type ?: 'both',
                'purchase_price' => $purchase > 0 ? $purchase : null,
                'retail_price' => $retail > 0 ? $retail : null,
                'wholesale_price' => $wholesale > 0 ? $wholesale : null,
                'selling_price' => $selling > 0 ? $selling : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ((int) $row->owner_branch_id !== CurrentBranch::DEFAULT_BRANCH_ID
                && $purchase > 0
                && (float) $row->selling_price <= 0
            ) {
                DB::table('products')->where('id', $row->id)->update([
                    'retail_price' => $retail,
                    'wholesale_price' => $wholesale,
                    'selling_price' => $selling,
                    'updated_at' => $now,
                ]);
            }

            $restored++;
        }

        return $restored;
    }

    /**
     * New branches start empty. Products are assigned explicitly by admin.
     */
    public function initializeBranch(Branch|int $branch): void
    {
        // Intentionally empty — no automatic catalog copy.
    }

    public function sumForBranch(?int $branchId = null): float
    {
        $resolved = $branchId ?? CurrentBranch::id();
        $query = BranchProductStock::query();

        if ($resolved) {
            return (float) $query->where('branch_id', $resolved)->sum('stock_quantity');
        }

        if (Auth::user()?->isAdmin()) {
            return (float) $query->sum('stock_quantity');
        }

        throw new MissingBranchContextException();
    }

    private function defaultSellingType(Product|int $product, int $productId, string $fallback = 'both'): string
    {
        if ($product instanceof Product) {
            return $product->getAttributes()['selling_type'] ?? $fallback;
        }

        return Product::query()->where('id', $productId)->value('selling_type') ?? $fallback;
    }
}
