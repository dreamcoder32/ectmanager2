<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\State;
use App\Models\City;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Collection;
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
                $skippedCount = $import->getSkippedCount();
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
                    'skipped_count' => $skippedCount,
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
                        'skipped_count' => $skippedCount,
                        'errors' => $errors,
                        'has_errors' => true
                    ], 422);
                }
                
                $message = "Successfully imported {$importedCount} parcels";
                if ($skippedCount > 0) {
                    $message .= " ({$skippedCount} duplicates skipped)";
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
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

    public function stopDeskPayment(Request $request){
        $recentCollections = \App\Models\Collection::with(['parcel'])
            ->where('created_by', auth()->id())
            ->whereDoesntHave('recoltes') // Exclude collections that are part of any recolte
            ->orderBy('collected_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'tracking_number' => $collection->parcel->tracking_number ?? 'N/A',
                    'cod_amount' => $collection->parcel->cod_amount ?? 0,
                    'collected_at' => $collection->collected_at,
                    'changeAmount' => $collection->amount - ($collection->parcel->cod_amount ?? 0),
                ];
            });

        // Debug: Log the query for troubleshooting
        \Log::info('Stopdesk collections query', [
            'user_id' => auth()->id(),
            'total_collections' => \App\Models\Collection::where('created_by', auth()->id())->count(),
            'collections_with_recoltes' => \App\Models\Collection::where('created_by', auth()->id())->whereHas('recoltes')->count(),
            'collections_without_recoltes' => \App\Models\Collection::where('created_by', auth()->id())->whereDoesntHave('recoltes')->count(),
            'filtered_count' => $recentCollections->count()
        ]);

        // Get active money cases for case selection - show free cases and cases used by current user
        $currentUserId = auth()->id();
        $activeCases = \App\Models\MoneyCase::where('status', 'active')
            ->where(function ($query) use ($currentUserId) {
                $query->whereNull('last_active_by') // Free cases
                      ->orWhere('last_active_by', $currentUserId); // Cases used by current user
            })
            ->orderBy('name')
            ->get()
            ->map(function ($case) use ($currentUserId) {
                return [
                    'id' => $case->id,
                    'name' => $case->name,
                    'description' => $case->description,
                    'balance' => $case->calculated_balance,
                    'currency' => $case->currency,
                    'is_user_active' => $case->last_active_by === $currentUserId,
                ];
            });

        // Find the user's last active case
        $userLastActiveCase = \App\Models\MoneyCase::where('status', 'active')
            ->where('last_active_by', $currentUserId)
            ->orderBy('last_activated_at', 'desc')
            ->first();

        return Inertia::render('StopDeskPayment/Index', [
            'recentCollections' => $recentCollections,
            'activeCases' => $activeCases,
            'userLastActiveCaseId' => $userLastActiveCase ? $userLastActiveCase->id : null,
            'auth' => [
                'user' => [
                    'id' => auth()->user()->id,
                    'can_collect_stopdesk' => auth()->user()->can_collect_stopdesk ?? false
                ]
            ]
        ]);
    }
    /**
     * Search for a parcel by tracking number for stopdesk payment
     */
    public function searchByTrackingNumber(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string'
        ]);

        // First, check if parcel exists at all
        $parcel = Parcel::with(['company', 'state', 'city'])
            ->where('tracking_number', $request->tracking_number)
            ->first();

        if (!$parcel) {
            // Parcel doesn't exist - allow manual entry
            return response()->json([
                'searchResult' => [
                    'success' => false,
                    'message' => 'Parcel not found',
                    'allow_manual_entry' => true
                ]
            ]);
        }

        // Check if parcel is already delivered/collected
        if ($parcel->status === 'delivered') {
            return response()->json([
                'searchResult' => [
                    'success' => false,
                    'message' => 'Parcel already delivered and collected',
                    'allow_manual_entry' => false,
                    'parcel_status' => 'delivered'
                ]
            ]);
        }

        // Parcel exists and not delivered - can be processed
        return response()->json([
            'searchResult' => [
                'success' => true,
                'parcel' => [
                    'id' => $parcel->id,
                    'tracking_number' => $parcel->tracking_number,
                    'recipient_name' => $parcel->recipient_name,
                    'recipient_phone' => $parcel->recipient_phone,
                    'recipient_address' => $parcel->recipient_address,
                    'cod_amount' => $parcel->cod_amount,
                    'status' => $parcel->status,
                    'company' => $parcel->company ? $parcel->company->name : null,
                    'state' => $parcel->state ? $parcel->state->name : null,
                    'city' => $parcel->city ? $parcel->city->name : null,
                ]
            ]
        ]);
    }

    /**
     * Confirm payment for stopdesk collection
     */
    public function confirmPayment(Request $request)
    {
        // Check if user can collect without money case
        $canCollectWithoutCase = auth()->user()->can_collect_stopdesk;
        
        $request->validate([
            'parcel_id' => 'required|exists:parcels,id',
            'amount_given' => 'required|numeric|min:0',
            'case_id' => $canCollectWithoutCase ? 'nullable|exists:money_cases,id' : 'required|exists:money_cases,id',
            'parcel_type' => 'required|in:stopdesk,home_delivery'
        ]);

     
        DB::beginTransaction();
        
        try {
            $parcel = Parcel::findOrFail($request->parcel_id);
            // Check if COD amount matches or if overpaid
            if ($request->amount_given < $parcel->cod_amount) {
                return back()->with([
                    'paymentResult' => [
                        'success' => false,
                        'message' => 'Insufficient payment amount'
                    ]
                ]);
            }



            // Get and activate the money case for the user
            // $moneyCase = \App\Models\MoneyCase::findOrFail($request->case_id);
            
            // Check if case is active and available
            // if (!$moneyCase->is_active) {
            //     return back()->with([
            //         'paymentResult' => [
            //             'success' => false,
            //             'message' => 'Selected money case is not active'
            //         ]
            //     ]);
            // }

            // Activate case for current user
            // $moneyCase->activateForUser(auth()->id());
            // Update parcel status to delivered
            $parcel->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'amount_paid' => $parcel->cod_amount
            ]);


                                    // return $parcel;

            // Calculate margin based on parcel type and company commission
            $margin = 0;
            if ($parcel->company) {
                if ($request->parcel_type === 'stopdesk') {
                    $margin = $parcel->company->commission ?? 0;
                } elseif ($request->parcel_type === 'home_delivery') {
                    $margin = $parcel->company->home_delivery_commission ?? 0;
                }
            }

            // Create collection record
            $collection = Collection::create([
                'collected_at' => now(),
                'parcel_id' => $parcel->id,
                'created_by' => auth()->id(),
                'note' => 'Stop desk payment collection',
                'amount' => $parcel->cod_amount,
                'amount_given' => $request->amount_given,
                'driver_id' => null, // Stop desk collections don't have drivers
                'margin' => $margin,
                'driver_commission' => null, // No driver commission for stop desk
                'case_id' => $request->case_id,
                'parcel_type' => $request->parcel_type,
            ]);

            // Money case balance is now calculated dynamically

            DB::commit();

            $changeAmount = $request->amount_given - $parcel->cod_amount;

            // Get updated recent collections for the user
            $recentCollections = \App\Models\Collection::with(['parcel.company'])
                ->where('created_by', auth()->id())
                ->orderBy('collected_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($collection) {
                    return [
                        'id' => $collection->id,
                        'tracking_number' => $collection->parcel->tracking_number ?? 'N/A',
                        'cod_amount' => $collection->parcel->cod_amount ?? 0,
                        'collected_at' => $collection->collected_at,
                        'changeAmount' => $collection->amount - ($collection->parcel->cod_amount ?? 0),
                        'parcel_type' => $collection->parcel_type ?? 'stopdesk',
                        'calculated_margin' => $collection->calculateMargin(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'parcel_id' => $parcel->id,
                'change_amount' => $changeAmount,
                'recentCollections' => $recentCollections
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment confirmation failed', [
                'parcel_id' => $request->parcel_id,
                'amount_given' => $request->amount_given,
                'case_id' => $request->case_id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with([
                'paymentResult' => [
                    'success' => false,
                    'message' => 'Payment confirmation failed: ' . $e->getMessage()
                ]
            ]);
        }
    }

    /**
     * Create a manual parcel and process payment at stop desk
     */
    public function createManualParcelAndCollect(Request $request)
    {
        // Check if user can collect without money case
        $canCollectWithoutCase = auth()->user()->can_collect_stopdesk;
        
        $request->validate([
            'tracking_number' => 'required|string|unique:parcels,tracking_number',
            'recipient_name' => 'required|string|max:255',
            'recipient_phone' => 'required|string|max:20',
            'recipient_address' => 'required|string',
            'cod_amount' => 'required|numeric|min:0.01',
            'amount_given' => 'required|numeric|min:0.01',
            'company' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'case_id' => $canCollectWithoutCase ? 'nullable|exists:money_cases,id' : 'required|exists:money_cases,id',
            'parcel_type' => 'required|in:stopdesk,home_delivery'
        ]);

        DB::beginTransaction();
        
        try {
            $moneyCase = null;
            
            // Only handle money case if case_id is provided
            if ($request->case_id) {
                // Get and activate the money case for the user
                $moneyCase = \App\Models\MoneyCase::findOrFail($request->case_id);
                
                // Check if case is active and available
                if (!$moneyCase->is_active) {
                    return back()->with([
                        'paymentResult' => [
                            'success' => false,
                            'message' => 'Selected money case is not active'
                        ]
                    ]);
                }

                // Activate case for current user
                $moneyCase->activateForUser(auth()->id());
            }

            // Create the parcel
            $parcel = Parcel::create([
                'tracking_number' => $request->tracking_number,
                'recipient_name' => $request->recipient_name,
                'recipient_phone' => $request->recipient_phone,
                'recipient_address' => $request->recipient_address,
                'cod_amount' => $request->cod_amount,
                'company' => $request->company,
                'state' => $request->state,
                'city' => $request->city,
                'status' => 'delivered', // Mark as delivered since payment is collected
                'delivered_at' => now(),
                'created_by' => auth()->id(),
            ]);

            // Calculate margin based on parcel type and company commission
            $margin = 0;
            if ($parcel->company) {
                if ($request->parcel_type === 'stopdesk') {
                    $margin = $parcel->company->commission ?? 0;
                } elseif ($request->parcel_type === 'home_delivery') {
                    $margin = $parcel->company->home_delivery_commission ?? 0;
                }
            }

            // Create the collection record
            $collection = Collection::create([
                'collected_at' => now(),
                'parcel_id' => $parcel->id,
                'created_by' => auth()->id(),
                'note' => 'Manual parcel entry and stop desk payment collection',
                'amount' => $request->amount_given,
                'driver_id' => null, // Stop desk collections don't have drivers
                'margin' => $margin,
                'driver_commission' => null, // No driver commission for stop desk
                'case_id' => $request->case_id,
                'parcel_type' => $request->parcel_type,
            ]);

            // Update money case balance only if case is provided
            if ($moneyCase) {
                $moneyCase->increment('balance', $request->amount_given);
            }

            DB::commit();

            $changeAmount = $request->amount_given - $parcel->cod_amount;

            // Get updated recent collections for the user
            $recentCollections = \App\Models\Collection::with(['parcel'])
                ->where('created_by', auth()->id())
                ->orderBy('collected_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($collection) {
                    return [
                        'id' => $collection->id,
                        'tracking_number' => $collection->parcel->tracking_number ?? 'N/A',
                        'cod_amount' => $collection->parcel->cod_amount ?? 0,
                        'collected_at' => $collection->collected_at,
                        'changeAmount' => $collection->amount - ($collection->parcel->cod_amount ?? 0),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Manual parcel created and payment confirmed successfully',
                'parcel_id' => $parcel->id,
                'change_amount' => $changeAmount,
                'collection' => [
                    'id' => $collection->id,
                    'collected_at' => $collection->collected_at->format('Y-m-d H:i:s'),
                    'amount' => $collection->amount,
                    'tracking_number' => $parcel->tracking_number,
                    'cod_amount' => $parcel->cod_amount,
                    'parcel' => [
                        'id' => $parcel->id,
                        'tracking_number' => $parcel->tracking_number,
                        'recipient_name' => $parcel->recipient_name,
                        'cod_amount' => $parcel->cod_amount,
                    ]
                ],
                'recentCollections' => $recentCollections
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Manual parcel creation failed: ' . $e->getMessage());
            
            return back()->with([
                'paymentResult' => [
                    'success' => false,
                    'message' => 'Failed to create manual parcel: ' . $e->getMessage()
                ]
            ]);
        }
    }
}
