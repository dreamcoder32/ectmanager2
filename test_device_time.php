<?php

use App\Services\ZKTecoService;
use App\Models\Tenant;

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

    // Get device info including time
    echo "=== Device Information ===\n";
    $info = $service->getDeviceInfo();

    foreach ($info as $key => $value) {
        if ($key === 'Time' && $value !== 'null') {
            // Try to parse the time
            $timeHex = $value;
            echo "{$key}: {$value} (hex)\n";

            // The time is usually encoded as 4 bytes (Unix timestamp or custom format)
            if (strlen($timeHex) >= 8) {
                $timeBytes = hex2bin($timeHex);
                if ($timeBytes) {
                    $unpacked = unpack('V', substr($timeBytes, 0, 4));
                    if ($unpacked) {
                        $timestamp = $unpacked[1];
                        $date = date('Y-m-d H:i:s', $timestamp);
                        echo "  Decoded: {$date}\n";
                    }
                }
            }
        } else {
            echo "{$key}: {$value}\n";
        }
    }

    $service->disconnect();
} else {
    echo "✗ Connection failed.\n";
}
