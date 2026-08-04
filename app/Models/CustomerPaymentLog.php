<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPaymentLog extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'customer_id', 'user_id', 'log_type', 'sale_id', 'invoice_id',
        'reference_number', 'amount', 'previous_amount', 'new_amount',
        'payment_status', 'description', 'comment', 'changes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'previous_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'changes' => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
