<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'device_user_id',
        'punch_time',
        'punch_type',
        'verify_mode',
        'work_code',
        'notes',
        'is_synced',
    ];

    protected $casts = [
        'punch_time' => 'datetime',
        'is_synced' => 'boolean',
    ];

    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the device that recorded this attendance.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(AttendanceDevice::class, 'device_id');
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('punch_time', $date);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('punch_time', [$startDate, $endDate]);
    }

    /**
     * Scope to get only synced records.
     */
    public function scopeSynced($query)
    {
        return $query->where('is_synced', true);
    }

    /**
     * Scope to get only unsynced records.
     */
    public function scopeUnsynced($query)
    {
        return $query->where('is_synced', false);
    }
}
