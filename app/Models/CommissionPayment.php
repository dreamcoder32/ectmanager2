<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CommissionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'collection_id',
        'amount',
        'currency',
        'commission_rate',
        'base_amount',
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
            'commission_rate' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'payment_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the commission payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the collection that earned the commission.
     */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Get the user who created the commission payment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who paid the commission payment.
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
     * Scope a query to filter by user and date range.
     */
    public function scopeByUserAndDateRange($query, $userId, $startDate, $endDate)
    {
        return $query->where('user_id', $userId)
                    ->whereBetween('payment_date', [$startDate, $endDate]);
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
    public function markAsPaid($paidById, $paymentMethod = null, $notes = null): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_by' => $paidById,
            'paid_at' => now(),
            'payment_method' => $paymentMethod ?? $this->payment_method,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Calculate commission amount based on rate and base amount.
     */
    public static function calculateCommissionAmount(float $baseAmount, float $commissionRate): float
    {
        return ($baseAmount * $commissionRate) / 100;
    }
}
