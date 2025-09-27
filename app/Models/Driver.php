<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'vehicle_info',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
     * Scope a query to only include active drivers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
