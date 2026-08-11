<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchShareInvestment extends Model
{
    protected $fillable = [
        'branch_share_id',
        'user_id',
        'amount',
        'share_percent',
        'profit_share',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'share_percent' => 'decimal:4',
            'profit_share' => 'decimal:2',
        ];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(BranchShare::class, 'branch_share_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
