<?php

use App\Services\ZKTecoService;
use App\Models\Tenant;
use Carbon\Carbon;

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

    // Try fetching with a specific from date (January 1, 2026)
    echo "=== Fetching Records from 2026-01-01 ===\n";
    $fromDate = Carbon::create(2026, 1, 1);
    $records = $service->fetchAttendanceRecords($device, $fromDate);

    echo "Total records fetched: " . count($records) . "\n\n";

    if (!empty($records)) {
        // Group by device_user_id
        $userIds = [];
        foreach ($records as $record) {
            $userId = $record['device_user_id'] ?? 'unknown';
            if (!isset($userIds[$userId])) {
                $userIds[$userId] = [];
            }
            $userIds[$userId][] = $record['punch_time']->format('Y-m-d H:i:s');
        }

        echo "=== Records by User ID ===\n";
        foreach ($userIds as $userId => $times) {
            echo "User ID {$userId}: " . count($times) . " records\n";
            foreach (array_slice($times, 0, 3) as $time) {
                echo "  - {$time}\n";
            }
            if (count($times) > 3) {
                echo "  ... and " . (count($times) - 3) . " more\n";
            }
        }
    } else {
        echo "No records found.\n";
    }

    $service->disconnect();
} else {
    echo "✗ Connection failed.\n";
}
