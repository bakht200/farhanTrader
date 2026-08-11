<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BranchShare extends Model
{
    use BelongsToBranch;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'branch_id',
        'year',
        'month',
        'period_start',
        'period_end',
        'status',
        'total_investment',
        'revenue',
        'gross_profit',
        'total_expenses',
        'net_profit',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'closed_at' => 'datetime',
            'total_investment' => 'decimal:2',
            'revenue' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'total_expenses' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    public function investments(): HasMany
    {
        return $this->hasMany(BranchShareInvestment::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function periodLabel(): string
    {
        return sprintf('%s %d', date('F', mktime(0, 0, 0, $this->month, 1)), $this->year);
    }
}
