<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Models\Company;
use App\Services\ZKTecoService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceDeviceController extends Controller
{
    protected ZKTecoService $zktecoService;

    public function __construct(ZKTecoService $zktecoService)
    {
        $this->zktecoService = $zktecoService;
    }

    /**
     * Display a listing of devices.
     */
    public function index()
    {
        $devices = AttendanceDevice::with(['attendanceRecords', 'company'])
            ->withCount('attendanceRecords')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Attendance/Devices/Index', [
            'devices' => $devices,
            'companies' => Company::all(),
        ]);
    }

    /**
     * Show the form for creating a new device.
     */
    public function create()
    {
        return Inertia::render('Attendance/Devices/Create', [
            'companies' => Company::all(),
        ]);
    }

    /**
     * Store a newly created device.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'device_model' => 'required|string|max:255',
            'serial_number' => 'required|string|unique:attendance_devices,serial_number',
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $device = AttendanceDevice::create($validated);

        return redirect()
            ->route('attendance.devices.index')
            ->with('success', 'Device added successfully');
    }

    /**
     * Display the specified device.
     */
    public function show(AttendanceDevice $device)
    {
        $device->load([
            'attendanceRecords' => function ($query) {
                $query->with('user')->latest()->limit(100);
            }
        ]);

        return Inertia::render('Attendance/Devices/Show', [
            'device' => $device,
        ]);
    }

    /**
     * Show the form for editing the specified device.
     */
    public function edit(AttendanceDevice $device)
    {
        return Inertia::render('Attendance/Devices/Edit', [
            'device' => $device,
            'companies' => Company::all(),
        ]);
    }

    /**
     * Update the specified device.
     */
    public function update(Request $request, AttendanceDevice $device)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'device_model' => 'required|string|max:255',
            'serial_number' => 'required|string|unique:attendance_devices,serial_number,' . $device->id,
            'ip_address' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $device->update($validated);

        return redirect()
            ->route('attendance.devices.index')
            ->with('success', 'Device updated successfully');
    }

    /**
     * Remove the specified device.
     */
    public function destroy(AttendanceDevice $device)
    {
        $device->delete();

        return redirect()
            ->route('attendance.devices.index')
            ->with('success', 'Device deleted successfully');
    }

    /**
     * Test device connection.
     */
    public function testConnection(AttendanceDevice $device)
    {
        $result = $this->zktecoService->testConnection($device);

        return response()->json($result);
    }
}
