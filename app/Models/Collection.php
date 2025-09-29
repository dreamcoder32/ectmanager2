<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    protected $fillable = [
        'collected_at',
        'parcel_id',
        'created_by',
        'note',
        'amount',
        'driver_id',
        'margin',
        'driver_commission',
        'case_id',
        'amount_given'
    ];

    protected $casts = [
        'collected_at' => 'datetime',
        'amount' => 'decimal:2',
        'amount_given'=> 'decimal:2',
        'margin' => 'decimal:2',
        'driver_commission' => 'decimal:2',
    ];

    /**
     * Get the parcel that was collected.
     */
    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    /**
     * Get the user who created this collection record.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the driver who delivered the parcel (for home delivery only).
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the commission payments for this collection.
     */
    public function commissionPayments(): HasMany
    {
        return $this->hasMany(CommissionPayment::class);
    }

    /**
     * Get the expenses related to this collection.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the money case this collection belongs to.
     */
    public function moneyCase(): BelongsTo
    {
        return $this->belongsTo(MoneyCase::class, 'case_id');
    }

    /**
     * Scope a query to filter by driver.
     */
    public function scopeByDriver($query, $driverId)
    {
        return $query->where('driver_id', $driverId);
    }

    /**
     * Scope a query to filter by user who created the collection.
     */
    public function scopeByCreator($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('collected_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get collections for home delivery parcels.
     */
    public function scopeHomeDelivery($query)
    {
        return $query->whereHas('parcel', function ($q) {
            $q->where('delivery_type', 'home_delivery');
        });
    }

    /**
     * Scope a query to get collections for stop desk parcels.
     */
    public function scopeStopDesk($query)
    {
        return $query->whereHas('parcel', function ($q) {
            $q->where('delivery_type', 'stop_desk');
        });
    }

    /**
     * Get the recoltes associated with this collection
     */
    public function recoltes()
    {
        return $this->belongsToMany(Recolte::class, 'recolte_collections');
    }

    /**
     * Check if this collection is for a home delivery parcel.
     */
    public function isHomeDelivery(): bool
    {
        return $this->parcel && $this->parcel->delivery_type === 'home_delivery';
    }

    /**
     * Check if this collection has driver commission.
     */
    public function hasDriverCommission(): bool
    {
        return $this->driver_commission > 0;
    }

    /**
     * Get the net amount after deducting driver commission.
     */
    public function getNetAmountAttribute(): float
    {
        return $this->amount - ($this->driver_commission ?? 0);
    }
}
