<?php

namespace App\Imports;

use App\Models\Parcel;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class ParcelsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnError
{
    use SkipsErrors;

    private $importedCount = 0;
    private $states = [];
    private $cities = [];

    public function __construct()
    {
        // Initialize empty arrays - will be populated when needed
        $this->states = [];
        $this->cities = [];
    }

    /**
     * Specify which row contains the headings (row 2 in this case)
     */
    public function headingRow(): int
    {
        return 2;
    }

    /**
     * Map the Excel row to a Parcel model
     */
    public function model(array $row): ?Parcel
    {
        try {
            // Skip empty rows or rows without required data
            if (empty($row['id']) || empty($row['client'])) {
                return null;
            }

            // Extract data from row using header mapping and clean UTF-8
            $trackingNumber = $this->cleanUtf8((string) $row['id']);
            $senderName = $this->cleanUtf8((string) ($row['expediteur'] ?? ''));
            $recipientName = $this->cleanUtf8((string) $row['client']);
            $primaryPhone = $this->cleanUtf8((string) ($row['tel_1'] ?? ''));
            $secondaryPhone = $this->cleanUtf8((string) ($row['tel_2'] ?? ''));
            $address = $this->cleanUtf8((string) ($row['adresse'] ?? ''));
            $cityName = $this->cleanUtf8((string) ($row['commune'] ?? ''));
            $stateName = $this->cleanUtf8((string) ($row['wilaya'] ?? ''));
            $codAmount = $row['total'] ?? 0;
            $notes = $this->cleanUtf8((string) ($row['remarque'] ?? ''));
            $reference = $this->cleanUtf8((string) ($row['ref'] ?? ''));
            $products = $this->cleanUtf8((string) ($row['produits'] ?? ''));

            // Find state from database (don't create new ones)
            $stateId = $this->findStateInDatabase($stateName);
            
            // Find city from database (don't create new ones)
            $cityId = $this->findCityInDatabase($cityName, $stateId);

            // Create parcel
            $parcel = new Parcel([
                'tracking_number' => $trackingNumber,
                'company_id' => 1, // Default company ID - you may want to make this configurable
                'sender_name' => $senderName,
                'sender_phone' => '', // Not provided in Excel format
                'sender_address' => '', // Not provided in Excel format
                'receiver_name' => $recipientName,
                'receiver_phone' => $primaryPhone,
                'receiver_address' => $address,
                'recipient_name' => $recipientName,
                'recipient_phone' => $primaryPhone,
                'recipient_address' => $address,
                'wilaya_code' => '', // Legacy field, can be empty
                'commune' => $cityName, // Legacy field
                'state_id' => $stateId,
                'city_id' => $cityId,
                'weight' => 0, // Default weight
                'declared_value' => 0, // Default declared value
                'cod_amount' => is_numeric($codAmount) ? floatval($codAmount) : 0,
                'delivery_fee' => 0, // Default delivery fee
                'description' => $products,
                'notes' => $notes,
                'reference' => $reference,
                'secondary_phone' => $secondaryPhone,
                'status' => 'pending', // Default status
            ]);

            $this->importedCount++;
            
            return $parcel;

        } catch (\Exception $e) {
            Log::error('Error importing parcel row: ' . $e->getMessage(), ['row' => $row]);
            return null;
        }
    }

    /**
     * Find state in database by name
     */
    private function findStateInDatabase($stateName)
    {
        if (empty($stateName)) {
            return null;
        }

        // Lazy load states cache if empty
        if (empty($this->states)) {
            $this->states = State::pluck('id', 'name')->toArray();
        }

        // Check if state exists in cache (exact match)
        if (isset($this->states[$stateName])) {
            return $this->states[$stateName];
        }

        // Try case-insensitive search
        foreach ($this->states as $name => $id) {
            if (strcasecmp($name, $stateName) === 0) {
                return $id;
            }
        }

        // State not found in database
        Log::warning("State not found in database: {$stateName}");
        return null;
    }

    /**
     * Find city in database by name and state
     */
    private function findCityInDatabase($cityName, $stateId)
    {
        if (empty($cityName) || empty($stateId)) {
            return null;
        }

        // Direct database query for exact match first
        $city = City::where('name', $cityName)
                   ->where('state_id', $stateId)
                   ->first();
        
        if ($city) {
            return $city->id;
        }

        // Try case-insensitive search
        $city = City::whereRaw('LOWER(name) = ?', [strtolower($cityName)])
                   ->where('state_id', $stateId)
                   ->first();
        
        if ($city) {
            return $city->id;
        }

        // City not found in database
        Log::warning("City not found in database: {$cityName} (State ID: {$stateId})");
        return null;
    }

    /**
     * Validation rules for each row
     */
    public function rules(): array
    {
        return [
            // Use header names for validation - allow mixed data types that will be converted in model
            'id' => 'required|min:1|max:255', // ID (Tracking Number) - can be string or numeric
            'client' => 'required|min:1|max:255', // Client (Recipient Name) - can be string or numeric
            'tel_1' => 'nullable|max:20', // Tel 1 (Primary Phone) - can be string or numeric
            'tel_2' => 'nullable|max:20', // Tel 2 (Secondary Phone) - can be string or numeric
            'adresse' => 'nullable|max:500', // Adresse (Address) - can be string or numeric
            'commune' => 'nullable|max:255', // Commune (City) - can be string or numeric
            'wilaya' => 'nullable|max:255', // Wilaya (State) - can be string or numeric
            'total' => 'nullable|numeric|min:0', // Total (COD Amount) - numeric only
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages()
    {
        return [
            'id.required' => 'Tracking number (ID) is required',
            'client.required' => 'Client name is required',
            'total.numeric' => 'Total amount must be a valid number',
        ];
    }

    /**
     * Batch size for bulk inserts
     */
    public function batchSize(): int
    {
        return 500; // Increased from 100 for better performance with large files
    }

    /**
     * Chunk size for reading large files
     */
    public function chunkSize(): int
    {
        return 500; // Increased from 100 for better performance with large files
    }

    /**
     * Get the count of imported records
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Clean and validate UTF-8 encoding for text fields
     */
    private function cleanUtf8(string $text): string
    {
        // Remove any null bytes
        $text = str_replace("\0", '', $text);
        
        // Check if the string is valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to convert from common encodings to UTF-8
            $encodings = ['ISO-8859-1', 'Windows-1252', 'CP1252'];
            foreach ($encodings as $encoding) {
                $converted = mb_convert_encoding($text, 'UTF-8', $encoding);
                if (mb_check_encoding($converted, 'UTF-8')) {
                    return $converted;
                }
            }
            
            // If conversion fails, remove invalid characters
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        
        return trim($text);
    }
}