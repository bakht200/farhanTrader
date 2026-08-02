<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['name', 'short_name', 'is_active', 'is_base_unit'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_base_unit' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get conversions where this unit is the source
     */
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }

    /**
     * Get conversions where this unit is the target
     */
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }

    /**
     * Get all conversions involving this unit
     */
    public function conversions()
    {
        return UnitConversion::where('from_unit_id', $this->id)
            ->orWhere('to_unit_id', $this->id);
    }

    /**
     * Scope to get base units only
     */
    public function scopeBaseUnits($query)
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
}
