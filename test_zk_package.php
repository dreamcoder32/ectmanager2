<?php

use App\Models\Tenant;
use CodingLibs\ZktecoPhp\Libs\ZKTeco;

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
echo "Initialized tenant: {$tenant->id}\n\n";

$device = \App\Models\AttendanceDevice::first();
if (!$device) {
    echo "No device found.\n";
    exit;
}

echo "=== Testing with coding-libs/zkteco-php Package ===\n";
echo "Device: {$device->ip_address}:{$device->port}\n\n";

try {
    // Create ZKTeco instance
    $zk = new ZKTeco($device->ip_address, $device->port);

    // Connect
    echo "Connecting...\n";
    if ($zk->connect()) {
        echo "✓ Connected successfully!\n\n";

        // Disable device
        $zk->disableDevice();

        // Get attendance logs
        echo "=== Fetching Attendance Logs ===\n";
        $attendance = $zk->getAttendances(); // Correct method name

        echo "Total records fetched: " . count($attendance) . "\n\n";

        if (!empty($attendance)) {
            // Group by user ID
            $byUser = [];
            foreach ($attendance as $record) {
                $uid = $record['uid'] ?? $record['id'] ?? 'unknown';
                if (!isset($byUser[$uid])) {
                    $byUser[$uid] = [];
                }
                $byUser[$uid][] = $record;
            }

            echo "=== Records by User ID ===\n";
            foreach ($byUser as $uid => $records) {
                echo "\nUser ID {$uid}: " . count($records) . " records\n";
                foreach (array_slice($records, 0, 5) as $record) {
                    print_r($record);
                }
                if (count($records) > 5) {
                    echo "  ... and " . (count($records) - 5) . " more\n";
                }
            }
        } else {
            echo "No attendance records found.\n";
        }

        // Re-enable device
        $zk->enableDevice();

        // Disconnect
        $zk->disconnect();
        echo "\n✓ Disconnected\n";

    } else {
        echo "✗ Connection failed!\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
