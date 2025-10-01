<?php

namespace App\Http\Controllers;

use App\Models\Recolte;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Routing\Controller as BaseController;

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
    public function index()
    {
        $recoltes = Recolte::with(['collections.parcel', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calculate cod_amount sum for each recolte
        $recoltes->getCollection()->transform(function ($recolte) {
            $recolte->total_cod_amount = $recolte->collections->sum(function ($collection) {
                return $collection->parcel ? $collection->parcel->cod_amount : 0;
            });
            return $recolte;
        });

        return Inertia::render('Recolte/Index', [
            'recoltes' => $recoltes
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get users who have created collections (agents and admins) with their available collections
        $users = \App\Models\User::whereHas('collections', function($query) {
                $query->whereDoesntHave('recoltes');
            })
            ->with(['collections' => function($query) {
                $query->with(['parcel'])
                    ->whereDoesntHave('recoltes')
                    ->orderBy('collected_at', 'desc');
            }])
            ->select('id', 'display_name', 'email', 'role')
            ->orderBy('display_name')
            ->get();

        // Calculate total amounts for each user's collections
        $users->each(function($user) {
            $user->total_amount = $user->collections->sum('amount');
        });

        // Get active money cases
        $activeCases = \App\Models\MoneyCase::where('status', 'active')
            ->select('id', 'name', 'description', 'balance')
            ->orderBy('name')
            ->get();

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
            'case_id' => 'nullable|exists:money_cases,id'
        ]);

        DB::beginTransaction();
        
        try {
            // Create the recolte
            $recolte = Recolte::create([
                'note' => $request->note,
                'created_by' => Auth::id()
            ]);

            // Attach the selected collections and update their case_id if provided
            $collections = Collection::whereIn('id', $request->collection_ids)->get();
            
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
                $collections->each(function($collection) use ($request) {
                    $collection->update(['case_id' => $request->case_id]);
                });
                
                // Update the money case balance
                $totalAmount = $collections->sum('amount');
                $moneyCase = \App\Models\MoneyCase::find($request->case_id);
                $moneyCase->updateBalance($totalAmount);
            }
            
            $recolte->collections()->attach($request->collection_ids);

            DB::commit();

            return redirect()->route('recoltes.index')
                ->with('success', "Recolte #{$recolte->code} created successfully with " . count($request->collection_ids) . " collections.");

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
        $recolte->load(['collections.parcel', 'createdBy']);
        
        $totalAmount = $recolte->collections->sum('amount');
        $totalCollections = $recolte->collections->count();
        $totalCodAmount = $recolte->collections->sum(function ($collection) {
            return $collection->parcel ? $collection->parcel->cod_amount : 0;
        });

        return Inertia::render('Recolte/Show', [
            'recolte' => $recolte,
            'totalAmount' => $totalAmount,
            'totalCollections' => $totalCollections,
            'totalCodAmount' => $totalCodAmount
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Recolte $recolte)
    {
        $recolte->load(['collections.parcel']);
        
        // Get available collections that haven't been recolted yet (excluding current recolte's collections)
        $availableCollections = Collection::with(['parcel'])
            ->whereDoesntHave('recoltes', function ($query) use ($recolte) {
                $query->where('recolte_id', '!=', $recolte->id);
            })
            ->orderBy('collected_at', 'desc')
            ->get();

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
