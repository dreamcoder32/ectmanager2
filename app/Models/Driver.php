<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'vehicle_info',
        'is_active',
        // Commission fields
        'commission_rate',
        'commission_type',
        'commission_is_active',
        'state_id',
        // Contract-related fields
        'birth_date',
        'birth_place',
        'address',
        'contract_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'commission_is_active' => 'boolean',
        // Contract-related casts
        'birth_date' => 'date',
        'contract_date' => 'date',
    ];

    /**
     * Get the state mappings for the driver.
     */
    public function stateMappings(): HasMany
    {
        return $this->hasMany(StateMapping::class);
    }

    /**
     * Get the collections for the driver.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Get the assigned parcels for the driver.
     */
    public function assignedParcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'assigned_driver_id');
    }

    /**
     * Get the expenses for the driver.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Driver's state.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Scope a query to only include active drivers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
