<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_id', 'name', 'customer_type', 'email', 'phone', 'address', 'city',
        'state', 'country', 'postal_code', 'credit_limit', 'is_active'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_id)) {
                // Get the next ID that will be assigned
                $lastCustomer = Customer::orderBy('id', 'desc')->first();
                $nextId = $lastCustomer ? $lastCustomer->id + 1 : 1;
                $customer->customer_id = 'CN-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
