<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Services\CustomerBalanceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Sale extends Model
{
    use BelongsToBranch;
    use HasFactory;

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
     *
     * Admin / default branch (id 1): PREFIX-000001
     * Other branches: PREFIX-{CODE}000001 (e.g. SALE-LA000001)
     *   where CODE is the first two letters of the branch name.
     *
     * Special prefixes like PB (previous balance) always use the admin style.
     *
     * @param  string  $prefix  Prefix for the sale number (e.g., 'SALE', 'ADJ', 'HOLD', 'PB')
     * @param  int|null  $branchId  Branch to number for (defaults to current branch)
     */
    public static function generateSaleNumber(string $prefix = 'SALE', ?int $branchId = null): string
    {
        $branchId = $branchId
            ?? \App\Support\CurrentBranch::id();

        if (! $branchId) {
            throw new \App\Exceptions\MissingBranchContextException();
        }

        $useBranchFormat = $branchId !== \App\Support\CurrentBranch::DEFAULT_BRANCH_ID
            && ! in_array($prefix, ['PB'], true);

        if (! $useBranchFormat) {
            return self::nextAdminStyleSaleNumber($prefix);
        }

        $branch = Branch::find($branchId);
        $code = $branch ? $branch->saleNumberCode() : 'BR';

        return self::nextBranchStyleSaleNumber($prefix, $code, $branchId);
    }

    /**
     * Admin format: PREFIX-000001 (global sequence for that prefix).
     */
    protected static function nextAdminStyleSaleNumber(string $prefix): string
    {
        $query = self::withoutGlobalScopes()
            ->where('sale_number', 'like', $prefix.'-%');

        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $sales = $query->pluck('sale_number')->toArray();
        $maxNumber = 0;

        foreach ($sales as $saleNumber) {
            // PREFIX-000001 only — ignore branch style (SALE-LA000001) and temp ids
            $parts = explode('-', (string) $saleNumber);
            if (count($parts) === 2 && ctype_digit($parts[1])) {
                $number = (int) $parts[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return $prefix.'-'.str_pad((string) ($maxNumber + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Branch format: PREFIX-LA000001 — sequence starts at 1 per branch + prefix.
     */
    protected static function nextBranchStyleSaleNumber(string $prefix, string $code, int $branchId): string
    {
        $patternPrefix = $prefix.'-'.$code;

        $query = self::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('sale_number', 'like', $patternPrefix.'%');

        if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }

        $sales = $query->pluck('sale_number')->toArray();
        $maxNumber = 0;
        $codeLen = strlen($code);

        foreach ($sales as $saleNumber) {
            $parts = explode('-', (string) $saleNumber, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $suffix = $parts[1];
            if (! str_starts_with($suffix, $code)) {
                continue;
            }

            $numeric = substr($suffix, $codeLen);
            if ($numeric !== '' && ctype_digit($numeric)) {
                $number = (int) $numeric;
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }

        return $patternPrefix.str_pad((string) ($maxNumber + 1), 6, '0', STR_PAD_LEFT);
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
