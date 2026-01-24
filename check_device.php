<?php
use App\Models\AttendanceDevice;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');
$device = AttendanceDevice::first();
echo $device->toJson(JSON_PRETTY_PRINT) . "\n";
