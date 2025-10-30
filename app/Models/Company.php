<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'is_active',
        'commission',
        'whatsapp_api_key',
        'whatsapp_desk_pickup_template',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission' => 'decimal:2',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        // Removed commission and whatsapp_api_key from hidden fields
        // as they are needed in the frontend
    ];

    /**
     * Get the parcels for the company.
     */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    /**
     * Get the margins for the company.
     */
    public function margins(): HasMany
    {
        return $this->hasMany(Margin::class);
    }

    /**
     * Get the parcel messages for the company.
     */
    public function parcelMessages(): HasMany
    {
        return $this->hasMany(ParcelMessage::class);
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
