<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load cities data from Excel file
        $excelPath = '/Users/mac/Downloads/Achir Souad Code Wilayas (1).xlsx';
        
        if (!file_exists($excelPath)) {
            $this->command->error("Excel file not found at: {$excelPath}");
            return;
        }

        // Read the Excel file - second sheet contains cities data
        $data = Excel::toArray([], $excelPath);
        $citiesData = $data[1]; // Second sheet (index 1)
        
        // Skip header row (first row)
        $citiesRows = array_slice($citiesData, 1);
        
        $this->command->info("Found " . count($citiesRows) . " cities to import");
        
        // Get all states indexed by their numeric code (1-58)
        $states = State::all()->keyBy(function ($state) {
            // Extract numeric part from code (DZ-01 -> 1, DZ-16 -> 16)
            return (int) str_replace('DZ-', '', $state->code);
        });
        
        $importedCount = 0;
        $skippedCount = 0;
        
        foreach ($citiesRows as $row) {
            $cityName = trim($row[0] ?? '');
            $wilayaCode = (int) ($row[1] ?? 0);
            
            if (empty($cityName) || $wilayaCode <= 0) {
                $skippedCount++;
                continue;
            }
            
            // Find the corresponding state
            $state = $states->get($wilayaCode);
            
            if (!$state) {
                $this->command->warn("State not found for wilaya code: {$wilayaCode}, skipping city: {$cityName}");
                $skippedCount++;
                continue;
            }
            
            // Generate a unique code for the city
            $cityCode = strtoupper(substr($state->name, 0, 3)) . '-' . str_pad($importedCount + 1, 3, '0', STR_PAD_LEFT);
            
            try {
                City::create([
                    'name' => $cityName,
                    'code' => $cityCode,
                    'state_id' => $state->id,
                    'is_active' => true,
                ]);
                
                $importedCount++;
                
                if ($importedCount % 100 == 0) {
                    $this->command->info("Imported {$importedCount} cities...");
                }
                
            } catch (\Exception $e) {
                $this->command->warn("Failed to import city: {$cityName} - " . $e->getMessage());
                $skippedCount++;
            }
        }
        
        $this->command->info("City import completed!");
        $this->command->info("Imported: {$importedCount} cities");
        $this->command->info("Skipped: {$skippedCount} cities");
    }
}
