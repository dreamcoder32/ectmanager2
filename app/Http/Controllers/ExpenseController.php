<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MoneyCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Expense::with(['createdBy', 'approvedBy', 'paidBy', 'moneyCase', 'category']);

        // Role-based filtering
        if ($user->role === 'agent') {
            // Agents can only see their own expenses
            $query->byUser($user->id);
        }
        // Supervisors and admins can see all expenses

        $expenses = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Expense/Index', [
            'expenses' => $expenses,
            'userRole' => $user->role
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $casesQuery = MoneyCase::where('status', 'active');

        if ($user->role === 'agent') {
            $casesQuery->where('last_active_by', $user->id);
        } elseif ($user->role === 'supervisor') {
            $companyIds = $user->companies()->pluck('companies.id');
            $casesQuery->whereIn('company_id', $companyIds);
        }
        // Admins see all active cases

        // Get active money cases
        $activeCases = $casesQuery->orderBy('name')
            ->get()
            ->map(function ($case) {
                return [
                    'id' => $case->id,
                    'name' => $case->name,
                    'description' => $case->description,
                    'balance' => $case->calculated_balance,
                ];
            });

        // Get active expense categories
        $categories = ExpenseCategory::active()
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        // Get recent recoltes (last 50)
        $recoltes = [];
        if (in_array($user->role, ['admin', 'supervisor'])) {
            $recoltesQuery = \App\Models\Recolte::with('createdBy')
                ->withSum('collections', 'amount')
                ->withSum('expenses', 'amount');

            if ($user->role === 'supervisor') {
                $recoltesQuery->whereNull('transfer_request_id');
            }

            $recoltes = $recoltesQuery->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($recolte) {
                    $total = $recolte->manual_amount ?? $recolte->collections_sum_amount ?? 0;
                    $expenses = $recolte->expenses_sum_amount ?? 0;
                    $balance = $total - $expenses;

                    return [
                        'id' => $recolte->id,
                        'code' => $recolte->code,
                        'created_at' => $recolte->created_at->format('Y-m-d'),
                        'creator' => $recolte->createdBy ? $recolte->createdBy->name : 'Unknown',
                        'balance' => $balance
                    ];
                });
        }

        return Inertia::render('Expense/Create', [
            'activeCases' => $activeCases,
            'categories' => $categories,
            'recoltes' => $recoltes
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'category_id' => 'required|exists:expense_categories,id',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
            'payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'case_id' => 'nullable|exists:money_cases,id',
            'recolte_id' => 'nullable|exists:recoltes,id'
        ]);

        // Ensure mutually exclusive selection
        if (!empty($validated['case_id']) && !empty($validated['recolte_id'])) {
            return back()->withErrors(['error' => 'Please select either a Money Case OR a Recolte, not both.']);
        }

        // Validate Balance
        if (!empty($validated['case_id'])) {
            $case = MoneyCase::find($validated['case_id']);
            if ($validated['amount'] > $case->calculated_balance) {
                return back()->withErrors(['amount' => 'Expense amount exceeds the Money Case balance (' . number_format($case->calculated_balance, 2) . ' ' . $validated['currency'] . ').']);
            }
        }

        if (!empty($validated['recolte_id'])) {
            $user = Auth::user();
            if ($user->role === 'agent') {
                return back()->withErrors(['recolte_id' => 'Agents are not allowed to link expenses to Recoltes.']);
            }

            $recolte = \App\Models\Recolte::withSum('collections', 'amount')
                ->withSum('expenses', 'amount')
                ->find($validated['recolte_id']);

            if ($user->role === 'supervisor' && $recolte->transfer_request_id) {
                return back()->withErrors(['recolte_id' => 'Cannot link expense to a transferred Recolte.']);
            }

            $total = $recolte->manual_amount ?? $recolte->collections_sum_amount ?? 0;
            $expenses = $recolte->expenses_sum_amount ?? 0;
            $balance = $total - $expenses;

            if ($validated['amount'] > $balance) {
                return back()->withErrors(['amount' => 'Expense amount exceeds the Recolte net total (' . number_format($balance, 2) . ' ' . $validated['currency'] . ').']);
            }
        }

        DB::beginTransaction();

        try {
            $expense = Expense::create([
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'currency' => $validated['currency'],
                'category_id' => $validated['category_id'],
                'description' => $validated['description'],
                'expense_date' => $validated['expense_date'],
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'case_id' => $validated['case_id'],
                'recolte_id' => $validated['recolte_id'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // If assigned to a case, deduct from case balance
            if ($validated['case_id']) {
                $moneyCase = MoneyCase::find($validated['case_id']);
                // $moneyCase->updateBalance();
            }

            DB::commit();

            return redirect()->route('expenses.index')
                ->with('success', 'Expense created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create expense: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        // Check if user can view this expense
        $this->authorizeExpenseAccess($expense);

        $expense->load(['createdBy', 'approvedBy', 'paidBy', 'moneyCase', 'category', 'salaryPayment', 'commissionPayment']);

        return Inertia::render('Expense/Show', [
            'expense' => $expense,
            'userRole' => Auth::user()->role
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Expense $expense)
    {
        // Check if user can edit this expense
        $this->authorizeExpenseAccess($expense);

        // Only allow editing if expense is pending
        if ($expense->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending expenses can be edited.']);
        }

        // Get active money cases
        $user = Auth::user();
        $casesQuery = MoneyCase::where('status', 'active');

        if ($user->role === 'agent') {
            $casesQuery->where('last_active_by', $user->id);
        } elseif ($user->role === 'supervisor') {
            $companyIds = $user->companies()->pluck('companies.id');
            $casesQuery->whereIn('company_id', $companyIds);
        }
        // Admins see all active cases

        $activeCases = $casesQuery->orderBy('name')
            ->get()
            ->map(function ($case) {
                return [
                    'id' => $case->id,
                    'name' => $case->name,
                    'description' => $case->description,
                    'balance' => $case->calculated_balance,
                ];
            });

        // Get active expense categories
        $categories = ExpenseCategory::active()
            ->select('id', 'name', 'description')
            ->orderBy('name')
            ->get();

        // Get recent recoltes (last 50)
        $recoltes = [];
        if (in_array($user->role, ['admin', 'supervisor'])) {
            $recoltesQuery = \App\Models\Recolte::with('createdBy')
                ->withSum('collections', 'amount')
                ->withSum('expenses', 'amount');

            if ($user->role === 'supervisor') {
                $recoltesQuery->whereNull('transfer_request_id');
            }

            $recoltes = $recoltesQuery->orderBy('created_at', 'desc')
                ->limit(50)
                ->get()
                ->map(function ($recolte) {
                    $total = $recolte->manual_amount ?? $recolte->collections_sum_amount ?? 0;
                    $expenses = $recolte->expenses_sum_amount ?? 0;
                    $balance = $total - $expenses;

                    return [
                        'id' => $recolte->id,
                        'code' => $recolte->code,
                        'created_at' => $recolte->created_at->format('Y-m-d'),
                        'creator' => $recolte->createdBy ? $recolte->createdBy->name : 'Unknown',
                        'balance' => $balance
                    ];
                });
        }

        return Inertia::render('Expense/Edit', [
            'expense' => $expense,
            'activeCases' => $activeCases,
            'categories' => $categories,
            'recoltes' => $recoltes
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        // Check if user can edit this expense
        $this->authorizeExpenseAccess($expense);

        // Only allow editing if expense is pending
        if ($expense->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending expenses can be edited.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'category_id' => 'required|exists:expense_categories,id',
            'description' => 'nullable|string',
            'expense_date' => 'required|date',
            'payment_method' => ['nullable', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'case_id' => 'nullable|exists:money_cases,id',
            'recolte_id' => 'nullable|exists:recoltes,id'
        ]);

        // Ensure mutually exclusive selection
        if (!empty($validated['case_id']) && !empty($validated['recolte_id'])) {
            return back()->withErrors(['error' => 'Please select either a Money Case OR a Recolte, not both.']);
        }

        // Validate Balance
        if (!empty($validated['case_id'])) {
            $case = MoneyCase::find($validated['case_id']);
            // If updating, add back the *current* expense amount to the balance before checking
            // But wait, calculated_balance excludes expenses based on DB state.
            // If this expense is already in the DB and linked to this case, it is already deducted.
            // So we should add it back to see the "available" balance for this update.
            $currentBalance = $case->calculated_balance;
            if ($expense->case_id == $case->id) {
                $currentBalance += $expense->amount;
            }

            if ($validated['amount'] > $currentBalance) {
                return back()->withErrors(['amount' => 'Expense amount exceeds the Money Case balance (' . number_format($currentBalance, 2) . ' ' . $validated['currency'] . ').']);
            }
        }

        if (!empty($validated['recolte_id'])) {
            $user = Auth::user();
            if ($user->role === 'agent') {
                return back()->withErrors(['recolte_id' => 'Agents are not allowed to link expenses to Recoltes.']);
            }

            $recolte = \App\Models\Recolte::withSum('collections', 'amount')
                ->withSum('expenses', 'amount')
                ->find($validated['recolte_id']);

            if ($user->role === 'supervisor' && $recolte->transfer_request_id) {
                return back()->withErrors(['recolte_id' => 'Cannot link expense to a transferred Recolte.']);
            }

            $total = $recolte->manual_amount ?? $recolte->collections_sum_amount ?? 0;
            $expenses = $recolte->expenses_sum_amount ?? 0;

            // Add back current expense if it was linked to this recolte
            if ($expense->recolte_id == $recolte->id) {
                $expenses -= $expense->amount;
            }

            $balance = $total - $expenses;

            if ($validated['amount'] > $balance) {
                return back()->withErrors(['amount' => 'Expense amount exceeds the Recolte net total (' . number_format($balance, 2) . ' ' . $validated['currency'] . ').']);
            }
        }

        DB::beginTransaction();

        try {
            $oldCaseId = $expense->case_id;
            $oldAmount = $expense->amount;

            // Update the expense
            $expense->update($validated);

            // Handle case balance updates
            if ($oldCaseId && $oldCaseId != $validated['case_id']) {
                // Remove from old case
                $oldCase = MoneyCase::find($oldCaseId);
                $oldCase->updateBalance();
            }

            if ($validated['case_id'] && $validated['case_id'] != $oldCaseId) {
                // Add to new case
                $newCase = MoneyCase::find($validated['case_id']);
                $newCase->updateBalance();
            } elseif ($validated['case_id'] && $validated['case_id'] == $oldCaseId && $oldAmount != $validated['amount']) {
                // Same case, different amount
                $case = MoneyCase::find($validated['case_id']);
                $case->updateBalance();
            }

            DB::commit();

            return redirect()->route('expenses.index')
                ->with('success', 'Expense updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update expense: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        // Check if user can delete this expense
        $this->authorizeExpenseAccess($expense);

        // Only allow deletion if expense is pending or rejected
        if (!in_array($expense->status, ['pending', 'rejected'])) {
            return back()->withErrors(['error' => 'Only pending or rejected expenses can be deleted.']);
        }

        DB::beginTransaction();

        try {
            // If expense was assigned to a case, add back the amount
            if ($expense->case_id) {
                $moneyCase = MoneyCase::find($expense->case_id);
                $moneyCase->updateBalance();
            }

            $expense->delete();

            DB::commit();

            return redirect()->route('expenses.index')
                ->with('success', 'Expense deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to delete expense: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve an expense.
     */
    public function approve(Expense $expense)
    {
        // Only supervisors and admins can approve
        if (!in_array(Auth::user()->role, ['supervisor', 'admin'])) {
            return back()->withErrors(['error' => 'You do not have permission to approve expenses.']);
        }

        if ($expense->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending expenses can be approved.']);
        }

        $expense->approve(Auth::user());

        return back()->with('success', 'Expense approved successfully.');
    }

    /**
     * Mark an expense as paid.
     */
    public function markAsPaid(Request $request, Expense $expense)
    {
        // Only supervisors and admins can mark as paid
        if (!in_array(Auth::user()->role, ['supervisor', 'admin'])) {
            return back()->withErrors(['error' => 'You do not have permission to mark expenses as paid.']);
        }

        if ($expense->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved expenses can be marked as paid.']);
        }

        $validated = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'payment_date' => 'required|date'
        ]);

        $expense->markAsPaid(
            Auth::user(),
            $validated['payment_method'],
            \Carbon\Carbon::parse($validated['payment_date'])
        );

        return back()->with('success', 'Expense marked as paid successfully.');
    }

    /**
     * Reject an expense.
     */
    public function reject(Expense $expense)
    {
        // Only supervisors and admins can reject
        if (!in_array(Auth::user()->role, ['supervisor', 'admin'])) {
            return back()->withErrors(['error' => 'You do not have permission to reject expenses.']);
        }

        if ($expense->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending expenses can be rejected.']);
        }

        DB::beginTransaction();

        try {
            // If expense was assigned to a case, add back the amount
            $expense->reject();

            // If expense was assigned to a case, update the balance
            if ($expense->case_id) {
                $moneyCase = MoneyCase::find($expense->case_id);
                $moneyCase->updateBalance();
            }

            DB::commit();

            return back()->with('success', 'Expense rejected successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to reject expense: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if the current user can access the expense.
     */
    private function authorizeExpenseAccess(Expense $expense)
    {
        $user = Auth::user();

        // Agents can only access their own expenses
        if ($user->role === 'agent' && $expense->created_by !== $user->id) {
            abort(403, 'You can only access your own expenses.');
        }

        // Supervisors and admins can access all expenses
    }
}
