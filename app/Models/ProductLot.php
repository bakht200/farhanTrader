<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLot extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'supplier_id',
        'supplier_bill_id',
        'unit_id',
        'quantity',
        'purchase_price',
        'extra_price',
        'retail_price',
        'wholesale_price',
        'selling_price',
        'selling_type',
        'received_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'purchase_price' => 'decimal:2',
        'extra_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function displaySellingPrice(): float
    {
        $type = $this->selling_type ?: 'both';
        if ($type === 'wholesale' && (float) $this->wholesale_price > 0) {
            return (float) $this->wholesale_price;
        }
        if (in_array($type, ['retail', 'both'], true) && (float) $this->retail_price > 0) {
            return (float) $this->retail_price;
        }

        return (float) ($this->selling_price ?: $this->purchase_price);
    }
}
