<?php
use App\Models\AttendanceDevice;
use App\Services\ZKTecoService;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tenantId = 'append4';
tenancy()->initialize($tenantId);

$device = AttendanceDevice::where('ip_address', '192.168.100.99')->first();

if (!$device) {
    echo "Device not found!\n";
    exit(1);
}

echo "Testing connection to {$device->name} ({$device->ip_address})...\n";

$zk = app(ZKTecoService::class);
try {
    if ($zk->connect($device)) {
        echo "Connected!\n";

        $info = $zk->getDeviceInfo();
        echo "Device Info:\n";
        print_r($info);

        $records = $zk->fetchAttendanceRecords($device);
        echo "Successfully fetched " . count($records) . " records.\n";
    } else {
        echo "Failed to connect.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
