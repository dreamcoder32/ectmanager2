<?php

namespace App\Http\Controllers;

use App\Models\CommissionPayment;
use App\Models\Collection;
use App\Models\User;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommissionPaymentController extends Controller
{
    /**
     * Display a listing of commission payments.
     */
    public function index(Request $request)
    {
        $query = CommissionPayment::with(['user', 'collection', 'createdBy', 'paidBy']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by collection
        if ($request->has('collection_id')) {
            $query->where('collection_id', $request->collection_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byUserAndDateRange($request->user_id ?? null, $request->start_date, $request->end_date);
        }

        $commissionPayments = $query->orderBy('payment_date', 'desc')
                                   ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $commissionPayments
        ]);
    }

    /**
     * Store a newly created commission payment.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'collection_id' => 'required|exists:collections,id',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'base_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        // Verify that the collection belongs to the user or user is eligible for commission
        $collection = Collection::findOrFail($request->collection_id);
        $user = User::findOrFail($request->user_id);

        if (!$user->hasCommissionConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have commission configuration'
            ], 422);
        }

        // Check if commission payment already exists for this collection
        $existingPayment = CommissionPayment::where('collection_id', $request->collection_id)->first();
        if ($existingPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Commission payment already exists for this collection'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $commissionPayment = CommissionPayment::create([
                'user_id' => $request->user_id,
                'collection_id' => $request->collection_id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'commission_rate' => $request->commission_rate,
                'base_amount' => $request->base_amount,
                'payment_date' => $request->payment_date,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'created_by' => Auth::id()
            ]);

            // Create corresponding expense record
            Expense::createFromCommissionPayment($commissionPayment);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commission payment created successfully',
                'data' => $commissionPayment->load(['user', 'collection', 'createdBy'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create commission payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified commission payment.
     */
    public function show(CommissionPayment $commissionPayment)
    {
        return response()->json([
            'success' => true,
            'data' => $commissionPayment->load(['user', 'collection', 'createdBy', 'paidBy'])
        ]);
    }

    /**
     * Update the specified commission payment.
     */
    public function update(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
            'base_amount' => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        // Only allow updates if payment is not yet paid
        if ($commissionPayment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a paid commission payment'
            ], 422);
        }

        $commissionPayment->update($request->only([
            'amount', 'currency', 'commission_rate', 'base_amount', 
            'payment_date', 'payment_method', 'notes'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Commission payment updated successfully',
            'data' => $commissionPayment->load(['user', 'collection', 'createdBy', 'paidBy'])
        ]);
    }

    /**
     * Mark commission payment as paid.
     */
    public function markAsPaid(Request $request, CommissionPayment $commissionPayment)
    {
        $request->validate([
            'payment_method' => ['sometimes', Rule::in(['cash', 'bank_transfer', 'check', 'card'])],
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($commissionPayment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Commission payment is already marked as paid'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $commissionPayment->markAsPaid(Auth::id(), $request->payment_method, $request->notes);

            // Update corresponding expense record
            $expense = Expense::where('reference_type', 'commission_payment')
                             ->where('reference_id', $commissionPayment->id)
                             ->first();
            
            if ($expense) {
                $expense->markAsPaid(Auth::id(), $request->payment_method);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commission payment marked as paid successfully',
                'data' => $commissionPayment->load(['user', 'collection', 'createdBy', 'paidBy'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark commission payment as paid: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate commission for completed collections.
     */
    public function calculateCommissions(Request $request)
    {
        $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        // Get collections in date range that don't have commission payments yet
        $query = Collection::whereBetween('collected_at', [$request->start_date, $request->end_date])
                          ->whereDoesntHave('commissionPayments'); // Only collections without existing commission payments

        if ($request->has('user_id')) {
            // Filter by specific user (created_by)
            $query->where('created_by', $request->user_id);
        }

        $collections = $query->with(['createdBy', 'parcel'])->get();
        $calculatedCommissions = [];

        foreach ($collections as $collection) {
            if (!$collection->createdBy || !$collection->createdBy->hasCommissionConfigured()) {
                continue;
            }

            $commissionAmount = $collection->createdBy->calculateCommission($collection->amount);
            
            if ($commissionAmount > 0) {
                $calculatedCommissions[] = [
                    'collection_id' => $collection->id,
                    'user_id' => $collection->createdBy->id,
                    'user_name' => $collection->createdBy->name,
                    'collection_total' => $collection->amount,
                    'commission_rate' => $collection->createdBy->commission_rate,
                    'commission_amount' => $commissionAmount,
                    'collection_date' => $collection->collected_at
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'calculated_commissions' => $calculatedCommissions,
                'total_commission_amount' => collect($calculatedCommissions)->sum('commission_amount'),
                'total_collections' => count($calculatedCommissions)
            ]
        ]);
    }

    /**
     * Generate commission payments for calculated commissions.
     */
    public function generateCommissionPayments(Request $request)
    {
        $request->validate([
            'commissions' => 'required|array',
            'commissions.*.collection_id' => 'required|exists:collections,id',
            'commissions.*.user_id' => 'required|exists:users,id',
            'commissions.*.amount' => 'required|numeric|min:0',
            'commissions.*.commission_rate' => 'required|numeric|min:0|max:100',
            'commissions.*.base_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'check', 'card'])]
        ]);

        $createdPayments = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->commissions as $commissionData) {
                // Check if commission payment already exists
                $existingPayment = CommissionPayment::where('collection_id', $commissionData['collection_id'])->first();
                if ($existingPayment) {
                    $errors[] = "Commission payment already exists for collection ID: {$commissionData['collection_id']}";
                    continue;
                }

                $commissionPayment = CommissionPayment::create([
                    'user_id' => $commissionData['user_id'],
                    'collection_id' => $commissionData['collection_id'],
                    'amount' => $commissionData['amount'],
                    'currency' => 'DZD',
                    'commission_rate' => $commissionData['commission_rate'],
                    'base_amount' => $commissionData['base_amount'],
                    'payment_date' => $request->payment_date,
                    'status' => 'pending',
                    'payment_method' => $request->payment_method,
                    'created_by' => Auth::id()
                ]);

                // Create corresponding expense record
                Expense::createFromCommissionPayment($commissionPayment);

                $createdPayments[] = $commissionPayment->load(['user', 'collection']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdPayments) . ' commission payments generated successfully',
                'data' => [
                    'created_payments' => $createdPayments,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate commission payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get commission payment statistics.
     */
    public function statistics(Request $request)
    {
        $query = CommissionPayment::query();

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

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
            'average_commission_rate' => $query->avg('commission_rate'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Remove the specified commission payment.
     */
    public function destroy(CommissionPayment $commissionPayment)
    {
        // Only allow deletion if payment is not yet paid
        if ($commissionPayment->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete a paid commission payment'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Delete corresponding expense record
            Expense::where('reference_type', 'commission_payment')
                   ->where('reference_id', $commissionPayment->id)
                   ->delete();

            $commissionPayment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commission payment deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete commission payment: ' . $e->getMessage()
            ], 500);
        }
    }
}
