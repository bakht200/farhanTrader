<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Services\CustomerBalanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Sale extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'sale_number', 'customer_id', 'user_id', 'sale_date',
        'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'paid_amount', 'payment_status', 'status', 'notes'
    ];

    protected $casts = [
        'sale_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Unpaid / carried balance from all sales strictly before this sale (same rules as sale detail / POS print).
     */
    public static function previousBalanceBefore(self $sale): float
    {
        if (!$sale->customer_id) {
            return 0.0;
        }

        app(CustomerBalanceService::class)->attachSaleBalances(collect([$sale]));
        return (float) ($sale->previous_balance ?? 0);
    }

    /**
     * Generate next sequential sale number based on prefix.
     * Format: PREFIX-000001
     *
     * Must ignore the branch global scope: sale_number is unique across ALL branches,
     * so Branch 2 cannot restart at SALE-000001 if Branch 1 already used it.
     *
     * @param  string  $prefix  Prefix for the sale number (e.g., 'SALE', 'ADJ', 'HOLD', 'PB')
     */
    public static function generateSaleNumber(string $prefix = 'SALE'): string
    {
        $query = self::withoutGlobalScopes()
            ->where('sale_number', 'like', $prefix.'-%');

        // Only lock when already inside a DB transaction (POS / sync / payments)
        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $sales = $query->pluck('sale_number')->toArray();

        $maxNumber = 0;

        foreach ($sales as $saleNumber) {
            // Format: PREFIX-000001 (ignore temp/offline ids like SALE-TMP-...)
            $parts = explode('-', (string) $saleNumber);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $number = (int) $parts[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        $nextNumber = $maxNumber + 1;

        return $prefix.'-'.str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Attach customer-detail aligned balance fields on Sale models.
     * This keeps Sales list/report math consistent with Customer detail page.
     */
    public static function attachCalculatedBalances(Collection $sales): void
    {
        app(CustomerBalanceService::class)->attachSaleBalances($sales);
    }
}
