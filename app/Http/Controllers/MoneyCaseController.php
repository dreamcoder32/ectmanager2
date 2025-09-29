<?php

namespace App\Http\Controllers;

use App\Models\MoneyCase;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class MoneyCaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cases = MoneyCase::with(['collections', 'expenses'])
            ->withCount(['collections', 'expenses'])
            ->get()
            ->map(function ($case) {
                $case->calculated_balance = $case->calculateBalance();
                return $case;
            });

        return Inertia::render('MoneyCase/Index', [
            'cases' => $cases
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MoneyCase/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:money_cases,name',
            'description' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $case = MoneyCase::create($validated);

        return redirect()->route('money-cases.index')
            ->with('success', 'Money case created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MoneyCase $moneyCase)
    {
        $moneyCase->load(['collections.parcel', 'expenses']);
        $moneyCase->calculated_balance = $moneyCase->calculateBalance();

        return Inertia::render('MoneyCase/Show', [
            'case' => $moneyCase
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MoneyCase $moneyCase)
    {
        return Inertia::render('MoneyCase/Edit', [
            'case' => $moneyCase
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MoneyCase $moneyCase)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('money_cases', 'name')->ignore($moneyCase->id)],
            'description' => 'nullable|string',
            'balance' => 'nullable|numeric|min:0',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $moneyCase->update($validated);

        return redirect()->route('money-cases.index')
            ->with('success', 'Money case updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MoneyCase $moneyCase)
    {
        // Check if case has associated collections or expenses
        if ($moneyCase->collections()->count() > 0 || $moneyCase->expenses()->count() > 0) {
            return redirect()->route('money-cases.index')
                ->with('error', 'Cannot delete money case that has associated collections or expenses.');
        }

        $moneyCase->delete();

        return redirect()->route('money-cases.index')
            ->with('success', 'Money case deleted successfully.');
    }

    /**
     * Get active money cases for selection dropdowns.
     */
    public function getActiveCases()
    {
        $cases = MoneyCase::where('status', 'active')
            ->select('id', 'name', 'balance')
            ->get();

        return response()->json($cases);
    }

    /**
     * Update the balance of a money case.
     */
    public function updateBalance(MoneyCase $moneyCase)
    {
        $moneyCase->updateBalance();

        return response()->json([
            'success' => true,
            'balance' => $moneyCase->fresh()->calculated_balance
        ]);
    }

    /**
     * Activate a money case for the current user.
     */
    public function activateForUser(Request $request)
    {
        $validated = $request->validate([
            'case_id' => 'required|exists:money_cases,id'
        ]);

        $moneyCase = MoneyCase::findOrFail($validated['case_id']);
        
        // Check if case is active
        if ($moneyCase->status !== 'active') {
            return back()->withErrors([
                'case_activation' => 'Money case is not active'
            ]);
        }

        // Activate the case for the current user
        $moneyCase->activateForUser(auth()->id());

        return back()->with([
            'success' => true,
            'message' => 'Money case activated successfully'
        ]);
    }
}
