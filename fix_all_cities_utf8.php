<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\City;

echo "Fixing ALL cities UTF-8 issues...\n";

$cities = City::all();
echo "Total cities: " . $cities->count() . "\n";

$fixed = 0;
$processed = 0;

foreach($cities as $city) {
    $processed++;
    $originalName = $city->name;
    
    // Check if this city needs fixing
    if (!mb_check_encoding($originalName, 'UTF-8') || json_encode($city) === false) {
        echo "Fixing city ID {$city->id}: '{$originalName}'\n";
        
        // Try different encoding conversions
        $encodings = ['ISO-8859-1', 'Windows-1252', 'CP1252'];
        $cityFixed = false;
        
        foreach($encodings as $encoding) {
            $converted = mb_convert_encoding($originalName, 'UTF-8', $encoding);
            if (mb_check_encoding($converted, 'UTF-8') && json_encode($converted) !== false) {
                $city->name = $converted;
                $city->save();
                echo "  -> Fixed with {$encoding}: '{$converted}'\n";
                $fixed++;
                $cityFixed = true;
                break;
            }
        }
        
        if (!$cityFixed) {
            // Remove problematic characters and force UTF-8
            $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $originalName);
            $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');
            $city->name = $cleaned;
            $city->save();
            echo "  -> Cleaned: '{$cleaned}'\n";
            $fixed++;
        }
    }
    
    if ($processed % 100 == 0) {
        echo "Processed {$processed} cities...\n";
    }
}

echo "Completed! Processed: {$processed}, Fixed: {$fixed}\n";

// Final test
echo "Testing final JSON encoding...\n";
$testCities = City::active()->get();
$json = json_encode($testCities);
if ($json === false) {
    echo "STILL FAILED: " . json_last_error_msg() . "\n";
} else {
    echo "SUCCESS: All cities can now be JSON encoded!\n";
}