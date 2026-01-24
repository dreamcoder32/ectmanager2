<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

// Users from ZKTeco device
$deviceUsers = [
    ['id' => 1, 'name' => 'Admin', 'email' => 'admin.device@company.com'],
    ['id' => 2, 'name' => 'MERIEM', 'email' => 'meriem@company.com'],
    ['id' => 3, 'name' => 'Feriel', 'email' => 'feriel@company.com'],
    ['id' => 4, 'name' => 'Zahra', 'email' => 'zahra@company.com'],
    ['id' => 5, 'name' => 'Asia', 'email' => 'asia@company.com'],
    ['id' => 6, 'name' => 'Z', 'email' => 'z@company.com'],
    ['id' => 7, 'name' => 'SILYA', 'email' => 'silya@company.com'],
    ['id' => 8, 'name' => 'Lydia', 'email' => 'lydia@company.com'],
    ['id' => 9, 'name' => 'Riham', 'email' => 'riham@company.com'],
    ['id' => 10, 'name' => 'Chaima', 'email' => 'chaima@company.com'],
    ['id' => 11, 'name' => 'Yacine', 'email' => 'yacine@company.com'],
    ['id' => 12, 'name' => 'Riadh', 'email' => 'riadh@company.com'],
    ['id' => 13, 'name' => 'Ghazels', 'email' => 'ghazels@company.com'],
];

echo "=== Creating Users ===\n";
$created = 0;
$updated = 0;

foreach ($deviceUsers as $deviceUser) {
    // Check if user already exists by email
    $user = User::where('email', $deviceUser['email'])->first();

    if ($user) {
        // Update existing user
        $user->device_user_id = (string) $deviceUser['id'];
        $user->save();
        echo "✓ Updated: {$deviceUser['name']} (Device ID: {$deviceUser['id']})\n";
        $updated++;
    } else {
        // Create new user
        $user = User::create([
            'uid' => 'USR' . str_pad(User::count() + 1, 6, '0', STR_PAD_LEFT),
            'first_name' => $deviceUser['name'],
            'last_name' => '',
            'email' => $deviceUser['email'],
            'password' => Hash::make('password123'),
            'role' => 'agent',
            'is_active' => true,
            'device_user_id' => (string) $deviceUser['id'],
        ]);
        echo "✓ Created: {$deviceUser['name']} (Device ID: {$deviceUser['id']})\n";
        $created++;
    }
}

echo "\n=== Summary ===\n";
echo "Created: {$created} users\n";
echo "Updated: {$updated} users\n";
echo "Total: " . ($created + $updated) . " users\n\n";

// Show all users with device_user_id
echo "=== Users with Device User ID ===\n";
$users = User::whereNotNull('device_user_id')->orderBy('device_user_id')->get(['first_name', 'email', 'device_user_id']);
foreach ($users as $user) {
    echo "ID {$user->device_user_id}: {$user->first_name} ({$user->email})\n";
}

echo "\n✅ All users created and mapped!\n";
echo "You can now sync attendance from the device.\n";
