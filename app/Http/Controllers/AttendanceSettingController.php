<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceSettingController extends Controller
{
    /**
     * Display attendance settings.
     */
    public function index()
    {
        $settings = AttendanceSetting::getSettings();

        return Inertia::render('Attendance/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update attendance settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'work_start_time' => 'required|date_format:H:i:s',
            'work_end_time' => 'required|date_format:H:i:s',
            'standard_work_hours' => 'required|integer|min:1|max:24',
            'grace_period_minutes' => 'required|integer|min:0|max:60',
            'half_day_hours' => 'required|integer|min:1|max:12',
            'auto_checkout_enabled' => 'boolean',
            'auto_checkout_time' => 'nullable|date_format:H:i:s',
            'sync_interval_minutes' => 'required|integer|min:5|max:1440',
            'working_days' => 'required|array',
        ]);

        $settings = AttendanceSetting::getSettings();
        $settings->update($validated);

        return redirect()
            ->route('attendance.settings.index')
            ->with('success', 'Attendance settings updated successfully');
    }
}
