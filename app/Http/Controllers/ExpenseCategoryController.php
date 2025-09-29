<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name')->get();

        return Inertia::render('ExpenseCategory/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ExpenseCategory/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        ExpenseCategory::create($validated);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->load(['expenses' => function ($query) {
            $query->with(['createdBy', 'approvedBy'])
                  ->orderBy('created_at', 'desc')
                  ->limit(10);
        }]);

        return Inertia::render('ExpenseCategory/Show', [
            'category' => $expenseCategory
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        return Inertia::render('ExpenseCategory/Edit', [
            'category' => $expenseCategory
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        $expenseCategory->update($validated);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Check if category has associated expenses
        if ($expenseCategory->expenses()->count() > 0) {
            return back()->withErrors([
                'error' => 'Cannot delete category that has associated expenses. Please reassign or delete the expenses first.'
            ]);
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')
            ->with('success', 'Expense category deleted successfully.');
    }

    /**
     * Toggle the active status of a category.
     */
    public function toggleStatus(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->update([
            'is_active' => !$expenseCategory->is_active
        ]);

        $status = $expenseCategory->is_active ? 'activated' : 'deactivated';
        
        return back()->with('success', "Expense category {$status} successfully.");
    }
}
