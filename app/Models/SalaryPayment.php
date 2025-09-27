<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'payment_month',
        'payment_year',
        'payment_date',
        'status',
        'payment_method',
        'notes',
        'created_by',
        'paid_by',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the salary payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created the salary payment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who paid the salary payment.
     */
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Scope a query to only include pending payments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid payments.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to filter by month and year.
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->where('payment_month', $month)
                    ->where('payment_year', $year);
    }

    /**
     * Check if the payment is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Mark the payment as paid.
     */
    public function markAsPaid(User $paidBy): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_by' => $paidBy->id,
            'paid_at' => now(),
        ]);
    }

    /**
     * Get the formatted payment period.
     */
    public function getPaymentPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->payment_year, $this->payment_month);
    }
}
