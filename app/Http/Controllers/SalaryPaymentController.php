<?php

namespace App\Http\Controllers;

use App\Models\SalaryPayment;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalaryPaymentController extends Controller
{
    /**
     * Display a listing of salary payments.
     */
    public function index(Request $request)
    {
        $query = SalaryPayment::with(['user', 'createdBy', 'paidBy']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by month/year
        if ($request->has('month') && $request->has('year')) {
            $query->byMonthYear($request->month, $request->year);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $salaryPayments = $query->orderBy('payment_date', 'desc')
                               ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $salaryPayments
        ]);
    }

    /**
     * Store a newly created salary payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'payment_month' => 'required|integer|between:1,12',
            'payment_year' => 'required|integer|min:2020',
            'payment_date' => 'required|date',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check if salary payment already exists for this user and month/year
        $existingPayment = SalaryPayment::where('user_id', $request->user_id)
                                      ->where('payment_month', $request->payment_month)
                                      ->where('payment_year', $request->payment_year)
                                      ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Salary payment already exists for this user and month/year'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salaryPayment = SalaryPayment::create([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'payment_month' => $request->payment_month,
                'payment_year' => $request->payment_year,
                'payment_date' => $request->payment_date,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            // Create corresponding expense record
            Expense::createFromSalaryPayment($salaryPayment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary payment created successfully',
                'data' => $salaryPayment->load(['user', 'createdBy'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create salary payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified salary payment.
     */
    public function show(SalaryPayment $salaryPayment)
    {
        return response()->json([
            'success' => true,
            'data' => $salaryPayment->load(['user', 'createdBy', 'paidBy'])
        ]);
    }

    /**
     * Update the specified salary payment.
     */
    public function update(Request $request, SalaryPayment $salaryPayment)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'payment_date' => 'sometimes|date',
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        // Only allow updates if payment is not yet paid
        if ($salaryPayment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a paid salary payment'
            ], 422);
        }

        $salaryPayment->update($request->only([
            'amount', 'currency', 'payment_date', 'payment_method', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Salary payment updated successfully',
            'data' => $salaryPayment->load(['user', 'createdBy', 'paidBy'])
        ]);
    }

    /**
     * Mark salary payment as paid.
     */
    public function markAsPaid(Request $request, SalaryPayment $salaryPayment)
    {
        $request->validate([
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($salaryPayment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Salary payment is already marked as paid'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salaryPayment->markAsPaid(Auth::id(), $request->payment_method, $request->notes);

            // Update corresponding expense record
            $expense = Expense::where('reference_type', 'salary_payment')
                             ->where('reference_id', $salaryPayment->id)
                             ->first();
            
            if ($expense) {
                $expense->markAsPaid(Auth::id(), $request->payment_method);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary payment marked as paid successfully',
                'data' => $salaryPayment->load(['user', 'createdBy', 'paidBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark salary payment as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate salary payments for all eligible users for a specific month/year.
     */
    public function generateMonthlyPayments(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'payment_date' => 'required|date'
        ]);

        // Get all users with active salary configuration
        $users = User::whereNotNull('salary_amount')
                    ->where('salary_is_active', true)
                    ->get();

        $createdPayments = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                // Check if payment already exists
                $existingPayment = SalaryPayment::where('user_id', $user->id)
                                              ->where('payment_month', $request->month)
                                              ->where('payment_year', $request->year)
                                              ->first();

                if ($existingPayment) {
                    $errors[] = "Payment already exists for user: {$user->name}";
                    continue;
                }

                $salaryPayment = SalaryPayment::create([
                    'user_id' => $user->id,
                    'amount' => $user->salary_amount,
                    'currency' => $user->salary_currency ?? 'DZD',
                    'payment_month' => $request->month,
                    'payment_year' => $request->year,
                    'payment_date' => $request->payment_date,
                    'status' => 'pending',
                    'payment_method' => 'bank_transfer',
                    'created_by' => Auth::id()
                ]);

                // Create corresponding expense record
                Expense::createFromSalaryPayment($salaryPayment);

                $createdPayments[] = $salaryPayment->load(['user']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdPayments) . ' salary payments generated successfully',
                'data' => [
                    'created_payments' => $createdPayments,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate salary payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get salary payment statistics.
     */
    public function statistics(Request $request)
    {
        $query = SalaryPayment::query();

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        $stats = [
            'total_payments' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'pending_payments' => $query->where('status', 'pending')->count(),
            'paid_payments' => $query->where('status', 'paid')->count(),
            'pending_amount' => $query->where('status', 'pending')->sum('amount'),
            'paid_amount' => $query->where('status', 'paid')->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Remove the specified salary payment.
     */
    public function destroy(SalaryPayment $salaryPayment)
    {
        // Only allow deletion if payment is not yet paid
        if ($salaryPayment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a paid salary payment'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete corresponding expense record
            Expense::where('reference_type', 'salary_payment')
                   ->where('reference_id', $salaryPayment->id)
                   ->delete();

            $salaryPayment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Salary payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete salary payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
