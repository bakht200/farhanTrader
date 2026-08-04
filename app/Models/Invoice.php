<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Services\CustomerBalanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class Invoice extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'invoice_number', 'sale_id', 'customer_id', 'invoice_date', 'due_date',
        'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'paid_amount', 'status', 'notes'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Attach customer-detail aligned balance fields to invoice models.
     * Added fields per invoice:
     * - db_paid_amount
     * - adj_paid_amount
     * - calculated_paid_amount
     * - invoice_previous_balance
     * - total_payable
     * - remaining_balance_due
     */
    public static function attachCalculatedBalances(Collection $invoices): void
    {
        app(CustomerBalanceService::class)->attachInvoiceBalances($invoices);
    }
}
