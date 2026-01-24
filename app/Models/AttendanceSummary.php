<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'first_check_in',
        'last_check_out',
        'total_work_minutes',
        'total_break_minutes',
        'overtime_minutes',
        'late_minutes',
        'early_departure_minutes',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'first_check_in' => 'datetime',
        'last_check_out' => 'datetime',
    ];

    /**
     * Get the user that owns the attendance summary.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by month.
     */
    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('date', $year)
            ->whereMonth('date', $month);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeForDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get total work hours.
     */
    public function getTotalWorkHoursAttribute(): float
    {
        return round($this->total_work_minutes / 60, 2);
    }

    /**
     * Get total break hours.
     */
    public function getTotalBreakHoursAttribute(): float
    {
        return round($this->total_break_minutes / 60, 2);
    }

    /**
     * Get overtime hours.
     */
    public function getOvertimeHoursAttribute(): float
    {
        return round($this->overtime_minutes / 60, 2);
    }

    /**
     * Check if the employee was late.
     */
    public function wasLate(): bool
    {
        return $this->late_minutes > 0;
    }

    /**
     * Check if the employee left early.
     */
    public function leftEarly(): bool
    {
        return $this->early_departure_minutes > 0;
    }
}
