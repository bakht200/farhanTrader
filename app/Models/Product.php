<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\UnitConversionService;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'brand', 'sku', 'description', 'category_id', 'unit_id', 'base_unit_id',
        'selling_type', 'total_units', 'supplier_name', 'supplier_phone', 'supplier_id',
        'product_type', 'quantity_alert', 'purchase_price', 'selling_price', 
        'retail_price', 'wholesale_price', 'stock_quantity', 'low_stock_threshold', 
        'manufacturer', 'manufactured_date', 'expiry_date', 'image', 'is_active', 'user_id'
    ];

    protected $casts = [
        'manufactured_date' => 'date',
        'expiry_date' => 'date',
        'stock_quantity' => 'decimal:6',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function history()
    {
        return $this->hasMany(ProductHistory::class)->orderBy('transaction_date', 'desc');
    }

    /**
     * Get the base unit relationship
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get all product units (selling units)
     */
    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get active selling units for this product
     */
    public function sellingUnits()
    {
        return $this->productUnits()->active()->with('unit');
    }

    /**
     * Get the base unit for this product
     */
    public function getBaseUnitAttribute()
    {
        return $this->productUnits()->baseUnit()->active()->with('unit')->first();
    }

    /**
     * Get stock quantity in a specific unit
     *
     * @param int $unitId
     * @return float
     */
    public function getStockInUnit(int $unitId): float
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->getAvailableQuantity($this->id, $unitId);
        } catch (\Exception $e) {
            // If conversion fails, return stock in base unit
            return (float) $this->stock_quantity;
        }
    }

    /**
     * Check if product has sufficient stock in a specific unit
     *
     * @param float $quantity
     * @param int $unitId
     * @return bool
     */
    public function hasSufficientStock(float $quantity, int $unitId): bool
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->hasSufficientStock($this->id, $quantity, $unitId);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Convert quantity to base unit
     *
     * @param float $quantity
     * @param int $unitId
     * @return float
     */
    public function convertToBaseUnit(float $quantity, int $unitId): float
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->toBaseUnit($quantity, $unitId, $this->id);
        } catch (\Exception $e) {
            return $quantity; // Fallback to original quantity
        }
    }

    /**
     * Convert quantity from base unit to another unit
     *
     * @param float $quantity
     * @param int $unitId
     * @return float
     */
    public function convertFromBaseUnit(float $quantity, int $unitId): float
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->fromBaseUnit($quantity, $unitId, $this->id);
        } catch (\Exception $e) {
            return $quantity; // Fallback to original quantity
        }
    }
}
