<?php

namespace App\Models;

use App\Support\CurrentBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBillItem extends Model
{
    protected $fillable = [
        'branch_id', 'supplier_bill_id', 'product_id', 'product_name', 'product_sku',
        'quantity', 'unit_price', 'discount', 'tax', 'total'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (SupplierBillItem $item) {
            if (! empty($item->branch_id)) {
                return;
            }

            if ($item->supplier_bill_id) {
                $item->branch_id = SupplierBill::withoutGlobalScopes()->whereKey($item->supplier_bill_id)->value('branch_id');
            }

            if (empty($item->branch_id)) {
                $item->branch_id = CurrentBranch::id();
            }
        });
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}











