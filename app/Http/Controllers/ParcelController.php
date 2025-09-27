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
        try {
            // Validate the uploaded file
            $request->validate([
                'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Max 10MB
            ]);

            $file = $request->file('excel_file');
            
            // Create import instance
            $import = new ParcelsImport();
            
            DB::beginTransaction();
            
            try {
                // Import the Excel file
                Excel::import($import, $file);
                
                DB::commit();
                
                $importedCount = $import->getImportedCount();
                $errors = $import->errors();
                
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
                    'user_id' => Auth::id()
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
            return response()->json([
                'success' => false,
                'message' => 'File validation failed',
                'errors' => $e->errors(),
                'has_errors' => true
            ], 422);
        } catch (\Exception $e) {
            Log::error('Unexpected error during Excel import: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred during import',
                'errors' => ['An unexpected error occurred. Please try again.'],
                'has_errors' => true
            ], 500);
        }
    }
}
