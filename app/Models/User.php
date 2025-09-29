<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uid',
        'display_name',
        'email',
        'password',
        'role',
        'is_active',
        'assigned_states',
        // Personal Information
        'first_name',
        'last_name',
        'date_of_birth',
        'identity_card_number',
        'national_identification_number',
        // Employment Information
        'started_working_at',
        'payment_day_of_month',
        'monthly_salary',
        'manager_id',
        // Legacy salary fields (keeping for backward compatibility)
        'salary_amount',
        'salary_currency',
        'salary_payment_day',
        'salary_start_date',
        'salary_is_active',
        // Commission fields
        'commission_rate',
        'commission_type',
        'commission_is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'assigned_states' => 'array',
            // New user management fields
            'date_of_birth' => 'date',
            'started_working_at' => 'date',
            'monthly_salary' => 'decimal:2',
            // Legacy salary fields
            'salary_amount' => 'decimal:2',
            'salary_start_date' => 'date',
            'salary_is_active' => 'boolean',
            'commission_rate' => 'decimal:2',
            'commission_is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's name (accessor for display_name).
     */
    public function getNameAttribute(): string
    {
        return $this->display_name ?? '';
    }

    /**
     * Get the call logs for the user.
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the expenses approved by the user.
     */
    public function approvedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }

    /**
     * Get the salary payments for the user.
     */
    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    /**
     * Get the commission payments for the user.
     */
    public function commissionPayments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    /**
     * Get the collections created by the user.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'created_by');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by role.
     */
    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Check if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is an agent.
     */
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    /**
     * Check if the user can access a specific state.
     */
    public function canAccessState(string $wilayaCode): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($wilayaCode, $this->assigned_states ?? []);
    }

    /**
     * Check if salary is configured and active for the user.
     */
    public function hasSalaryConfigured(): bool
    {
        return $this->salary_is_active && $this->salary_amount > 0;
    }

    /**
     * Check if commission is configured and active for the user.
     */
    public function hasCommissionConfigured(): bool
    {
        return $this->commission_is_active && $this->commission_rate > 0;
    }

    /**
     * Get the monthly salary amount for the user.
     */
    public function getMonthlySalary(): float
    {
        return $this->hasSalaryConfigured() ? (float) $this->salary_amount : 0;
    }

    /**
     * Calculate commission for a given amount.
     */
    public function calculateCommission(float $amount): float
    {
        if (!$this->hasCommissionConfigured()) {
            return 0;
        }

        if ($this->commission_type === 'percentage') {
            return ($amount * $this->commission_rate) / 100;
        }

        // For fixed_per_parcel, the amount represents the number of parcels
        return $amount * $this->commission_rate;
    }

    /**
     * Get the manager (supervisor) of this user.
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the subordinates (agents) managed by this user.
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Get the full name of the user.
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    /**
     * Check if user is a supervisor (has subordinates).
     */
    public function isSupervisor(): bool
    {
        return $this->subordinates()->exists();
    }

    /**
     * Check if user has a manager assigned.
     */
    public function hasManager(): bool
    {
        return !is_null($this->manager_id);
    }
}
