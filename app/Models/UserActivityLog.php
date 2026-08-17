<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'ip_address',
        'browser',
        'browser_version',
        'platform',
        'device_type',
        'user_agent',
        'url',
        'route_name',
        'method',
        'page',
        'activity_type',
        'button',
        'process',
        'description',
        'request_data',
        'response_data',
        'status',
        'response_code',
        'execution_time',
        'date',
        'time',
        'session_id',
        'referrer',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'date' => 'date',
        'execution_time' => 'decimal:4',
    ];

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by activity type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by IP address
     */
    public function scopeByIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }
}
