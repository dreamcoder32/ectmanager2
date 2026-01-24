<?php
use App\Models\User;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
tenancy()->initialize('append4');
echo "User count: " . User::count() . "\n";
foreach (User::all() as $user) {
    echo "User [{$user->id}] Name: {$user->name}, DeviceUserID: {$user->device_user_id}\n";
}
