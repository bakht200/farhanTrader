<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use BelongsToBranch;
    use HasFactory;

    public const ANONYMOUS_NAME = 'Anonymous';

    protected $fillable = [
        'branch_id', 'supplier_id', 'name', 'company_name', 'email', 'phone', 'address',
        'city', 'state', 'country', 'postal_code', 'tax_id', 'is_active', 'is_anonymous',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_anonymous' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (empty($supplier->supplier_id)) {
                // supplier_id is globally unique — ignore branch scope (same class of bug as sale numbers)
                $codes = self::withoutGlobalScopes()
                    ->where('supplier_id', 'like', 'SN-%')
                    ->pluck('supplier_id');

                $maxNumber = 0;
                foreach ($codes as $code) {
                    if (preg_match('/^SN-(\d+)$/', (string) $code, $m)) {
                        $maxNumber = max($maxNumber, (int) $m[1]);
                    }
                }

                $supplier->supplier_id = 'SN-'.str_pad((string) ($maxNumber + 1), 3, '0', STR_PAD_LEFT);
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

    public static function findOrCreateAnonymous(?int $branchId = null): self
    {
        $branchId = $branchId ?? \App\Support\CurrentBranch::requireId();

        $existing = self::query()
            ->where('branch_id', $branchId)
            ->where('is_anonymous', true)
            ->first();

        if ($existing) {
            return $existing;
        }

        return self::query()->create([
            'branch_id' => $branchId,
            'name' => self::ANONYMOUS_NAME,
            'is_anonymous' => true,
            'is_active' => true,
        ]);
    }
}
