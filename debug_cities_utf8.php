<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\City;

echo "Debugging cities UTF-8 issues in batches...\n";

$cities = City::active()->get();
echo "Total active cities: " . $cities->count() . "\n";

$batchSize = 50;
$batches = $cities->chunk($batchSize);

foreach($batches as $batchIndex => $batch) {
    echo "Testing batch " . ($batchIndex + 1) . " (cities " . (($batchIndex * $batchSize) + 1) . " to " . (($batchIndex + 1) * $batchSize) . ")...\n";
    
    $json = json_encode($batch);
    if ($json === false) {
        echo "PROBLEMATIC BATCH FOUND! Batch " . ($batchIndex + 1) . "\n";
        echo "Error: " . json_last_error_msg() . "\n";
        
        // Test each city in this batch individually
        foreach($batch as $city) {
            $cityJson = json_encode($city);
            if ($cityJson === false) {
                echo "PROBLEMATIC CITY: ID {$city->id}, Name: '{$city->name}'\n";
                echo "Hex dump: " . bin2hex($city->name) . "\n";
                
                // Try to fix this city
                $originalName = $city->name;
                
                // Try different encoding conversions
                $encodings = ['ISO-8859-1', 'Windows-1252', 'CP1252', 'UTF-8'];
                $fixed = false;
                
                foreach($encodings as $encoding) {
                    $converted = mb_convert_encoding($originalName, 'UTF-8', $encoding);
                    if (mb_check_encoding($converted, 'UTF-8') && json_encode($converted) !== false) {
                        echo "Fixed with encoding {$encoding}: '{$converted}'\n";
                        $city->name = $converted;
                        $city->save();
                        $fixed = true;
                        break;
                    }
                }
                
                if (!$fixed) {
                    // Remove problematic characters
                    $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $originalName);
                    $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');
                    echo "Cleaned version: '{$cleaned}'\n";
                    $city->name = $cleaned;
                    $city->save();
                }
            }
        }
        break; // Stop after finding the first problematic batch
    } else {
        echo "Batch " . ($batchIndex + 1) . " is OK\n";
    }
}

echo "Debug completed!\n";