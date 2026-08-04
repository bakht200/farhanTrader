<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductHistory extends Model
{
    use BelongsToBranch;

    protected $table = 'product_history';

    protected $fillable = [
        'branch_id', 'product_id', 'supplier_id', 'supplier_bill_id', 'type',
        'quantity_added', 'old_price', 'new_price',
        'old_stock_quantity', 'new_stock_quantity', 'notes', 'transaction_date'
    ];

    protected $casts = [
        'quantity_added' => 'integer',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'old_stock_quantity' => 'integer',
        'new_stock_quantity' => 'integer',
        'transaction_date' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierBill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class);
    }
}











