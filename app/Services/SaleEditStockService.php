<?php

namespace App\Services;

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
}
