<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'quantity', 'unit_id',
        'quantity_in_base_unit', 'unit_price', 'discount', 'tax', 'total'
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'quantity_in_base_unit' => 'decimal:6',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit this item was ordered in
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
