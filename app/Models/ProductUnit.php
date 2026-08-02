<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'is_base_unit',
        'selling_price',
        'is_active',
    ];

    protected $casts = [
        'is_base_unit' => 'boolean',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product this unit belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Scope to get base unit for a product
     */
    public function scopeBaseUnit($query)
    {
        return $query->where('is_base_unit', true);
    }

    /**
     * Scope to get active units only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get selling units (non-base units)
     */
    public function scopeSellingUnits($query)
    {
        return $query->where('is_base_unit', false);
    }
}

