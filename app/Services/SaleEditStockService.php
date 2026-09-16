<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLot;
use App\Models\Sale;
use App\Models\SaleItem;

class SaleEditStockService
{
    /**
     * Completed POS sales deduct stock; hold/draft carts do not.
     */
    public function saleHadStockDeducted(Sale $sale): bool
    {
        return $sale->status === 'completed';
    }

    /**
     * Base-unit qty to credit back per product / lot while validating an edit
     * (before stock is actually restored).
     *
     * @return array{products: array<int, float>, lots: array<int, float>}
     */
    public function availabilityCredits(Sale $sale): array
    {
        $credits = ['products' => [], 'lots' => []];

        if (! $this->saleHadStockDeducted($sale)) {
            return $credits;
        }

        $sale->loadMissing('items');

        foreach ($sale->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $qty = $item->baseQuantity();
            if ($qty <= 0) {
                continue;
            }

            $productId = (int) $item->product_id;
            $credits['products'][$productId] = ($credits['products'][$productId] ?? 0) + $qty;

            if ($item->product_lot_id) {
                $lotId = (int) $item->product_lot_id;
                $credits['lots'][$lotId] = ($credits['lots'][$lotId] ?? 0) + $qty;
            }
        }

        return $credits;
    }

    /**
     * Put stock/lots back for every catalog line on a completed sale (edit/delete).
     */
    public function restoreSaleItems(Sale $sale, ?int $branchId = null, string $reason = 'POS edit restore'): void
    {
        if (! $this->saleHadStockDeducted($sale)) {
            return;
        }

        $sale->loadMissing('items.product');

        foreach ($sale->items as $item) {
            $this->restoreItem($item, $sale, $branchId, $reason);
        }
    }

    protected function restoreItem(SaleItem $item, Sale $sale, ?int $branchId, string $reason): void
    {
        if (! $item->product_id || ! $item->product) {
            return;
        }

        $qty = $item->baseQuantity();
        if ($qty <= 0) {
            return;
        }

        $item->product->incrementStock($qty, $branchId, [
            'source_type' => 'sale',
            'source_id' => $sale->id,
            'reason' => $reason,
        ]);

        app(ProductLotService::class)->restoreForSale(
            $item->product,
            $qty,
            $item->product_lot_id ? (int) $item->product_lot_id : null,
            $branchId
        );
    }

    /**
     * Apply only the quantity difference for an invoice edit.
     * Same items/qty => no stock movement. Increase deducts extra; decrease returns stock.
     *
     * @param  array{products: array<int, float>, lots: array<int, float>}  $oldCredits
     * @param  array<int, array{product: Product, qty_in_base: float, lot_id: ?int}>  $newRows
     */
    public function applyNetChange(array $oldCredits, array $newRows, ?int $branchId, int $saleId): void
    {
        $newProducts = [];
        $newLots = [];
        $products = [];

        foreach ($newRows as $row) {
            $product = $row['product'] ?? null;
            if (! $product instanceof Product) {
                continue;
            }
            $productId = (int) $product->id;
            $qty = (float) ($row['qty_in_base'] ?? 0);
            $products[$productId] = $product;
            $newProducts[$productId] = ($newProducts[$productId] ?? 0) + $qty;
            if (! empty($row['lot_id'])) {
                $lotId = (int) $row['lot_id'];
                $newLots[$lotId] = ($newLots[$lotId] ?? 0) + $qty;
            }
        }

        $productIds = array_unique(array_merge(
            array_map('intval', array_keys($oldCredits['products'] ?? [])),
            array_keys($newProducts)
        ));

        foreach ($productIds as $productId) {
            $delta = round(($newProducts[$productId] ?? 0) - (float) ($oldCredits['products'][$productId] ?? 0), 6);
            if (abs($delta) < 0.000001) {
                continue;
            }

            $product = $products[$productId] ?? Product::query()->find($productId);
            if (! $product) {
                continue;
            }

            if ($delta > 0) {
                $product->decrementStock($delta, $branchId, [
                    'source_type' => 'sale',
                    'source_id' => $saleId,
                    'reason' => 'POS edit qty increase',
                ]);
            } else {
                $product->incrementStock(abs($delta), $branchId, [
                    'source_type' => 'sale',
                    'source_id' => $saleId,
                    'reason' => 'POS edit qty decrease',
                ]);
            }
        }

        $lotService = app(ProductLotService::class);
        $lotIds = array_unique(array_merge(
            array_map('intval', array_keys($oldCredits['lots'] ?? [])),
            array_keys($newLots)
        ));

        foreach ($lotIds as $lotId) {
            $delta = round(($newLots[$lotId] ?? 0) - (float) ($oldCredits['lots'][$lotId] ?? 0), 6);
            if (abs($delta) < 0.000001) {
                continue;
            }

            $lot = ProductLot::query()->find($lotId);
            if (! $lot) {
                continue;
            }
            $product = $products[$lot->product_id] ?? Product::query()->find($lot->product_id);
            if (! $product) {
                continue;
            }

            if ($delta > 0) {
                $lotService->decrementForSale($product, $delta, $lotId, $branchId);
            } else {
                $lotService->restoreForSale($product, abs($delta), $lotId, $branchId);
            }
        }

        $unlottedExtraDone = [];
        foreach ($newRows as $row) {
            if (! empty($row['lot_id']) || ! ($row['product'] instanceof Product)) {
                continue;
            }
            $productId = (int) $row['product']->id;
            if (isset($unlottedExtraDone[$productId])) {
                continue;
            }
            $unlottedExtraDone[$productId] = true;
            $delta = round(($newProducts[$productId] ?? 0) - (float) ($oldCredits['products'][$productId] ?? 0), 6);
            if ($delta > 0.000001) {
                $lotService->decrementForSale($row['product'], $delta, null, $branchId);
            }
        }
    }
}
