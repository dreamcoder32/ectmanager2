<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSummary;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display attendance dashboard.
     */
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $statistics = $this->attendanceService->getMonthlyStatistics($year, $month);

        return Inertia::render('Attendance/Index', [
            'statistics' => $statistics,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Display user attendance details.
     */
    public function show(Request $request, User $user)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $report = $this->attendanceService->getMonthlyReport($user->id, $year, $month);

        return Inertia::render('Attendance/Show', [
            'user' => $user,
            'report' => $report,
            'year' => $year,
            'month' => $month,
        ]);
    }

    /**
     * Sync attendance from all devices.
     */
    public function sync(Request $request)
    {
        $fromDate = $request->has('from_date')
            ? Carbon::parse($request->get('from_date'))
            : Carbon::now()->subDays(7);

        $results = $this->attendanceService->syncAllDevices($fromDate);

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => 'Attendance sync completed',
        ]);
    }

    /**
     * Get daily attendance records.
     */
    public function daily(Request $request)
    {
        $date = $request->get('date', now()->format('Y-m-d'));

        $records = AttendanceRecord::with(['user', 'device'])
            ->forDate($date)
            ->orderBy('punch_time')
            ->get()
            ->groupBy('user_id');

        $summaries = AttendanceSummary::with('user')
            ->where('date', $date)
            ->get();

        return Inertia::render('Attendance/Daily', [
            'date' => $date,
            'records' => $records,
            'summaries' => $summaries,
        ]);
    }

    /**
     * Export monthly attendance report.
     */
    public function export(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $userId = $request->get('user_id');

        if ($userId) {
            $report = $this->attendanceService->getMonthlyReport($userId, $year, $month);
            $data = [$report];
        } else {
            $data = $this->attendanceService->getMonthlyStatistics($year, $month);
        }

        // Generate Excel export
        return $this->generateExcelExport($data, $year, $month);
    }

    /**
     * Generate Excel export.
     */
    protected function generateExcelExport(array $data, int $year, int $month): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filename = "attendance_report_{$year}_{$month}.xlsx";

        // Use Maatwebsite Excel to generate export
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\AttendanceExport($data),
            $filename
        );
    }

    /**
     * Manual punch (for admin corrections).
     */
    public function manualPunch(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'punch_time' => 'required|date',
            'punch_type' => 'required|in:check_in,check_out,break_start,break_end',
            'notes' => 'nullable|string',
        ]);

        $record = AttendanceRecord::create([
            'user_id' => $validated['user_id'],
            'device_id' => null,
            'device_user_id' => User::find($validated['user_id'])->device_user_id,
            'punch_time' => $validated['punch_time'],
            'punch_type' => $validated['punch_type'],
            'notes' => $validated['notes'] ?? 'Manual entry by admin',
            'is_synced' => false,
        ]);

        // Regenerate summary for the date
        $date = Carbon::parse($validated['punch_time'])->format('Y-m-d');
        $this->attendanceService->generateDailySummary($validated['user_id'], $date);

        return response()->json([
            'success' => true,
            'record' => $record,
            'message' => 'Manual punch recorded successfully',
        ]);
    }

    /**
     * Update attendance summary notes.
     */
    public function updateNotes(Request $request, AttendanceSummary $summary)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $summary->update($validated);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
