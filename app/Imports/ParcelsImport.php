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
    private $skippedCount = 0;
    private $states = [];
    private $cities = [];
    private $detailedErrors = [];
    private $companyId = null;

    public function __construct($companyId = null)
    {
        // Initialize empty arrays - will be populated when needed
        $this->states = [];
        $this->cities = [];
        $this->detailedErrors = [];
        $this->companyId = $companyId;
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
        $rowNumber = $this->importedCount + 2; // +2 because we start from row 2 (header) and count from 1
        
        try {
            // Skip empty rows or rows without required data
            if (empty($row['id']) || empty($row['client'])) {
                $this->detailedErrors[] = [
                    'row' => $rowNumber,
                    'error' => 'Missing required data: ID or Client name is empty',
                    'data' => $row
                ];
                Log::warning("Row {$rowNumber}: Missing required data", [
                    'id' => $row['id'] ?? 'empty',
                    'client' => $row['client'] ?? 'empty'
                ]);
                return null;
            }

            // Extract data from row using header mapping and clean UTF-8
            $trackingNumber = $this->cleanUtf8((string) $row['id']);
            
            // Check if parcel with this tracking number already exists
            $existingParcel = Parcel::where('tracking_number', $trackingNumber)->first();
            if ($existingParcel) {
                $this->detailedErrors[] = [
                    'row' => $rowNumber,
                    'error' => "Parcel with tracking number '{$trackingNumber}' already exists - skipped",
                    'data' => ['tracking_number' => $trackingNumber, 'existing_id' => $existingParcel->id]
                ];
                Log::info("Row {$rowNumber}: Duplicate parcel skipped", [
                    'tracking_number' => $trackingNumber,
                    'existing_parcel_id' => $existingParcel->id
                ]);
                $this->skippedCount++; // Increment skipped count
                return null; // Skip this row
            }
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
            // Debug: Check what we're getting from Excel
            $rawDateValue = $row['date_creation'] ?? 'NOT_FOUND';
            Log::info("Excel date debugging", [
                'row_number' => $rowNumber,
                'raw_date_value' => $rawDateValue,
                'raw_date_type' => gettype($rawDateValue),
                'available_columns' => array_keys($row)
            ]);
            
            $parcelCreationDate = $this->parseDate($rawDateValue);

            // Find state from database (don't create new ones)
            $stateId = $this->findStateInDatabase($stateName);
            
            // Find city from database (don't create new ones)
            $cityId = $this->findCityInDatabase($cityName, $stateId);

            // Log missing state/city information
            if (!$stateId && !empty($stateName)) {
                $this->detailedErrors[] = [
                    'row' => $rowNumber,
                    'error' => "State not found in database: {$stateName}",
                    'data' => ['tracking_number' => $trackingNumber, 'state' => $stateName]
                ];
                Log::warning("Row {$rowNumber}: State not found", [
                    'tracking_number' => $trackingNumber,
                    'state_name' => $stateName
                ]);
            }

            if (!$cityId && !empty($cityName)) {
                $this->detailedErrors[] = [
                    'row' => $rowNumber,
                    'error' => "City not found in database: {$cityName} (State: {$stateName})",
                    'data' => ['tracking_number' => $trackingNumber, 'city' => $cityName, 'state' => $stateName]
                ];
                Log::warning("Row {$rowNumber}: City not found", [
                    'tracking_number' => $trackingNumber,
                    'city_name' => $cityName,
                    'state_name' => $stateName
                ]);
            }

            // Determine delivery type based on COD amount
            $deliveryType = 'home_delivery'; // Default to home delivery
            if (is_numeric($codAmount) && floatval($codAmount) > 0) {
                $deliveryType = 'stopdesk'; // If COD amount > 0, set as stopdesk
            }

            // Get company ID - use provided company or default to 1
            $companyId = $this->companyId ?? 1;
            
            // Debug: Log what we're about to save
            Log::info("Creating parcel with parcel_creation_date", [
                'tracking_number' => $trackingNumber,
                'parcel_creation_date' => $parcelCreationDate,
                'parcel_creation_date_type' => gettype($parcelCreationDate),
                'parcel_creation_date_is_null' => is_null($parcelCreationDate)
            ]);
            
            // Create parcel
            $parcel = new Parcel([
                'tracking_number' => $trackingNumber,
                'company_id' => $companyId,
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
                'delivery_type' => $deliveryType, // Set delivery type based on COD
                'has_whatsapp_tag' => false, // Default to false
                'recipient_phone_whatsapp' => null, // Will be verified later
                'secondary_phone_whatsapp' => null, // Will be verified later
                'whatsapp_verified_at' => null, // Will be set when verified
                'description' => $products,
                'notes' => $notes,
                'reference' => $reference,
                'secondary_phone' => $secondaryPhone,
                'parcel_creation_date' => $parcelCreationDate,
                'status' => 'pending', // Default status
            ]);

            $this->importedCount++;
            
            return $parcel;

        } catch (\Exception $e) {
            $this->detailedErrors[] = [
                'row' => $rowNumber,
                'error' => 'Exception during import: ' . $e->getMessage(),
                'data' => $row
            ];
            Log::error("Row {$rowNumber}: Exception during import", [
                'error' => $e->getMessage(),
                'tracking_number' => $row['id'] ?? 'unknown',
                'client' => $row['client'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
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
            'date_creation' => 'nullable|date', // Date création (Parcel Creation Date) - optional date
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
     * Handle validation errors
     */
    public function onError(\Throwable $error)
    {
        $this->detailedErrors[] = [
            'row' => 'unknown',
            'error' => 'Validation error: ' . $error->getMessage(),
            'data' => []
        ];
        Log::error('Excel import validation error', [
            'error' => $error->getMessage(),
            'trace' => $error->getTraceAsString()
        ]);
    }

    /**
     * Get detailed errors that occurred during import
     */
    public function getDetailedErrors(): array
    {
        return $this->detailedErrors;
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
     * Get the count of skipped duplicate records
     */
    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Parse date from Excel cell value
     */
    private function parseDate($dateValue)
    {
        Log::info("parseDate called", [
            'value' => $dateValue,
            'type' => gettype($dateValue),
            'empty' => empty($dateValue),
            'is_string' => is_string($dateValue)
        ]);
        
        if (empty($dateValue)) {
            Log::info("parseDate returning null - empty value");
            return null;
        }

        // If it's already a Carbon instance or DateTime
        if ($dateValue instanceof \Carbon\Carbon || $dateValue instanceof \DateTime) {
            return $dateValue;
        }

        // If it's a numeric value (Excel serial date)
        if (is_numeric($dateValue)) {
            try {
                // Excel serial date starts from 1900-01-01
                $excelEpoch = new \DateTime('1900-01-01');
                $excelEpoch->add(new \DateInterval('P' . (int)$dateValue . 'D'));
                return $excelEpoch;
            } catch (\Exception $e) {
                Log::warning("Failed to parse Excel serial date: {$dateValue}", ['error' => $e->getMessage()]);
                return null;
            }
        }

        // Try to parse as string date
        $dateString = trim((string)$dateValue);
        if (empty($dateString)) {
            return null;
        }

        // Try common date formats
        $formats = [
            'Y-m-d',
            'd/m/Y',
            'm/d/Y',
            'd-m-Y',
            'Y-m-d H:i:s',
            'd/m/Y H:i:s',
            'm/d/Y H:i:s',
            'd-m-Y H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                $date = \DateTime::createFromFormat($format, $dateString);
                if ($date && $date->format($format) === $dateString) {
                    return $date;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try Carbon's flexible parsing
        try {
            $result = \Carbon\Carbon::parse($dateString);
            Log::info("parseDate success with Carbon", [
                'input' => $dateString,
                'result' => $result,
                'result_type' => gettype($result)
            ]);
            return $result;
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$dateString}", ['error' => $e->getMessage()]);
            return null;
        }
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