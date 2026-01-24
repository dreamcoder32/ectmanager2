<?php

use App\Services\ZKTecoService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenantId = 'append4';
$tenant = Tenant::find($tenantId);

if (!$tenant) {
    echo "Tenant '$tenantId' not found.\n";
    exit(1);
}

tenancy()->initialize($tenant);

$service = app(ZKTecoService::class);
$device = \App\Models\AttendanceDevice::first();

if (!$device) {
    echo "No device found.\n";
    exit;
}

echo "Connecting to device: {$device->ip_address}:{$device->port}\n\n";

if ($service->connect($device)) {
    echo "✓ Connected!\n\n";

    // Fetch attendance records
    echo "=== Fetching Attendance Records ===\n";
    $records = $service->fetchAttendanceRecords($device);

    echo "Total records fetched: " . count($records) . "\n\n";

    if (!empty($records)) {
        // Group by device_user_id to see unique users
        $userIds = [];
        foreach ($records as $record) {
            $userId = $record['device_user_id'] ?? 'unknown';
            if (!isset($userIds[$userId])) {
                $userIds[$userId] = 0;
            }
            $userIds[$userId]++;
        }

        echo "=== User IDs Found on Device ===\n";
        foreach ($userIds as $userId => $count) {
            echo "User ID: {$userId} - {$count} records\n";
        }

        echo "\n=== Sample Records (first 5) ===\n";
        foreach (array_slice($records, 0, 5) as $record) {
            echo "User ID: {$record['device_user_id']} at {$record['punch_time']}\n";
        }
    } else {
        echo "No records found on device.\n";
    }

    $service->disconnect();
} else {
    echo "✗ Connection failed.\n";
}
