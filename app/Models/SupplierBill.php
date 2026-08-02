<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierBill extends Model
{
    protected $fillable = [
        'supplier_id', 'bill_number', 'bill_amount', 'bill_date', 
        'description', 'reference_number', 'bill_image'
    ];

    protected $casts = [
        'bill_date' => 'date',
        'bill_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierBillItem::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->transactions()->where('type', 'debit')->sum('amount'); // Payments are debits
    }

    public function getRemainingAmountAttribute()
    {
        return $this->bill_amount - $this->paid_amount;
    }
}
