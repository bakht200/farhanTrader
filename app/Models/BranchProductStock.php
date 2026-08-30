<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchProductStock extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'stock_quantity',
        'selling_type',
        'display_name',
        'purchase_price',
        'selling_price',
        'retail_price',
        'wholesale_price',
        'extra_price',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:6',
            'purchase_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'extra_price' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
