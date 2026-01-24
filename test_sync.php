<?php

use App\Services\ZKTecoService;
use App\Services\AttendanceService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Explicitly set the tenant context
$tenantId = 'append4';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    echo "Tenant '$tenantId' not found.\n";
    exit(1);
}

tenancy()->initialize($tenant);
echo "Initialized tenant: " . $tenant->id . "\n\n";

// Check users with device_user_id
echo "=== Users with Device User ID ===\n";
$users = User::whereNotNull('device_user_id')->get(['id', 'first_name', 'last_name', 'email', 'device_user_id']);
if ($users->isEmpty()) {
    echo "❌ No users found with device_user_id set!\n";
    echo "Please go to Users page and set the Device User ID for at least one user.\n\n";
} else {
    foreach ($users as $user) {
        echo "✓ {$user->first_name} {$user->last_name} ({$user->email}) - Device ID: {$user->device_user_id}\n";
    }
}
echo "\n";

// Get device
$device = \App\Models\AttendanceDevice::first();
if (!$device) {
    echo "❌ No attendance device found!\n";
    exit(1);
}

echo "=== Device Info ===\n";
echo "Device: {$device->name}\n";
echo "IP: {$device->ip_address}:{$device->port}\n";
echo "Status: " . ($device->is_active ? 'Active' : 'Inactive') . "\n\n";

// Try to sync
echo "=== Starting Sync ===\n";
$attendanceService = app(AttendanceService::class);

try {
    $result = $attendanceService->syncDevice($device);

    echo "\n=== Sync Result ===\n";
    echo "Status: " . ($result['success'] ? '✓ Success' : '✗ Failed') . "\n";
    echo "Message: {$result['message']}\n";

    if (isset($result['records_synced'])) {
        echo "Records Synced: {$result['records_synced']}\n";
    }

    if (isset($result['error'])) {
        echo "Error: {$result['error']}\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Sync Failed with Exception:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Checking Attendance Records ===\n";
$records = \App\Models\AttendanceRecord::with('user')->latest()->take(10)->get();
if ($records->isEmpty()) {
    echo "No attendance records found in database.\n";
} else {
    echo "Latest {$records->count()} attendance records:\n";
    foreach ($records as $record) {
        $userName = $record->user ? "{$record->user->first_name} {$record->user->last_name}" : "Unknown User";
        echo "- {$userName} ({$record->device_user_id}) at {$record->punch_time}\n";
    }
}
