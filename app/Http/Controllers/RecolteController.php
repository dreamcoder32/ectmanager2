<?php

namespace App\Http\Controllers;

use App\Models\Recolte;
use App\Models\Collection;
use App\Exports\RecolteExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Dompdf\Dompdf;
use Dompdf\Options;

class RecolteController extends BaseController
{
    public function __construct()
    {
        // Only admin and supervisor can access recolte operations
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole(['admin', 'supervisor'])) {
                abort(403, 'Unauthorized. Only admin and supervisor can perform recolte operations.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get company filter from request
        $companyFilter = $request->input('company_id');

        // Validate company access if filter is provided
        if ($companyFilter && $user->role !== 'admin' && !$user->belongsToCompany($companyFilter)) {
            abort(403, 'You do not have access to this company.');
        }

        // Filter recoltes based on user's company access
        if ($user->role === 'admin') {
            // Admins can see all recoltes
            // Optimized: Removed 'collections.parcel' as it's not needed for the index view and is heavy
            $query = Recolte::with(['collections.driver', 'collections.createdBy', 'createdBy', 'company', 'expenses', 'transferRequest']);

            // Apply company filter if provided
            if ($companyFilter) {
                $query->where('company_id', $companyFilter);
            }

            $recoltes = $query->orderBy('created_at', 'desc')->paginate(20);
        } else {
            // Other users can only see recoltes from their assigned companies
            $userCompanyIds = $user->companies()->pluck('companies.id');

            // Apply company filter if provided (must be within user's companies)
            if ($companyFilter) {
                $userCompanyIds = $userCompanyIds->intersect([$companyFilter]);
            }

            $recoltes = Recolte::with(['collections.driver', 'collections.createdBy', 'createdBy', 'company', 'expenses', 'transferRequest'])
                ->whereIn('company_id', $userCompanyIds)
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        // Calculate cod_amount sum for each recolte and determine type/name
        $recoltes->getCollection()->transform(function ($recolte) {
            $recolte->total_cod_amount = $recolte->collections->sum(function ($collection) {
                return $collection->amount ?? 0;
            });

            $firstCollection = $recolte->collections->first();
            if ($firstCollection && $firstCollection->driver_id) {
                $recolte->type = 'driver';
                $recolte->related_name = $firstCollection->driver ? $firstCollection->driver->name : 'Unknown Driver';
            } else {
                $recolte->type = 'agent';
                $recolte->related_name = ($firstCollection && $firstCollection->createdBy) ? $firstCollection->createdBy->first_name . ' ' . $firstCollection->createdBy->last_name : 'Unknown Agent';
            }

            $recolte->expenses_count = $recolte->expenses->count();
            $recolte->total_expenses = $recolte->expenses->sum('amount');
            $recolte->net_total = ($recolte->manual_amount ?? $recolte->total_cod_amount) - $recolte->total_expenses;

            return $recolte;
        });

        // Get companies for filtering
        if ($user->role === 'admin') {
            $companies = \App\Models\Company::active()->get(['id', 'name', 'code']);
        } else {
            $companies = $user->companies()->where('is_active', true)->get(['companies.id', 'companies.name', 'companies.code']);
        }

        $admins = \App\Models\User::where('role', 'admin')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('Recolte/Index', [
            'recoltes' => $recoltes,
            'companies' => $companies,
            'admins' => $admins,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();

        // Get users who have created collections (agents and admins) with their available collections
        // Filter by company access
        if ($user->role === 'admin') {
            // Admins can see all users and their collections
            $users = \App\Models\User::whereHas('collections', function ($query) {
                $query->whereDoesntHave('recoltes');
            })
                ->with([
                    'collections' => function ($query) {
                        $query->with(['parcel'])
                            ->whereDoesntHave('recoltes')
                            ->orderBy('collected_at', 'desc');
                    }
                ])
                ->select('id', 'first_name', 'email', 'role', 'uid')
                ->orderBy('first_name')
                ->get();
        } else {
            // Other users can only see users from their assigned companies
            $userCompanyIds = $user->companies()->pluck('companies.id');
            $users = \App\Models\User::whereHas('collections', function ($query) use ($userCompanyIds) {
                $query->whereDoesntHave('recoltes')
                    ->whereHas('parcel', function ($parcelQuery) use ($userCompanyIds) {
                        $parcelQuery->whereIn('company_id', $userCompanyIds);
                    });
            })
                ->with([
                    'collections' => function ($query) use ($userCompanyIds) {
                        $query->with(['parcel'])
                            ->whereDoesntHave('recoltes')
                            ->whereHas('parcel', function ($parcelQuery) use ($userCompanyIds) {
                                $parcelQuery->whereIn('company_id', $userCompanyIds);
                            })
                            ->orderBy('collected_at', 'desc');
                    }
                ])
                ->select('id', 'first_name', 'email', 'role', 'uid')
                ->orderBy('first_name')
                ->get();
        }

        // Fetch unlinked expenses for each user based on their collections' case_ids
        // We iterate through users to get their collection case IDs
        $users->each(function ($user) {
            $caseIds = $user->collections->pluck('case_id')->unique()->filter();

            if ($caseIds->isNotEmpty()) {
                $user->unlinked_expenses = \App\Models\Expense::whereIn('case_id', $caseIds)
                    ->whereNull('recolte_id')
                    ->where('status', '!=', 'rejected')
                    ->get();
            } else {
                $user->unlinked_expenses = collect();
            }

            $user->total_amount = $user->collections->sum('amount');
        });

        // Get active money cases - filter by user's companies
        if ($user->role === 'admin') {
            // Admins can see all active money cases
            $activeCases = \App\Models\MoneyCase::where('status', 'active')
                ->select('id', 'name', 'description', 'balance')
                ->orderBy('name')
                ->get();
        } else {
            // Other users can only see money cases from their assigned companies
            $userCompanyIds = $user->companies()->pluck('companies.id');
            $activeCases = \App\Models\MoneyCase::where('status', 'active')
                ->whereIn('company_id', $userCompanyIds)
                ->select('id', 'name', 'description', 'balance')
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Recolte/Create', [
            'users' => $users,
            'activeCases' => $activeCases
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
            'collection_ids' => 'required|array|min:1',
            'collection_ids.*' => 'exists:collections,id',
            'case_id' => 'nullable|exists:money_cases,id',
            'manual_amount' => 'required|numeric|min:0',
            'amount_discrepancy_note' => 'nullable|string|max:1000'
        ]);

        DB::beginTransaction();

        try {
            // Get the collections to determine the company and lock them to prevent race conditions
            $collections = Collection::whereIn('id', $request->collection_ids)
                ->lockForUpdate() // Lock these rows
                ->with('parcel')
                ->get();

            // Check if any collection is already attached to a recolte
            // We need to check the pivot table. Since we have the collections, we can check their relation.
            // However, lockForUpdate on collections table doesn't lock the pivot table.
            // But if we check the relation NOW, inside the transaction, we should be safe if everyone follows this protocol.

            foreach ($collections as $collection) {
                if ($collection->recoltes()->exists()) {
                    throw new \Exception("Collection #{$collection->id} (Parcel: {$collection->parcel->tracking_number}) has already been processed in another Recolte.");
                }
            }

            // Determine company from collections' parcels
            $companyIds = $collections->pluck('parcel.company_id')->unique()->filter();

            if ($companyIds->count() === 0) {
                throw new \Exception('No valid company found for the selected collections.');
            }

            if ($companyIds->count() > 1) {
                throw new \Exception('All collections must belong to the same company.');
            }

            $companyId = $companyIds->first();

            // Calculate total amount from collections
            $totalAmount = $collections->sum('amount');
            $manualAmount = $request->manual_amount;

            // Check for discrepancy and validate note requirement
            $hasDiscrepancy = abs($totalAmount - $manualAmount) > 0.01; // Allow for small rounding differences
            if ($hasDiscrepancy && empty($request->amount_discrepancy_note)) {
                return back()->withErrors([
                    'amount_discrepancy_note' => 'A note explaining the amount discrepancy is required when the manual amount differs from the calculated total.'
                ])->withInput();
            }

            // Validate user has access to this company
            $user = Auth::user();
            if ($user->role !== 'admin' && !$user->belongsToCompany($companyId)) {
                throw new \Exception('You do not have access to this company.');
            }

            // Create the recolte
            $recolte = Recolte::create([
                'note' => $request->note,
                'created_by' => Auth::id(),
                'company_id' => $companyId,
                'manual_amount' => $manualAmount,
                'amount_discrepancy_note' => $hasDiscrepancy ? $request->amount_discrepancy_note : null
            ]);

            // Attach the selected collections and update their case_id if provided

            // Free money cases for collections being recolted
            $casesToFree = $collections->whereNotNull('case_id')->pluck('case_id')->unique();
            foreach ($casesToFree as $caseId) {
                $moneyCase = \App\Models\MoneyCase::find($caseId);
                if ($moneyCase) {
                    $moneyCase->update(['last_active_by' => null]);
                }
            }

            if ($request->case_id) {
                // Update collections to assign them to the selected case
                $collections->each(function ($collection) use ($request) {
                    $collection->update(['case_id' => $request->case_id]);
                });

                // Update the money case balance
                $totalAmount = $collections->sum('amount');
                $moneyCase = \App\Models\MoneyCase::find($request->case_id);
                $moneyCase->updateBalance();
            }

            $recolte->collections()->attach($request->collection_ids);

            $message = "Recolte #{$recolte->code} created successfully with " . count($request->collection_ids) . " collections.";

            // Automatically link unlinked expenses based on the collections' case_ids
            // We get the case_ids from the collections we just attached
            $caseIds = $collections->pluck('case_id')->unique()->filter();

            if ($caseIds->isNotEmpty()) {
                // Find unlinked expenses for these cases
                $expensesToLink = \App\Models\Expense::whereIn('case_id', $caseIds)
                    ->whereNull('recolte_id')
                    ->where('status', '!=', 'rejected')
                    ->get();

                if ($expensesToLink->count() > 0) {
                    foreach ($expensesToLink as $expense) {
                        $expense->update(['recolte_id' => $recolte->id]);
                    }
                    $message .= " Linked " . $expensesToLink->count() . " expenses.";
                }
            }

            DB::commit();

            $message .= " Calculated total: " . number_format($totalAmount, 2) . " DZD, Manual amount: " . number_format($manualAmount, 2) . " DZD";

            if ($hasDiscrepancy) {
                $message .= " (Discrepancy noted)";
            }

            return redirect()->route('recoltes.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create recolte: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Recolte $recolte)
    {
        $recolte->load(['collections.parcel', 'createdBy', 'expenses']);

        $totalAmount = $recolte->collections->sum('amount');
        $totalCollections = $recolte->collections->count();
        $totalCodAmount = $recolte->collections->sum(function ($collection) {
            return $collection->parcel ? $collection->parcel->cod_amount : 0;
        });

        $totalExpenses = $recolte->expenses->sum('amount');
        $netTotal = $totalAmount - $totalExpenses;

        return Inertia::render('Recolte/Show', [
            'recolte' => $recolte,
            'totalAmount' => $totalAmount,
            'totalCollections' => $totalCollections,
            'totalCodAmount' => $totalCodAmount,
            'totalExpenses' => $totalExpenses,
            'netTotal' => $netTotal
        ]);
    }

    /**
     * Export recolte details to Excel.
     */
    public function export(Recolte $recolte)
    {
        $type = request()->query('type');
        if ($type === 'pdf') {
            $recolte->load(['collections.parcel', 'collections.createdBy', 'createdBy', 'expenses']);

            // Generate Barcode
            $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $barcodeData = $generator->getBarcode('RCT-' . $recolte->id, $generator::TYPE_CODE_128);
            $barcodeBase64 = base64_encode($barcodeData);

            $fileName = 'recolte_' . $recolte->code . '_' . date('Y-m-d') . '.pdf';
            $html = view('exports.recolte', [
                'recolte' => $recolte,
                'barcode' => $barcodeBase64,
            ])->render();

            // Generate PDF using Dompdf to avoid Node/Puppeteer dependency
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->setPaper('A4');
            $dompdf->loadHtml('<style>@page { margin: 5mm }</style>' . $html);
            $dompdf->render();
            $pdfOutput = $dompdf->output();

            return response()->stream(fn() => print ($pdfOutput), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        }

        $fileName = 'recolte_' . $recolte->code . '_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new RecolteExport($recolte), $fileName);
    }

    /**
     * Export multiple recoltes to a single PDF summary.
     */
    public function bulkExport(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:recoltes,id'
        ]);

        $recoltes = Recolte::with(['collections.driver', 'collections.createdBy', 'createdBy', 'expenses'])
            ->whereIn('id', $request->ids)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals for each recolte and grand totals
        $grandTotalCollected = 0;
        $grandTotalExpenses = 0;
        $grandNetTotal = 0;

        $recoltes->transform(function ($recolte) use (&$grandTotalCollected, &$grandTotalExpenses, &$grandNetTotal) {
            $recolte->total_cod_amount = $recolte->collections->sum(function ($collection) {
                return $collection->amount ?? 0;
            });

            $firstCollection = $recolte->collections->first();
            if ($firstCollection && $firstCollection->driver_id) {
                $recolte->type = 'driver';
                $recolte->related_name = $firstCollection->driver ? $firstCollection->driver->name : 'Unknown Driver';
            } else {
                $recolte->type = 'agent';
                $recolte->related_name = ($firstCollection && $firstCollection->createdBy) ? $firstCollection->createdBy->first_name . ' ' . $firstCollection->createdBy->last_name : 'Unknown Agent';
            }

            $recolte->total_expenses = $recolte->expenses->sum('amount');
            $recolte->net_total = ($recolte->manual_amount ?? $recolte->total_cod_amount) - $recolte->total_expenses;

            $grandTotalCollected += ($recolte->manual_amount ?? $recolte->total_cod_amount);
            $grandTotalExpenses += $recolte->total_expenses;
            $grandNetTotal += $recolte->net_total;

            return $recolte;
        });

        $fileName = 'recoltes_bulk_' . date('Y-m-d_H-i') . '.pdf';
        $html = view('exports.recoltes_bulk', [
            'recoltes' => $recoltes,
            'grandTotalCollected' => $grandTotalCollected,
            'grandTotalExpenses' => $grandTotalExpenses,
            'grandNetTotal' => $grandNetTotal
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml($html);
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        return response()->stream(fn() => print ($pdfOutput), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Export multiple recoltes to a single PDF with full details for each.
     */
    public function bulkExportDetailed(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:recoltes,id'
        ]);

        $recoltes = Recolte::with(['collections.parcel', 'collections.createdBy', 'createdBy', 'expenses'])
            ->whereIn('id', $request->ids)
            ->orderBy('created_at', 'desc')
            ->get();

        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();

        // Attach barcode to each recolte
        $recoltes->each(function ($recolte) use ($generator) {
            $barcodeData = $generator->getBarcode('RCT-' . $recolte->id, $generator::TYPE_CODE_128);
            $recolte->barcode_base64 = base64_encode($barcodeData);
        });

        $fileName = 'recoltes_bulk_detailed_' . date('Y-m-d_H-i') . '.pdf';
        $html = view('exports.recoltes_bulk_detailed', [
            'recoltes' => $recoltes,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');
        $dompdf->loadHtml('<style>@page { margin: 5mm }</style>' . $html);
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        return response()->stream(fn() => print ($pdfOutput), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recolte $recolte)
    {
        $user = auth()->user();
        $recolte->load(['collections.parcel']);

        // Get available collections that haven't been recolted yet (excluding current recolte's collections)
        // Filter by company access
        if ($user->role === 'admin') {
            // Admins can see all available collections
            $availableCollections = Collection::with(['parcel'])
                ->whereDoesntHave('recoltes', function ($query) use ($recolte) {
                    $query->where('recolte_id', '!=', $recolte->id);
                })
                ->orderBy('collected_at', 'desc')
                ->get();
        } else {
            // Other users can only see collections from their assigned companies
            $userCompanyIds = $user->companies()->pluck('companies.id');
            $availableCollections = Collection::with(['parcel'])
                ->whereDoesntHave('recoltes', function ($query) use ($recolte) {
                    $query->where('recolte_id', '!=', $recolte->id);
                })
                ->whereHas('parcel', function ($query) use ($userCompanyIds) {
                    $query->whereIn('company_id', $userCompanyIds);
                })
                ->orderBy('collected_at', 'desc')
                ->get();
        }

        return Inertia::render('Recolte/Edit', [
            'recolte' => $recolte,
            'availableCollections' => $availableCollections
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Recolte $recolte)
    {
        $request->validate([
            'note' => 'nullable|string|max:1000',
            'collection_ids' => 'required|array|min:1',
            'collection_ids.*' => 'exists:collections,id'
        ]);

        DB::beginTransaction();

        try {
            // Update the recolte
            $recolte->update([
                'note' => $request->note
            ]);

            // Sync the selected collections
            $recolte->collections()->sync($request->collection_ids);

            DB::commit();

            return redirect()->route('recoltes.index')
                ->with('success', "Recolte #{$recolte->code} updated successfully.");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update recolte: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Recolte $recolte)
    {
        DB::beginTransaction();

        try {
            // Detach all collections first
            $recolte->collections()->detach();

            // Delete the recolte
            $recolte->delete();

            DB::commit();

            return redirect()->route('recoltes.index')
                ->with('success', "Recolte #{$recolte->code} deleted successfully.");

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete recolte: ' . $e->getMessage()]);
        }
    }
}
