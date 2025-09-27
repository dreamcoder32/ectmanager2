<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\State;
use App\Models\City;
use App\Models\Company;
use App\Models\Driver;
use App\Imports\ParcelsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class ParcelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Parcel::with(['company', 'assignedDriver', 'state', 'city']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%");
            });
        }

        // Get per_page from request, default to 15, max 100
        $perPage = min($request->get('per_page', 15), 100);
        
        $parcels = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return Inertia::render('Parcels/Index', [
            'parcels' => $parcels,
            'filters' => $request->only(['status', 'state_id', 'city_id', 'search']),
            'states' => State::active()->get(),
            'cities' => [], // Temporarily disable cities to test UTF-8 issue
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Parcels/Create', [
            'companies' => Company::active()->get(),
            'drivers' => Driver::active()->get(),
            'states' => State::active()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|unique:parcels,tracking_number',
            'company_id' => 'required|exists:companies,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'cod_amount' => 'required|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'assigned_driver_id' => 'nullable|exists:drivers,id',
        ]);

        $parcel = Parcel::create($validated);

        return redirect()->route('parcels.index')
            ->with('success', 'Parcel created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Parcel $parcel)
    {
        $parcel->load(['company', 'assignedDriver', 'state', 'city', 'collections', 'callLogs']);

        return Inertia::render('Parcels/Show', [
            'parcel' => $parcel,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Parcel $parcel)
    {
        $parcel->load(['company', 'assignedDriver', 'state', 'city']);

        return Inertia::render('Parcels/Edit', [
            'parcel' => $parcel,
            'companies' => Company::active()->get(),
            'drivers' => Driver::active()->get(),
            'states' => State::active()->get(),
            'cities' => City::where('state_id', $parcel->state_id)->active()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Parcel $parcel)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|unique:parcels,tracking_number,' . $parcel->id,
            'company_id' => 'required|exists:companies,id',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'cod_amount' => 'required|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'status' => 'required|in:pending,assigned,dispatched,delivered,returned,failed',
            'notes' => 'nullable|string',
            'assigned_driver_id' => 'nullable|exists:drivers,id',
        ]);

        $parcel->update($validated);

        return redirect()->route('parcels.index')
            ->with('success', 'Parcel updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Parcel $parcel)
    {
        $parcel->delete();

        return redirect()->route('parcels.index')
            ->with('success', 'Parcel deleted successfully.');
    }

    /**
     * Get cities by state ID.
     */
    public function getCitiesByState(State $state)
    {
        return response()->json([
            'cities' => $state->cities()->active()->get(),
        ]);
    }

    /**
     * Get all states.
     */
    public function getStates()
    {
        return response()->json([
            'states' => State::active()->get(),
        ]);
    }

    /**
     * Get all cities for a specific state.
     */
    public function getCities(Request $request)
    {
        $query = City::active();

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        return response()->json([
            'cities' => $query->get(),
        ]);
    }

    /**
     * Import parcels from Excel file.
     */
    public function importExcel(Request $request)
    {
        // Set execution limits for large file processing
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '512M'); // Increase memory for processing
        
        // Log the start of import process
        Log::info('Excel import started', [
            'user_id' => Auth::id(),
            'request_size' => $request->header('Content-Length'),
            'ip' => $request->ip()
        ]);
        
        try {
            // Validate the uploaded file
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:51200', // Max 50MB
            ]);

            $file = $request->file('excel_file');
            
            // Log file details
            Log::info('Excel file validated', [
                'filename' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'user_id' => Auth::id()
            ]);
            
            // Create import instance
            $import = new ParcelsImport();
            
            DB::beginTransaction();
            
            try {
                // Import the Excel file
                Log::info('Starting Excel import process', [
                    'filename' => $file->getClientOriginalName(),
                    'user_id' => Auth::id()
                ]);
                
                Excel::import($import, $file);
                
                DB::commit();
                
                $importedCount = $import->getImportedCount();
                $errors = $import->errors();
                $detailedErrors = $import->getDetailedErrors();
                
                // Log detailed errors if any
                if (!empty($detailedErrors)) {
                    Log::warning('Excel import detailed errors', [
                        'filename' => $file->getClientOriginalName(),
                        'user_id' => Auth::id(),
                        'detailed_errors' => $detailedErrors
                    ]);
                    
                    // Log each error individually for better readability
                    foreach ($detailedErrors as $index => $error) {
                        Log::warning("Excel import error #{$index}", [
                            'filename' => $file->getClientOriginalName(),
                            'row' => $error['row'],
                            'error' => $error['error'],
                            'data' => $error['data'] ?? []
                        ]);
                    }
                }
                
                Log::info('Excel import completed', [
                    'imported_count' => $importedCount,
                    'errors_count' => count($errors),
                    'detailed_errors_count' => count($detailedErrors),
                    'filename' => $file->getClientOriginalName(),
                    'user_id' => Auth::id()
                ]);
                
                if (count($errors) > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Import completed with some errors',
                        'imported_count' => $importedCount,
                        'errors' => $errors,
                        'has_errors' => true
                    ], 422);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => "Successfully imported {$importedCount} parcels",
                    'imported_count' => $importedCount,
                    'errors' => [],
                    'has_errors' => false
                ]);
                
            } catch (\Exception $e) {
                DB::rollBack();
                
                Log::error('Excel import failed: ' . $e->getMessage(), [
                    'file' => $file->getClientOriginalName(),
                    'user_id' => Auth::id(),
                    'exception' => $e->getTraceAsString(),
                    'line' => $e->getLine(),
                    'file_path' => $e->getFile()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Import failed: ' . $e->getMessage(),
                    'imported_count' => 0,
                    'errors' => [$e->getMessage()],
                    'has_errors' => true
                ], 500);
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Excel file validation failed', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors(),
                'has_errors' => true
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error during Excel import: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file_path' => $e->getFile(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during import',
                'errors' => ['An unexpected error occurred. Please try again.'],
                'has_errors' => true
            ], 500);
        }
    }

    /**
     * Search for a parcel by tracking number for stopdesk payment
     */
    public function searchByTrackingNumber(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string'
        ]);

        $parcel = Parcel::with(['company', 'state', 'city'])
            ->where('tracking_number', $request->tracking_number)
            ->where('status', '!=', 'delivered') // Only show undelivered parcels
            ->first();

        if (!$parcel) {
            return back()->with('flash', [
                'success' => false,
                'message' => 'Parcel not found or already delivered'
            ]);
        }

        return back()->with('flash', [
            'success' => true,
            'parcel' => [
                'id' => $parcel->id,
                'tracking_number' => $parcel->tracking_number,
                'recipient_name' => $parcel->recipient_name,
                'recipient_phone' => $parcel->recipient_phone,
                'cod_amount' => $parcel->cod_amount,
                'status' => $parcel->status,
                'company' => $parcel->company ? $parcel->company->name : null,
                'state' => $parcel->state ? $parcel->state->name : null,
                'city' => $parcel->city ? $parcel->city->name : null,
            ]
        ]);
    }

    /**
     * Confirm payment for stopdesk collection
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'parcel_id' => 'required|exists:parcels,id',
            'amount_paid' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $parcel = Parcel::findOrFail($request->parcel_id);
            
            // Update parcel status to delivered
            $parcel->update([
                'status' => 'delivered',
                'delivered_at' => now()
            ]);

            // Create collection record
            $collection = \App\Models\Collection::create([
                'parcel_id' => $parcel->id,
                'collected_at' => now(),
                'amount' => $request->amount_paid,
                'created_by' => Auth::id(),
                'note' => 'Stopdesk collection payment'
            ]);

            DB::commit();

            Log::info('Stopdesk payment confirmed', [
                'parcel_id' => $parcel->id,
                'tracking_number' => $parcel->tracking_number,
                'amount_paid' => $request->amount_paid,
                'collected_by' => Auth::id()
            ]);

            return back()->with('flash', [
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'parcel' => [
                    'id' => $parcel->id,
                    'tracking_number' => $parcel->tracking_number,
                    'recipient_name' => $parcel->recipient_name,
                    'cod_amount' => $parcel->cod_amount,
                    'amount_paid' => $collection->amount,
                    'delivered_at' => $parcel->delivered_at
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error confirming stopdesk payment', [
                'parcel_id' => $request->parcel_id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return back()->with('flash', [
                'success' => false,
                'message' => 'Error confirming payment'
            ]);
        }
    }
}
