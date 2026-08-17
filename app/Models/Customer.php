<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToBranch;
    use HasFactory;

    protected $fillable = [
        'branch_id', 'customer_id', 'name', 'customer_type', 'email', 'phone', 'address', 'city',
        'state', 'country', 'postal_code', 'credit_limit', 'is_active'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_id)) {
                // customer_id is globally unique — ignore branch scope
                $codes = self::withoutGlobalScopes()
                    ->where('customer_id', 'like', 'CN-%')
                    ->pluck('customer_id');

                $maxNumber = 0;
                foreach ($codes as $code) {
                    if (preg_match('/^CN-(\d+)$/', (string) $code, $m)) {
                        $maxNumber = max($maxNumber, (int) $m[1]);
                    }
                }

                $customer->customer_id = 'CN-'.str_pad((string) ($maxNumber + 1), 3, '0', STR_PAD_LEFT);
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
