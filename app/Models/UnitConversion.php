<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitConversion extends Model
{
    protected $fillable = [
        'product_id',
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
        'is_active',
    ];

    protected $casts = [
        'conversion_factor' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product this conversion belongs to
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit this conversion is from
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    /**
     * Get the unit this conversion is to
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    /**
     * Get the reverse conversion (to_unit -> from_unit)
     */
    public function getReverseConversion(): ?self
    {
        return self::where('product_id', $this->product_id)
            ->where('from_unit_id', $this->to_unit_id)
            ->where('to_unit_id', $this->from_unit_id)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Scope to filter by product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get active conversions only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

