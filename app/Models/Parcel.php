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
        'has_whatsapp_tag',
        'recipient_phone_whatsapp',
        'secondary_phone_whatsapp',
        'whatsapp_verified_at',
        'notes',
        'reference',
        'secondary_phone',
        'collected_at',
        'delivered_at',
        'parcel_creation_date',
        'assigned_driver_id',
        'description',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'declared_value' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'has_whatsapp_tag' => 'boolean',
        'recipient_phone_whatsapp' => 'boolean',
        'secondary_phone_whatsapp' => 'boolean',
        'whatsapp_verified_at' => 'datetime',
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
        'parcel_creation_date' => 'datetime',
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
     * Get the messages for the parcel.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ParcelMessage::class);
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

    /**
     * Check if WhatsApp phone verification has been completed.
     */
    public function isWhatsAppVerified(): bool
    {
        return !is_null($this->whatsapp_verified_at);
    }

    /**
     * Check if recipient phone is on WhatsApp.
     */
    public function hasRecipientPhoneWhatsApp(): bool
    {
        return $this->recipient_phone_whatsapp === true;
    }

    /**
     * Check if secondary phone is on WhatsApp.
     */
    public function hasSecondaryPhoneWhatsApp(): bool
    {
        return $this->secondary_phone_whatsapp === true;
    }

    /**
     * Check if any phone number is on WhatsApp.
     */
    public function hasAnyPhoneWhatsApp(): bool
    {
        return $this->hasRecipientPhoneWhatsApp() || $this->hasSecondaryPhoneWhatsApp();
    }

    /**
     * Get the best phone number for WhatsApp (prefer recipient phone).
     */
    public function getBestWhatsAppPhone(): ?string
    {
        if ($this->hasRecipientPhoneWhatsApp() && !empty($this->recipient_phone)) {
            return $this->recipient_phone;
        }
        
        if ($this->hasSecondaryPhoneWhatsApp() && !empty($this->secondary_phone)) {
            return $this->secondary_phone;
        }
        
        return null;
    }
}
