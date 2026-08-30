<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Supplier;
use App\Support\CurrentBranch;
use Illuminate\Support\Collection;

class ProductLotService
{
    /**
     * Keep lot purchase/extra in sync when a supplier bill line is edited.
     */
    public function syncLotsFromBillLine(Product $product, int $billId, array $line): void
    {
        $purchase = round((float) ($line['unit_price'] ?? $line['purchase_price'] ?? 0), 2);
        $extra = round((float) ($line['extra_price'] ?? 0), 2);

        ProductLot::query()
            ->where('supplier_bill_id', $billId)
            ->where('product_id', $product->id)
            ->update([
                'purchase_price' => $purchase,
                'extra_price' => $extra,
            ]);
    }

    /**
     * POS/sync extra when a lot still carries a bad value from before bill-edit lot sync.
     */
    public function effectiveExtraPrice(Product $product, ProductLot $lot): float
    {
        $master = round((float) ($product->getAttributes()['extra_price'] ?? 0), 2);
        $lotExtra = round((float) $lot->extra_price, 2);
        $purchase = round((float) $lot->purchase_price, 2);

        if ($master <= 0 || $lotExtra === $master) {
            return $lotExtra;
        }

        if ($purchase > 0 && $lotExtra > ($purchase * 2)) {
            return $master;
        }

        return $lotExtra;
    }

    /**
     * @return int Number of lots corrected in the database
     */
    public function repairStaleLotExtraPrices(?int $branchId = null): int
    {
        $query = ProductLot::query()->with('product');
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $fixed = 0;
        foreach ($query->get() as $lot) {
            $product = $lot->product;
            if (! $product) {
                continue;
            }
            $effective = $this->effectiveExtraPrice($product, $lot);
            if (round((float) $lot->extra_price, 2) !== $effective) {
                $lot->extra_price = $effective;
                $lot->save();
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Add received qty as a stock lot. Same product + same purchase/extra/unit
     * on this branch adds to that lot. A different purchase rate creates a new lot
     * so POS can show old and new rates side by side.
     */
    public function receive(
        Product $product,
        array $line,
        Supplier $supplier,
        int $branchId,
        ?int $billId = null
    ): ProductLot {
        $qty = (float) ($line['quantity'] ?? 0);
        $purchase = round((float) ($line['unit_price'] ?? $line['purchase_price'] ?? 0), 2);
        $extra = round((float) ($line['extra_price'] ?? 0), 2);
        $sellingType = $line['selling_type'] ?? ($product->getAttributes()['selling_type'] ?? 'both');
        $retail = (float) ($line['retail_price'] ?? 0);
        $wholesale = (float) ($line['wholesale_price'] ?? 0);
        if ($retail <= 0) {
            $retail = $purchase;
        }
        if ($wholesale <= 0) {
            $wholesale = $purchase;
        }
        $selling = $retail > 0 ? $retail : $wholesale;
        $unitId = (int) ($line['unit_id'] ?? $product->base_unit_id ?? $product->unit_id);

        $lot = ProductLot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->where('unit_id', $unitId)
            ->where('purchase_price', $purchase)
            ->where('extra_price', $extra)
            ->first();

        if ($lot) {
            $lot->quantity = (float) $lot->quantity + max(0, $qty);
            $lot->retail_price = $retail;
            $lot->wholesale_price = $wholesale;
            $lot->selling_price = $selling;
            $lot->selling_type = $sellingType;
            if ($billId) {
                $lot->supplier_bill_id = $billId;
            }
            $lot->supplier_id = $supplier->id;
            $lot->save();

            return $lot->fresh();
        }

        return ProductLot::query()->create([
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'supplier_bill_id' => $billId,
            'unit_id' => $unitId ?: null,
            'quantity' => max(0, $qty),
            'purchase_price' => $purchase,
            'extra_price' => $extra,
            'retail_price' => $retail,
            'wholesale_price' => $wholesale,
            'selling_price' => $selling,
            'selling_type' => $sellingType,
            'received_at' => now(),
        ]);
    }

    /**
     * Freeze leftover branch stock as a lot at the current (old) rate
     * before a bill overwrites purchase price. Otherwise POS only shows
     * the new rate and hides the old quantity.
     */
    public function preserveUnlottedStock(Product $product, int $branchId): void
    {
        $stock = (float) $product->currentStock($branchId);
        if ($stock <= 0.000001) {
            return;
        }

        $lotQty = (float) ProductLot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->sum('quantity');
        $gap = round($stock - $lotQty, 6);
        if ($gap <= 0.000001) {
            return;
        }

        $purchase = round((float) $product->purchase_price, 2);
        $extra = round((float) ($product->extra_price ?? 0), 2);
        $unitId = (int) ($product->base_unit_id ?? $product->unit_id);
        $retail = (float) ($product->retail_price ?? $product->selling_price ?? $purchase);
        $wholesale = (float) ($product->wholesale_price ?? $product->selling_price ?? $purchase);
        $selling = (float) ($product->selling_price ?? $retail);
        $sellingType = $product->selling_type ?? 'both';

        $lot = ProductLot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->where('purchase_price', $purchase)
            ->where('extra_price', $extra)
            ->where(function ($query) use ($unitId) {
                $query->where('unit_id', $unitId)->orWhereNull('unit_id');
            })
            ->orderBy('id')
            ->first();

        if ($lot) {
            $lot->quantity = (float) $lot->quantity + $gap;
            $lot->save();

            return;
        }

        ProductLot::query()->create([
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id ?: null,
            'unit_id' => $unitId ?: null,
            'quantity' => $gap,
            'purchase_price' => $purchase,
            'extra_price' => $extra,
            'retail_price' => $retail,
            'wholesale_price' => $wholesale,
            'selling_price' => $selling,
            'selling_type' => $sellingType,
            'received_at' => now(),
        ]);
    }

    public function decrementForSale(Product $product, float $qtyInBase, ?int $lotId, ?int $branchId = null): void
    {
        $branchId = $branchId ?? CurrentBranch::requireId();
        $remaining = max(0, $qtyInBase);

        if ($lotId) {
            $lot = ProductLot::query()
                ->where('id', $lotId)
                ->where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->first();
            if ($lot) {
                $take = min((float) $lot->quantity, $remaining);
                $lot->quantity = max(0, (float) $lot->quantity - $take);
                $lot->save();
                $remaining -= $take;
            }
        }

        if ($remaining > 0.000001) {
            $lots = ProductLot::query()
                ->where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->where('quantity', '>', 0)
                ->orderBy('id')
                ->get();
            foreach ($lots as $lot) {
                if ($remaining <= 0.000001) {
                    break;
                }
                $take = min((float) $lot->quantity, $remaining);
                $lot->quantity = max(0, (float) $lot->quantity - $take);
                $lot->save();
                $remaining -= $take;
            }
        }
    }

    /**
     * Add already-converted base-unit quantity onto an existing lot (same price).
     * If this product has no lots yet, one is created from current prices and
     * includes any un-lotted stock so POS still matches branch stock.
     */
    public function addReceivedQuantity(Product $product, float $qtyInBase, ?int $lotId = null, ?int $branchId = null): ProductLot
    {
        $branchId = $branchId ?? CurrentBranch::requireId();
        $qtyInBase = max(0, $qtyInBase);

        if ($lotId) {
            $lot = ProductLot::query()
                ->where('id', $lotId)
                ->where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->first();
            if (! $lot) {
                throw new \InvalidArgumentException('Select which stock lot to add this quantity to.');
            }
            $lot->quantity = (float) $lot->quantity + $qtyInBase;
            $lot->save();

            return $lot->fresh();
        }

        $lots = ProductLot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->get();

        if ($lots->count() > 1) {
            throw new \InvalidArgumentException('This product has more than one purchase rate. Select which lot to add the quantity to.');
        }

        if ($lots->count() === 1) {
            $lot = $lots->first();
            $lot->quantity = (float) $lot->quantity + $qtyInBase;
            $lot->save();

            return $lot->fresh();
        }

        $stockNow = $product->currentStock($branchId);

        return ProductLot::query()->create([
            'branch_id' => $branchId,
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id ?: null,
            'unit_id' => $product->base_unit_id ?? $product->unit_id,
            'quantity' => max($qtyInBase, $stockNow),
            'purchase_price' => round((float) $product->purchase_price, 2),
            'extra_price' => round((float) ($product->extra_price ?? 0), 2),
            'retail_price' => round((float) ($product->retail_price ?? $product->selling_price ?? 0), 2),
            'wholesale_price' => round((float) ($product->wholesale_price ?? $product->selling_price ?? 0), 2),
            'selling_price' => round((float) ($product->selling_price ?? $product->retail_price ?? 0), 2),
            'selling_type' => $product->selling_type ?? 'both',
            'received_at' => now(),
        ]);
    }

    public function restoreForSale(Product $product, float $qtyInBase, ?int $lotId, ?int $branchId = null): void
    {
        $branchId = $branchId ?? CurrentBranch::requireId();
        if ($lotId) {
            $lot = ProductLot::query()
                ->where('id', $lotId)
                ->where('product_id', $product->id)
                ->where('branch_id', $branchId)
                ->first();
            if ($lot) {
                $lot->quantity = (float) $lot->quantity + max(0, $qtyInBase);
                $lot->save();

                return;
            }
        }

        $lot = ProductLot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->first();
        if ($lot) {
            $lot->quantity = (float) $lot->quantity + max(0, $qtyInBase);
            $lot->save();
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Product>  $products
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function posCards(Collection $products, int $branchId): Collection
    {
        $lots = ProductLot::query()
            ->where('branch_id', $branchId)
            ->whereIn('product_id', $products->pluck('id'))
            ->where('quantity', '>', 0)
            ->orderBy('purchase_price')
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        $cards = collect();
        foreach ($products as $product) {
            $productLots = $lots->get($product->id, collect());
            if ($productLots->isNotEmpty()) {
                foreach ($productLots as $lot) {
                    $cards->push($this->cardFromLot($product, $lot));
                }
            } else {
                $cards->push($this->cardFromProduct($product));
            }
        }

        return $cards->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function cardFromProduct(Product $product): array
    {
        $baseUnit = $product->base_unit_id ?? $product->unit_id;
        $baseUnitName = $product->baseUnit->short_name ?? $product->unit->short_name ?? '';

        return [
            'id' => $product->id,
            'lot_id' => null,
            'name' => $product->name,
            'sku' => $product->sku,
            'category_id' => $product->category_id,
            'brand' => $product->brand ?? '',
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'retail_price' => $product->retail_price ?? $product->selling_price,
            'wholesale_price' => $product->wholesale_price ?? $product->selling_price,
            'extra_price' => (float) ($product->extra_price ?? 0),
            'selling_type' => $product->selling_type ?? 'retail',
            'stock_quantity' => $product->stock_quantity,
            'unit_id' => $baseUnit,
            'unit_name' => $baseUnitName,
            'base_unit_id' => $baseUnit,
            'selling_units' => $product->sellingUnitsForPos(),
            'image' => $product->image ? asset('storage/'.$product->image) : null,
            'lot_label' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cardFromLot(Product $product, ProductLot $lot): array
    {
        $card = $this->cardFromProduct($product);
        $lotSelling = $lot->displaySellingPrice();
        $productBase = $product->branchPosBasePrice();
        $ratio = $productBase > 0 ? ($lotSelling / $productBase) : 1.0;
        $units = collect($card['selling_units'])->map(function ($unit) use ($ratio, $lotSelling) {
            $isBase = ! empty($unit['is_base_unit']);
            $unit['selling_price'] = $isBase
                ? $lotSelling
                : round(((float) ($unit['selling_price'] ?? 0)) * $ratio, 2);

            return $unit;
        })->all();

        $card['lot_id'] = $lot->id;
        $card['purchase_price'] = (float) $lot->purchase_price;
        $card['extra_price'] = $this->effectiveExtraPrice($product, $lot);
        $card['retail_price'] = (float) $lot->retail_price;
        $card['wholesale_price'] = (float) $lot->wholesale_price;
        $card['selling_price'] = $lotSelling;
        $card['selling_type'] = $lot->selling_type ?: $card['selling_type'];
        $card['stock_quantity'] = (float) $lot->quantity;
        $card['selling_units'] = $units;
        $card['lot_label'] = 'Rate PKR '.number_format((float) $lot->purchase_price, 2);

        return $card;
    }

    /**
     * Apply a product-edit selling change only to lots at that purchase rate.
     * Older lots at other rates keep their own selling prices.
     */
    public function updateSellingForPurchaseRate(Product $product, int $branchId, float $purchasePrice, array $prices): void
    {
        $purchase = round($purchasePrice, 2);
        $lots = ProductLot::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $product->id)
            ->where('purchase_price', $purchase)
            ->get();

        if ($lots->isEmpty()) {
            return;
        }

        $retail = (float) ($prices['retail_price'] ?? 0);
        $wholesale = (float) ($prices['wholesale_price'] ?? 0);
        $selling = (float) ($prices['selling_price'] ?? 0);
        $sellingType = $prices['selling_type'] ?? null;

        foreach ($lots as $lot) {
            if ($retail > 0) {
                $lot->retail_price = $retail;
            }
            if ($wholesale > 0) {
                $lot->wholesale_price = $wholesale;
            }
            if ($selling > 0) {
                $lot->selling_price = $selling;
            }
            if ($sellingType) {
                $lot->selling_type = $sellingType;
            }
            $lot->save();
        }
    }
}
