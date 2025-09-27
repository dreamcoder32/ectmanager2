<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Parcel extends Model
{
    protected $fillable = [
        'tracking_number',
        'company_id',
        'sender_name',
        'sender_phone',
        'sender_address',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'wilaya_code',
        'commune',
        'state_id',
        'city_id',
        'weight',
        'declared_value',
        'cod_amount',
        'delivery_fee',
        'status',
        'delivery_type',
        'notes',
        'reference',
        'secondary_phone',
        'collected_at',
        'delivered_at',
        'assigned_driver_id',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the company that owns the parcel.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the assigned driver for the parcel.
     */
    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    /**
     * Get the state that this parcel belongs to.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the city that this parcel belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Get the call logs for the parcel.
     */
    public function callLogs(): HasMany
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Get the collections for this parcel.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Get the latest collection for this parcel.
     */
    public function latestCollection(): HasOne
    {
        return $this->hasOne(Collection::class)->latest('collected_at');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by wilaya and commune.
     */
    public function scopeByLocation($query, $wilayaCode, $commune = null)
    {
        $query->where('wilaya_code', $wilayaCode);
        
        if ($commune) {
            $query->where('commune', $commune);
        }
        
        return $query;
    }

    /**
     * Scope a query to filter by assigned driver.
     */
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('assigned_driver_id', $driverId);
    }

    /**
     * Check if the parcel is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if the parcel is for stopdesk collection.
     */
    public function isStopdesk(): bool
    {
        return $this->delivery_type === 'stopdesk';
    }

    /**
     * Check if the parcel is for home delivery.
     */
    // public function isHomeDelivery(): bool
    // {
    //     return $this->delivery_type === 'home_delivery';
    // }
}
