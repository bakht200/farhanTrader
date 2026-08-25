<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\UnitConversion;
use Illuminate\Support\Facades\Cache;

class UnitConversionService
{
    /**
     * Convert quantity from one unit to another for a specific product.
     *
     * @throws \Exception
     */
    public function convert(float $quantity, int $fromUnitId, int $toUnitId, int $productId): float
    {
        if ($fromUnitId === $toUnitId) {
            return $quantity;
        }

        $factor = $this->getConversionFactor($fromUnitId, $toUnitId, $productId);

        return $quantity * $factor;
    }

    /**
     * Convert quantity to the product's base unit.
     *
     * @throws \Exception
     */
    public function toBaseUnit(float $quantity, int $unitId, int $productId): float
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = (int) ($product->base_unit_id ?? $product->unit_id ?? 0);

        if (! $baseUnitId) {
            throw new \Exception("Product {$productId} does not have a base unit defined");
        }

        return $this->convert($quantity, $unitId, $baseUnitId, $productId);
    }

    /**
     * Convert a POS/sync line quantity into the product's stock (base) unit.
     *
     * @throws \RuntimeException
     */
    public function toBaseQuantity(Product $product, float $quantity, ?int $unitId = null): float
    {
        $requestedQty = round(max(0, $quantity), 6);
        $baseUnitId = (int) ($product->base_unit_id ?? $product->unit_id ?? 0);

        if (! $baseUnitId) {
            throw new \RuntimeException("Base unit is not configured for {$product->name}");
        }

        $selectedUnitId = $unitId ?: $baseUnitId;
        if ($selectedUnitId === $baseUnitId) {
            return $requestedQty;
        }

        if (! $this->isValidUnitForProduct((int) $product->id, $selectedUnitId)) {
            throw new \RuntimeException("Selected unit is not configured for {$product->name}");
        }

        try {
            $converted = (float) $this->convert($requestedQty, $selectedUnitId, $baseUnitId, (int) $product->id);
        } catch (\Exception $e) {
            throw new \RuntimeException("Unable to convert quantity for {$product->name}");
        }

        if ($converted <= 0) {
            throw new \RuntimeException("Unable to convert quantity for {$product->name}");
        }

        return round($converted, 6);
    }

    /**
     * How many of $unitId equal one base unit (1 CTN = 48 PKT → 48).
     */
    public function unitsPerBase(Product $product, int $unitId): float
    {
        $baseUnitId = (int) ($product->base_unit_id ?? $product->unit_id ?? 0);
        if (! $baseUnitId || $unitId === $baseUnitId) {
            return 1.0;
        }

        return (float) $this->getConversionFactor($baseUnitId, $unitId, (int) $product->id);
    }

    /**
     * Convert quantity from the product's base unit to another unit.
     *
     * @throws \Exception
     */
    public function fromBaseUnit(float $quantity, int $unitId, int $productId): float
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = (int) ($product->base_unit_id ?? $product->unit_id ?? 0);

        if (! $baseUnitId) {
            throw new \Exception("Product {$productId} does not have a base unit defined");
        }

        return $this->convert($quantity, $baseUnitId, $unitId, $productId);
    }

    /**
     * Get available quantity of a product in a specific unit.
     *
     * @throws \Exception
     */
    public function getAvailableQuantity(int $productId, int $unitId): float
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = (int) ($product->base_unit_id ?? $product->unit_id ?? 0);

        if (! $baseUnitId) {
            throw new \Exception("Product {$productId} does not have a base unit defined");
        }

        $stockInBaseUnit = (float) $product->currentStock();

        if ($unitId === $baseUnitId) {
            return $stockInBaseUnit;
        }

        return $this->convert($stockInBaseUnit, $baseUnitId, $unitId, $productId);
    }

    /**
     * Check if sufficient stock is available in a specific unit.
     *
     * @throws \Exception
     */
    public function hasSufficientStock(int $productId, float $requestedQuantity, int $unitId): bool
    {
        $availableQuantity = $this->getAvailableQuantity($productId, $unitId);

        return $availableQuantity >= $requestedQuantity;
    }

    /**
     * @throws \Exception
     */
    public function getConversionFactor(int $fromUnitId, int $toUnitId, ?int $productId = null): float
    {
        if ($fromUnitId === $toUnitId) {
            return 1.0;
        }

        if (! $productId) {
            throw new \Exception('Product ID is required for unit conversion');
        }

        $conversion = $this->getConversion($fromUnitId, $toUnitId, $productId);
        if ($conversion) {
            return (float) $conversion->conversion_factor;
        }

        $reverseConversion = $this->getConversion($toUnitId, $fromUnitId, $productId);
        if ($reverseConversion) {
            return 1.0 / (float) $reverseConversion->conversion_factor;
        }

        $inferred = $this->inferFactorFromUnitPrices($productId, $fromUnitId, $toUnitId);
        if ($inferred !== null) {
            return $inferred;
        }

        throw new \Exception("No conversion factor found for product {$productId} between unit {$fromUnitId} and unit {$toUnitId}");
    }

    public function getSellingUnits(int $productId)
    {
        return ProductUnit::where('product_id', $productId)
            ->active()
            ->with('unit')
            ->get();
    }

    public function getBaseUnit(int $productId): ?ProductUnit
    {
        return ProductUnit::where('product_id', $productId)
            ->baseUnit()
            ->active()
            ->with('unit')
            ->first();
    }

    public function isValidUnitForProduct(int $productId, int $unitId): bool
    {
        $product = Product::query()->find($productId);
        if ($product) {
            $configured = array_filter([
                (int) ($product->base_unit_id ?? 0),
                (int) ($product->unit_id ?? 0),
            ]);
            if (in_array($unitId, $configured, true)) {
                return true;
            }
        }

        return ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->active()
            ->exists();
    }

    public function clearCache(): void
    {
        Cache::flush();
    }

    protected function getConversion(int $fromUnitId, int $toUnitId, int $productId): ?UnitConversion
    {
        $cacheKey = "unit_conversion_{$productId}_{$fromUnitId}_{$toUnitId}";
        $cached = Cache::get($cacheKey);
        if ($cached instanceof UnitConversion) {
            return $cached;
        }

        $row = UnitConversion::active()
            ->forProduct($productId)
            ->where('from_unit_id', $fromUnitId)
            ->where('to_unit_id', $toUnitId)
            ->first();

        if ($row) {
            Cache::put($cacheKey, $row, 3600);
        }

        return $row;
    }

    /**
     * 1 from-unit = (fromPrice / toPrice) to-units when both selling units have prices.
     */
    protected function inferFactorFromUnitPrices(int $productId, int $fromUnitId, int $toUnitId): ?float
    {
        $fromPrice = $this->unitSellingPrice($productId, $fromUnitId);
        $toPrice = $this->unitSellingPrice($productId, $toUnitId);

        if ($fromPrice > 0.000001 && $toPrice > 0.000001) {
            return $fromPrice / $toPrice;
        }

        return null;
    }

    protected function unitSellingPrice(int $productId, int $unitId): float
    {
        $row = ProductUnit::query()
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->active()
            ->first();

        if ($row && (float) $row->selling_price > 0) {
            return (float) $row->selling_price;
        }

        $product = Product::query()->find($productId);
        if (! $product) {
            return 0.0;
        }

        $baseId = (int) ($product->base_unit_id ?? $product->unit_id ?? 0);
        if ($unitId === $baseId) {
            return (float) ($product->getAttributes()['selling_price'] ?? 0);
        }

        return 0.0;
    }
}
