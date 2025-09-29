<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoneyCase extends Model
{
    protected $fillable = [
        'name',
        'description',
        'balance',
        'status',
        'last_active_by',
        'last_activated_at'
    ];

    protected $casts = [
        'last_activated_at' => 'datetime',
    ];

    protected $appends = [
        'calculated_balance'
    ];

    /**
     * Get the collections for this money case.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'case_id');
    }

    /**
     * Get the expenses for this money case.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'case_id');
    }

    /**
     * Get the user who last activated this money case.
     */
    public function lastActiveUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_active_by');
    }

    /**
     * Calculate the current balance based on collections and expenses.
     */
    public function calculateBalance(): float
    {
        // Only include collections that haven't been recolted
        $totalCollections = $this->collections()
            ->whereDoesntHave('recoltes')
            ->sum('amount');
        $totalExpenses = $this->expenses()->sum('amount');
        
        return $totalCollections - $totalExpenses;
    }

    /**
     * Get the calculated balance attribute.
     */
    public function getCalculatedBalanceAttribute(): float
    {
        $balance = $this->calculateBalance();
        return (float) $balance;
    }

    /**
     * Update the balance field with the calculated balance.
     */
    public function updateBalance(): void
    {
        $this->update(['balance' => $this->calculateBalance()]);
    }

    /**
     * Activate this money case for a specific user.
     */
    public function activateForUser(int $userId): void
    {
        $this->update([
            'last_active_by' => $userId,
            'last_activated_at' => now(),
            'status' => 'active'
        ]);
    }

    /**
     * Check if this money case is active for a specific user.
     */
    public function isActiveForUser(int $userId): bool
    {
        return $this->status === 'active' && $this->last_active_by === $userId;
    }

    /**
     * Get active money cases for a specific user.
     */
    public static function getActiveForUser(int $userId)
    {
        return static::where('status', 'active')
            ->where('last_active_by', $userId)
            ->get();
    }
}
