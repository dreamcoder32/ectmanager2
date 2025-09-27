<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Margin extends Model
{
    protected $fillable = [
        'company_id',
        'commune',
        'margin_amount',
        'is_active',
    ];

    protected $casts = [
        'margin_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the margin.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include active margins.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by commune.
     */
    public function scopeByCommune($query, $commune)
    {
        return $query->where('commune', $commune);
    }
}
