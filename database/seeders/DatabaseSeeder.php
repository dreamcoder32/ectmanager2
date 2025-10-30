<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\Driver;
use App\Models\StateMapping;
use App\Models\Margin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed states and cities first
        $this->call([
            StateSeeder::class,
            CitySeeder::class,
        ]);

        // Create admin user
        User::create([
            'uid' => 'admin-001',
            'first_name' => 'System Administrator',
            'email' => 'admin@delivery.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'assigned_states' => null,
        ]);

        // Create agent user
        User::create([
            'uid' => 'agent-001',
            'first_name' => 'Agent User',
            'email' => 'agent@delivery.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'is_active' => true,
            'assigned_states' => ['01', '16'], // Adrar and Alger
        ]);

        // Create sample companies
        $company1 = Company::create([
            'name' => 'Express Delivery Co.',
            'code' => 'EDC',
            'is_active' => true,
        ]);

        $company2 = Company::create([
            'name' => 'Fast Logistics',
            'code' => 'FL',
            'is_active' => true,
        ]);

        // Create sample drivers
        $driver1 = Driver::create([
            'name' => 'Ahmed Benali',
            'phone' => '0555123456',
            'license_number' => 'DL123456789',
            'vehicle_info' => 'Renault Kangoo - 123 ABC 16',
            'is_active' => true,
        ]);

        $driver2 = Driver::create([
            'name' => 'Mohamed Khelifi',
            'phone' => '0666789012',
            'license_number' => 'DL987654321',
            'vehicle_info' => 'Peugeot Partner - 456 DEF 01',
            'is_active' => true,
        ]);

        // Create state mappings for Algerian wilayas
        $stateMappings = [
            ['wilaya_code' => '01', 'wilaya_name' => 'Adrar', 'commune' => 'Adrar', 'driver_id' => $driver2->id, 'driver_cost' => 500.00],
            ['wilaya_code' => '16', 'wilaya_name' => 'Alger', 'commune' => 'Alger Centre', 'driver_id' => $driver1->id, 'driver_cost' => 300.00],
            ['wilaya_code' => '16', 'wilaya_name' => 'Alger', 'commune' => 'Bab Ezzouar', 'driver_id' => $driver1->id, 'driver_cost' => 350.00],
            ['wilaya_code' => '16', 'wilaya_name' => 'Alger', 'commune' => 'Birtouta', 'driver_id' => $driver1->id, 'driver_cost' => 400.00],
        ];

        foreach ($stateMappings as $mapping) {
            StateMapping::create($mapping);
        }

        // Create margins for companies
        $margins = [
            ['company_id' => $company1->id, 'commune' => 'Alger Centre', 'margin_amount' => 50.00],
            ['company_id' => $company1->id, 'commune' => 'Bab Ezzouar', 'margin_amount' => 75.00],
            ['company_id' => $company2->id, 'commune' => 'Alger Centre', 'margin_amount' => 60.00],
            ['company_id' => $company2->id, 'commune' => 'Adrar', 'margin_amount' => 100.00],
        ];

        foreach ($margins as $margin) {
            Margin::create($margin);
        }
    }
}
