<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $fillable = [
        'parcel_id',
        'user_id',
        'call_type',
        'call_status',
        'notes',
        'called_at',
        'duration_seconds',
    ];

    protected $casts = [
        'called_at' => 'datetime',
    ];

    /**
     * Get the parcel that owns the call log.
     */
    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    /**
     * Get the user that made the call.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by call type.
     */
    public function scopeByCallType($query, $callType)
    {
        return $query->where('call_type', $callType);
    }

    /**
     * Scope a query to filter by call status.
     */
    public function scopeByCallStatus($query, $callStatus)
    {
        return $query->where('call_status', $callStatus);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by parcel.
     */
    public function scopeByParcel($query, $parcelId)
    {
        return $query->where('parcel_id', $parcelId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('called_at', [$startDate, $endDate]);
    }

    /**
     * Check if the call was answered.
     */
    public function wasAnswered(): bool
    {
        return $this->call_status === 'answered';
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
