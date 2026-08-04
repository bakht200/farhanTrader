<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturn extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'return_number', 'sale_id', 'customer_id', 'user_id',
        'return_date', 'total_amount', 'status', 'reason', 'notes'
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
