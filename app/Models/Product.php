<?php

namespace App\Models;

use App\Services\BranchStockService;
use App\Services\UnitConversionService;
use App\Support\CurrentBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function branchStocks(): HasMany
    {
        return $this->hasMany(BranchProductStock::class);
    }

    public function currentBranchStock(): HasOne
    {
        return $this->hasOne(BranchProductStock::class)
            ->where('branch_id', CurrentBranch::id() ?? CurrentBranch::DEFAULT_BRANCH_ID);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function sellingUnits()
    {
        return $this->productUnits()->active()->with('unit');
    }

    public function getBaseUnitAttribute()
    {
        return $this->productUnits()->baseUnit()->active()->with('unit')->first();
    }

    /**
     * Branch-aware stock quantity for the active branch.
     */
    public function getStockQuantityAttribute($value): float
    {
        if (!$this->exists) {
            return (float) ($value ?? 0);
        }

        if ($this->relationLoaded('currentBranchStock')) {
            return (float) ($this->currentBranchStock->stock_quantity ?? 0);
        }

        return app(BranchStockService::class)->get($this);
    }

    public function currentStock(?int $branchId = null): float
    {
        return app(BranchStockService::class)->get($this, $branchId);
    }

    public function setBranchStock(float $quantity, ?int $branchId = null): void
    {
        app(BranchStockService::class)->set($this, $quantity, $branchId);
        $this->unsetRelation('currentBranchStock');
    }

    public function incrementStock(float $amount, ?int $branchId = null): float
    {
        $qty = app(BranchStockService::class)->increment($this, $amount, $branchId);
        $this->unsetRelation('currentBranchStock');

        return $qty;
    }

    public function decrementStock(float $amount, ?int $branchId = null): float
    {
        $qty = app(BranchStockService::class)->decrement($this, $amount, $branchId);
        $this->unsetRelation('currentBranchStock');

        return $qty;
    }

    public function getStockInUnit(int $unitId): float
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->getAvailableQuantity($this->id, $unitId);
        } catch (\Exception $e) {
            return (float) $this->stock_quantity;
        }
    }

    public function hasSufficientStock(float $quantity, int $unitId): bool
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->hasSufficientStock($this->id, $quantity, $unitId);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function convertToBaseUnit(float $quantity, int $unitId): float
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->toBaseUnit($quantity, $unitId, $this->id);
        } catch (\Exception $e) {
            return $quantity;
        }
    }

    public function convertFromBaseUnit(float $quantity, int $unitId): float
    {
        $service = app(UnitConversionService::class);
        try {
            return $service->fromBaseUnit($quantity, $unitId, $this->id);
        } catch (\Exception $e) {
            return $quantity;
        }
    }
}
