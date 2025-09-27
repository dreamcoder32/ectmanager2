<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\City;

echo "Checking cities for UTF-8 issues...\n";

$cities = City::all();
$problematic = [];

foreach($cities as $city) {
    if (!mb_check_encoding($city->name, 'UTF-8')) {
        $problematic[] = [
            'id' => $city->id,
            'name' => $city->name,
            'original' => bin2hex($city->name)
        ];
    }
}

echo "Problematic cities count: " . count($problematic) . "\n";

if (count($problematic) > 0) {
    echo "First 10 problematic cities:\n";
    foreach(array_slice($problematic, 0, 10) as $city) {
        echo "ID: {$city['id']}, Name: {$city['name']}, Hex: {$city['original']}\n";
    }
    
    echo "\nFixing UTF-8 issues...\n";
    
    foreach($problematic as $cityData) {
        $city = City::find($cityData['id']);
        if ($city) {
            // Try to convert from common encodings
            $fixed = false;
            $encodings = ['ISO-8859-1', 'Windows-1252', 'CP1252'];
            
            foreach ($encodings as $encoding) {
                $converted = mb_convert_encoding($city->name, 'UTF-8', $encoding);
                if (mb_check_encoding($converted, 'UTF-8')) {
                    $city->name = $converted;
                    $city->save();
                    echo "Fixed city ID {$city->id}: {$converted}\n";
                    $fixed = true;
                    break;
                }
            }
            
            if (!$fixed) {
                // Remove invalid characters
                $cleaned = mb_convert_encoding($city->name, 'UTF-8', 'UTF-8');
                $city->name = $cleaned;
                $city->save();
                echo "Cleaned city ID {$city->id}: {$cleaned}\n";
            }
        }
    }
    
    echo "UTF-8 fixes completed!\n";
} else {
    echo "No problematic cities found.\n";
}