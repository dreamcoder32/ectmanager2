<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize database connection for SQLite
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => '/Users/mac/ect2/delivery-management-system/database/database.sqlite',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Starting cities UTF-8 fix...\n";

try {
    // Get all cities with raw SQL to avoid Laravel model issues
    $cities = Capsule::select('SELECT id, name FROM cities');
    
    $problematic = [];
    $fixed = 0;
    
    foreach ($cities as $city) {
        $original = $city->name;
        
        // Check if the name is valid UTF-8
        if (!mb_check_encoding($original, 'UTF-8')) {
            $problematic[] = [
                'id' => $city->id,
                'name' => $original,
                'hex' => bin2hex($original)
            ];
            
            // Try to fix it
            $fixed_name = mb_convert_encoding($original, 'UTF-8', 'ISO-8859-1');
            
            // If still not valid, try other encodings
            if (!mb_check_encoding($fixed_name, 'UTF-8')) {
                $fixed_name = mb_convert_encoding($original, 'UTF-8', 'Windows-1252');
            }
            
            // If still not valid, remove invalid characters
            if (!mb_check_encoding($fixed_name, 'UTF-8')) {
                $fixed_name = mb_convert_encoding($original, 'UTF-8', 'UTF-8');
            }
            
            // Update the city
            Capsule::update('UPDATE cities SET name = ? WHERE id = ?', [$fixed_name, $city->id]);
            $fixed++;
            
            echo "Fixed city ID {$city->id}: '{$original}' -> '{$fixed_name}'\n";
        }
    }
    
    echo "\nFound " . count($problematic) . " problematic cities\n";
    echo "Fixed {$fixed} cities\n";
    
    // Test JSON encoding
    $all_cities = Capsule::select('SELECT name FROM cities');
    $names = array_column($all_cities, 'name');
    
    $json_result = json_encode($names);
    if ($json_result === false) {
        echo "JSON encoding still FAILED: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON encoding SUCCESS!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}