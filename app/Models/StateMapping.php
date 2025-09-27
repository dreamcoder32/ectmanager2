<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StateMapping extends Model
{
    protected $fillable = [
        'wilaya_code',
        'wilaya_name',
        'commune',
        'driver_id',
        'driver_cost',
        'is_active',
    ];

    protected $casts = [
        'driver_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the driver that owns the state mapping.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the parcels for this state mapping.
     */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class, 'wilaya_code', 'wilaya_code')
                    ->where('commune', $this->commune);
    }

    /**
     * Scope a query to only include active state mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by wilaya code.
     */
    public function scopeByWilaya($query, $wilayaCode)
    {
        return $query->where('wilaya_code', $wilayaCode);
    }

    /**
     * Scope a query to filter by commune.
     */
    public function scopeByCommune($query, $commune)
    {
        return $query->where('commune', $commune);
    }
}
