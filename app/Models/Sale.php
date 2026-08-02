<?php

namespace App\Models;

use App\Services\CustomerBalanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Sale extends Model
{
    protected $fillable = [
        'sale_number', 'customer_id', 'user_id', 'sale_date',
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
     * Generate next sequential sale number based on prefix
     * Format: PREFIX-000001
     * 
     * @param string $prefix Prefix for the sale number (e.g., 'SALE', 'ADJ', 'HOLD', 'PB')
     * @return string Next sequential sale number
     */
    public static function generateSaleNumber(string $prefix = 'SALE'): string
    {
        // Get all sale numbers with this prefix
        $sales = self::where('sale_number', 'like', $prefix . '-%')
            ->pluck('sale_number')
            ->toArray();

        $maxNumber = 0;
        
        foreach ($sales as $saleNumber) {
            // Extract the number part after the prefix and dash
            // Format: PREFIX-000001
            $parts = explode('-', $saleNumber);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $number = (int)$parts[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        // Increment and format with zero padding (6 digits)
        $nextNumber = $maxNumber + 1;
        return $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
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
