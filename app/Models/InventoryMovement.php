<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'branch_id',
        'product_id',
        'delta',
        'qty_before',
        'qty_after',
        'source_type',
        'source_id',
        'user_id',
        'idempotency_key',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'delta' => 'decimal:6',
            'qty_before' => 'decimal:6',
            'qty_after' => 'decimal:6',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
