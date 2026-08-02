<?php

namespace App\Services;

use App\Models\UnitConversion;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class UnitConversionService
{
    /**
     * Convert quantity from one unit to another for a specific product
     *
     * @param float $quantity
     * @param int $fromUnitId
     * @param int $toUnitId
     * @param int $productId
     * @return float
     * @throws \Exception
     */
    public function convert(float $quantity, int $fromUnitId, int $toUnitId, int $productId): float
    {
        // Same unit, no conversion needed
        if ($fromUnitId === $toUnitId) {
            return $quantity;
        }

        // Try direct conversion
        $conversion = $this->getConversion($fromUnitId, $toUnitId, $productId);
        if ($conversion) {
            return $quantity * $conversion->conversion_factor;
        }

        // Try reverse conversion
        $reverseConversion = $this->getConversion($toUnitId, $fromUnitId, $productId);
        if ($reverseConversion) {
            return $quantity / $reverseConversion->conversion_factor;
        }

        throw new \Exception("No conversion factor found for product {$productId} between unit {$fromUnitId} and unit {$toUnitId}");
    }

    /**
     * Convert quantity to product's base unit
     *
     * @param float $quantity
     * @param int $unitId
     * @param int $productId
     * @return float
     * @throws \Exception
     */
    public function toBaseUnit(float $quantity, int $unitId, int $productId): float
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = $product->base_unit_id ?? $product->unit_id;

        if (!$baseUnitId) {
            throw new \Exception("Product {$productId} does not have a base unit defined");
        }

        return $this->convert($quantity, $unitId, $baseUnitId, $productId);
    }

    /**
     * Convert quantity from product's base unit to another unit
     *
     * @param float $quantity
     * @param int $unitId
     * @param int $productId
     * @return float
     * @throws \Exception
     */
    public function fromBaseUnit(float $quantity, int $unitId, int $productId): float
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = $product->base_unit_id ?? $product->unit_id;

        if (!$baseUnitId) {
            throw new \Exception("Product {$productId} does not have a base unit defined");
        }

        return $this->convert($quantity, $baseUnitId, $unitId, $productId);
    }

    /**
     * Get available quantity of a product in a specific unit
     *
     * @param int $productId
     * @param int $unitId
     * @return float
     * @throws \Exception
     */
    public function getAvailableQuantity(int $productId, int $unitId): float
    {
        $product = Product::findOrFail($productId);
        $baseUnitId = $product->base_unit_id ?? $product->unit_id;

        if (!$baseUnitId) {
            throw new \Exception("Product {$productId} does not have a base unit defined");
        }

        $stockInBaseUnit = (float) $product->stock_quantity;

        if ($unitId === $baseUnitId) {
            return $stockInBaseUnit;
        }

        return $this->convert($stockInBaseUnit, $baseUnitId, $unitId, $productId);
    }

    /**
     * Check if sufficient stock is available in a specific unit
     *
     * @param int $productId
     * @param float $requestedQuantity
     * @param int $unitId
     * @return bool
     * @throws \Exception
     */
    public function hasSufficientStock(int $productId, float $requestedQuantity, int $unitId): bool
    {
        $availableQuantity = $this->getAvailableQuantity($productId, $unitId);
        return $availableQuantity >= $requestedQuantity;
    }

    /**
     * Get conversion factor between two units for a specific product
     *
     * @param int $fromUnitId
     * @param int $toUnitId
     * @param int $productId
     * @return UnitConversion|null
     */
    protected function getConversion(int $fromUnitId, int $toUnitId, int $productId): ?UnitConversion
    {
        $cacheKey = "unit_conversion_{$productId}_{$fromUnitId}_{$toUnitId}";

        return Cache::remember($cacheKey, 3600, function () use ($fromUnitId, $toUnitId, $productId) {
            return UnitConversion::active()
                ->forProduct($productId)
                ->where('from_unit_id', $fromUnitId)
                ->where('to_unit_id', $toUnitId)
                ->first();
        });
    }
    
    /**
     * Get conversion factor between two units (legacy method for backward compatibility)
     *
     * @param int $fromUnitId
     * @param int $toUnitId
     * @param int|null $productId
     * @return UnitConversion|null
     */
    public function getConversionFactor(int $fromUnitId, int $toUnitId, ?int $productId = null): ?float
    {
        if ($fromUnitId === $toUnitId) {
            return 1.0;
        }

        if (!$productId) {
            throw new \Exception("Product ID is required for unit conversion");
        }

        $conversion = $this->getConversion($fromUnitId, $toUnitId, $productId);
        if ($conversion) {
            return (float) $conversion->conversion_factor;
        }

        // Try reverse conversion
        $reverseConversion = $this->getConversion($toUnitId, $fromUnitId, $productId);
        if ($reverseConversion) {
            return 1.0 / (float) $reverseConversion->conversion_factor;
        }

        throw new \Exception("No conversion factor found for product {$productId} between unit {$fromUnitId} and unit {$toUnitId}");
    }

    /**
     * Get all selling units for a product
     *
     * @param int $productId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSellingUnits(int $productId)
    {
        return ProductUnit::where('product_id', $productId)
            ->active()
            ->with('unit')
            ->get();
    }

    /**
     * Get base unit for a product
     *
     * @param int $productId
     * @return ProductUnit|null
     */
    public function getBaseUnit(int $productId): ?ProductUnit
    {
        return ProductUnit::where('product_id', $productId)
            ->baseUnit()
            ->active()
            ->with('unit')
            ->first();
    }

    /**
     * Validate that a unit can be used for a product
     *
     * @param int $productId
     * @param int $unitId
     * @return bool
     */
    public function isValidUnitForProduct(int $productId, int $unitId): bool
    {
        return ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->active()
            ->exists();
    }

    /**
     * Clear conversion cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}

