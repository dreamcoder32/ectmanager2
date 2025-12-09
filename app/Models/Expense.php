<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'amount',
        'currency',
        'category_id',
        'description',
        'expense_date',
        'status',
        'payment_method',
        'payment_date',
        'reference_type',
        'reference_id',
        'created_by',
        'approved_by',
        'paid_by',
        'approved_at',
        'paid_at',
        'case_id',
        'recolte_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'payment_date' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the expense category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    /**
     * Get the user who created the expense.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the expense.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who paid the expense.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the related salary payment if reference_type is salary_payment.
     */
    public function salaryPayment(): BelongsTo
    {
        return $this->belongsTo(SalaryPayment::class, 'reference_id')
            ->where('reference_type', 'salary_payment');
    }

    /**
     * Get the related commission payment if reference_type is commission_payment.
     */
    public function commissionPayment(): BelongsTo
    {
        return $this->belongsTo(CommissionPayment::class, 'reference_id')
            ->where('reference_type', 'commission_payment');
    }

    /**
     * Get the money case this expense belongs to.
     */
    public function moneyCase(): BelongsTo
    {
        return $this->belongsTo(MoneyCase::class, 'case_id');
    }

    /**
     * Get the recolte this expense belongs to.
     */
    public function recolte(): BelongsTo
    {
        return $this->belongsTo(Recolte::class);
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by pending status.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to filter by approved status.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to filter by paid status.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by reference type.
     */
    public function scopeByReferenceType($query, $referenceType)
    {
        return $query->where('reference_type', $referenceType);
    }

    /**
     * Scope a query to filter expenses by user (for agents to see only their own).
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope a query to filter expenses that need approval.
     */
    public function scopeNeedsApproval($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Check if the expense is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the expense is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the expense is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Approve the expense.
     */
    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark the expense as paid.
     */
    public function markAsPaid(User $user, string $paymentMethod = 'cash', ?\Carbon\Carbon $paymentDate = null): void
    {
        $this->update([
            'status' => 'paid',
            'payment_method' => $paymentMethod,
            'payment_date' => $paymentDate ?? now()->toDateString(),
            'paid_by' => $user->id,
            'paid_at' => now(),
        ]);
    }

    /**
     * Reject the expense.
     */
    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
        ]);
    }

    /**
     * Create expense from salary payment.
     */
    public static function createFromSalaryPayment(SalaryPayment $salaryPayment): self
    {
        return self::create([
            'title' => "Salary Payment - {$salaryPayment->user->name} ({$salaryPayment->payment_month}/{$salaryPayment->payment_year})",
            'amount' => $salaryPayment->amount,
            'currency' => $salaryPayment->currency,
            'category' => 'salary',
            'description' => "Salary payment for {$salaryPayment->getPaymentPeriodAttribute()}",
            'expense_date' => $salaryPayment->payment_date,
            'status' => 'approved',
            'reference_type' => 'salary_payment',
            'reference_id' => $salaryPayment->id,
            'created_by' => $salaryPayment->created_by,
            'approved_by' => $salaryPayment->created_by,
            'approved_at' => now(),
        ]);
    }

    /**
     * Create expense from commission payment.
     */
    public static function createFromCommissionPayment(CommissionPayment $commissionPayment): self
    {
        return self::create([
            'title' => "Commission Payment - {$commissionPayment->user->name}",
            'amount' => $commissionPayment->amount,
            'currency' => $commissionPayment->currency,
            'category' => 'commission',
            'description' => "Commission payment for collection #{$commissionPayment->collection_id}",
            'expense_date' => $commissionPayment->payment_date,
            'status' => 'approved',
            'reference_type' => 'commission_payment',
            'reference_id' => $commissionPayment->id,
            'created_by' => $commissionPayment->created_by,
            'approved_by' => $commissionPayment->created_by,
            'approved_at' => now(),
        ]);
    }
}
