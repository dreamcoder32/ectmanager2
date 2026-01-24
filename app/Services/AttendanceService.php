<?php

namespace App\Services;

use App\Models\AttendanceDevice;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Models\AttendanceSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    protected ZKTecoServiceV2 $zktecoService;

    public function __construct(ZKTecoServiceV2 $zktecoService)
    {
        $this->zktecoService = $zktecoService;
    }

    /**
     * Sync attendance records from all active devices
     */
    public function syncAllDevices(?Carbon $fromDate = null): array
    {
        $devices = AttendanceDevice::active()->get();
        $results = [];

        foreach ($devices as $device) {
            $results[$device->id] = $this->syncDevice($device, $fromDate);
        }

        return $results;
    }

    /**
     * Sync attendance records from a specific device
     */
    public function syncDevice(AttendanceDevice $device, ?Carbon $fromDate = null): array
    {
        try {
            $records = $this->zktecoService->fetchAttendanceRecords($device, $fromDate);

            $imported = 0;
            $skipped = 0;

            foreach ($records as $recordData) {
                // Check if record already exists
                $exists = AttendanceRecord::where('user_id', $recordData['user_id'])
                    ->where('device_id', $recordData['device_id'])
                    ->where('punch_time', $recordData['punch_time'])
                    ->exists();

                if (!$exists) {
                    AttendanceRecord::create($recordData);
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            // Update device sync status
            $device->updateLastSync();

            // Generate summaries for affected dates
            $this->generateSummariesForRecords($records);

            return [
                'success' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'total' => count($records),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to sync device {$device->id}: {$e->getMessage()}");
            $device->updateLastSync($e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate attendance summaries for specific records
     */
    protected function generateSummariesForRecords(array $records): void
    {
        $userDates = [];

        foreach ($records as $record) {
            $date = Carbon::parse($record['punch_time'])->format('Y-m-d');
            $userId = $record['user_id'];
            $key = "{$userId}_{$date}";

            if (!in_array($key, $userDates)) {
                $userDates[] = $key;
                $this->generateDailySummary($userId, $date);
            }
        }
    }

    /**
     * Generate daily attendance summary for a user
     */
    public function generateDailySummary(int $userId, string $date): AttendanceSummary
    {
        $user = User::findOrFail($userId);
        $settings = AttendanceSetting::getSettings();

        // Get all records for the day
        $records = AttendanceRecord::forUser($userId)
            ->forDate($date)
            ->orderBy('punch_time')
            ->get();

        if ($records->isEmpty()) {
            return $this->createAbsentSummary($userId, $date);
        }

        // Determine punch types based on sequence
        $this->determinePunchTypes($records);

        // Calculate work hours
        $firstCheckIn = $records->where('punch_type', 'check_in')->first();
        $lastCheckOut = $records->where('punch_type', 'check_out')->last();

        $totalWorkMinutes = 0;
        $totalBreakMinutes = 0;

        // Calculate work time between check-in and check-out pairs
        $checkIns = $records->where('punch_type', 'check_in');
        $checkOuts = $records->where('punch_type', 'check_out');

        foreach ($checkIns as $index => $checkIn) {
            $checkOut = $checkOuts->skip($index)->first();
            if ($checkOut) {
                $minutes = $checkIn->punch_time->diffInMinutes($checkOut->punch_time);
                $totalWorkMinutes += $minutes;
            }
        }

        // Calculate breaks
        for ($i = 0; $i < $checkOuts->count() - 1; $i++) {
            $breakStart = $checkOuts->skip($i)->first();
            $breakEnd = $checkIns->skip($i + 1)->first();

            if ($breakStart && $breakEnd) {
                $totalBreakMinutes += $breakStart->punch_time->diffInMinutes($breakEnd->punch_time);
            }
        }

        // Get work times (custom or default)
        $workStartTime = $user->custom_work_start_time ?? $settings->work_start_time;
        $workEndTime = $user->custom_work_end_time ?? $settings->work_end_time;

        // Calculate late and early departure
        $expectedStart = Carbon::parse($date . ' ' . $workStartTime);
        $expectedEnd = Carbon::parse($date . ' ' . $workEndTime);

        $lateMinutes = 0;
        if ($firstCheckIn && $firstCheckIn->punch_time->gt($expectedStart->addMinutes($settings->grace_period_minutes))) {
            $lateMinutes = $expectedStart->diffInMinutes($firstCheckIn->punch_time);
        }

        $earlyDepartureMinutes = 0;
        if ($lastCheckOut && $lastCheckOut->punch_time->lt($expectedEnd)) {
            $earlyDepartureMinutes = $lastCheckOut->punch_time->diffInMinutes($expectedEnd);
        }

        // Calculate overtime
        $standardMinutes = $settings->getStandardWorkMinutes();
        $overtimeMinutes = max(0, $totalWorkMinutes - $standardMinutes);

        // Determine status
        $status = $this->determineStatus($totalWorkMinutes, $settings);

        return AttendanceSummary::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            [
                'first_check_in' => $firstCheckIn?->punch_time,
                'last_check_out' => $lastCheckOut?->punch_time,
                'total_work_minutes' => $totalWorkMinutes,
                'total_break_minutes' => $totalBreakMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'late_minutes' => $lateMinutes,
                'early_departure_minutes' => $earlyDepartureMinutes,
                'status' => $status,
            ]
        );
    }

    /**
     * Determine punch types based on sequence
     */
    protected function determinePunchTypes($records): void
    {
        $expectCheckIn = true;

        foreach ($records as $record) {
            if ($expectCheckIn) {
                $record->punch_type = 'check_in';
                $expectCheckIn = false;
            } else {
                $record->punch_type = 'check_out';
                $expectCheckIn = true;
            }
            $record->save();
        }
    }

    /**
     * Determine attendance status
     */
    protected function determineStatus(int $totalWorkMinutes, AttendanceSetting $settings): string
    {
        if ($totalWorkMinutes === 0) {
            return 'absent';
        }

        $halfDayMinutes = $settings->half_day_hours * 60;
        $standardMinutes = $settings->getStandardWorkMinutes();

        if ($totalWorkMinutes >= $standardMinutes) {
            return 'present';
        } elseif ($totalWorkMinutes >= $halfDayMinutes) {
            return 'half_day';
        }

        return 'absent';
    }

    /**
     * Create absent summary
     */
    protected function createAbsentSummary(int $userId, string $date): AttendanceSummary
    {
        return AttendanceSummary::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            [
                'status' => 'absent',
                'total_work_minutes' => 0,
            ]
        );
    }

    /**
     * Get monthly attendance report for a user
     */
    public function getMonthlyReport(int $userId, int $year, int $month): array
    {
        $summaries = AttendanceSummary::forUser($userId)
            ->forMonth($year, $month)
            ->orderBy('date')
            ->get();

        $totalWorkHours = 0;
        $totalOvertimeHours = 0;
        $presentDays = 0;
        $absentDays = 0;
        $halfDays = 0;
        $lateDays = 0;

        foreach ($summaries as $summary) {
            $totalWorkHours += $summary->total_work_hours;
            $totalOvertimeHours += $summary->overtime_hours;

            if ($summary->status === 'present') {
                $presentDays++;
            } elseif ($summary->status === 'absent') {
                $absentDays++;
            } elseif ($summary->status === 'half_day') {
                $halfDays++;
            }

            if ($summary->wasLate()) {
                $lateDays++;
            }
        }

        return [
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
            'total_work_hours' => round($totalWorkHours, 2),
            'total_overtime_hours' => round($totalOvertimeHours, 2),
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'half_days' => $halfDays,
            'late_days' => $lateDays,
            'summaries' => $summaries,
        ];
    }

    /**
     * Get attendance statistics for all users in a month
     */
    public function getMonthlyStatistics(int $year, int $month): array
    {
        $users = User::where('attendance_enabled', true)->get();
        $statistics = [];

        foreach ($users as $user) {
            $statistics[] = array_merge(
                ['user' => $user],
                $this->getMonthlyReport($user->id, $year, $month)
            );
        }

        return $statistics;
    }

    /**
     * Sync users to all devices
     */
    public function syncUsersToAllDevices(): array
    {
        $devices = AttendanceDevice::active()->get();
        $users = User::where('attendance_enabled', true)
            ->whereNotNull('device_user_id')
            ->get()
            ->toArray();

        $results = [];

        foreach ($devices as $device) {
            try {
                $success = $this->zktecoService->syncUsersToDevice($device, $users);
                $results[$device->id] = [
                    'success' => $success,
                    'users_synced' => count($users),
                ];
            } catch (\Exception $e) {
                $results[$device->id] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
