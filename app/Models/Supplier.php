<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_id', 'name', 'company_name', 'email', 'phone', 'address',
        'city', 'state', 'country', 'postal_code', 'tax_id', 'is_active'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->supplier_id)) {
                $lastSupplier = Supplier::orderBy('id', 'desc')->first();
                $nextId = $lastSupplier ? $lastSupplier->id + 1 : 1;
                $supplier->supplier_id = 'SN-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierTransaction::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(SupplierBill::class);
    }
}
