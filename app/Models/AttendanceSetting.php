<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_start_time',
        'work_end_time',
        'standard_work_hours',
        'grace_period_minutes',
        'half_day_hours',
        'auto_checkout_enabled',
        'auto_checkout_time',
        'sync_interval_minutes',
        'working_days',
    ];

    protected $casts = [
        'auto_checkout_enabled' => 'boolean',
        'working_days' => 'array',
    ];

    /**
     * Get the singleton instance of settings.
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'work_start_time' => '09:00:00',
            'work_end_time' => '17:00:00',
            'standard_work_hours' => 8,
            'grace_period_minutes' => 15,
            'half_day_hours' => 4,
            'auto_checkout_enabled' => false,
            'sync_interval_minutes' => 30,
            'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
        ]);
    }

    /**
     * Check if a given day is a working day.
     */
    public function isWorkingDay(string $day): bool
    {
        return in_array(strtolower($day), $this->working_days);
    }

    /**
     * Get standard work minutes.
     */
    public function getStandardWorkMinutes(): int
    {
        return $this->standard_work_hours * 60;
    }
}
