<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant; // Added this line

class TenantPayment extends Model
{
    protected $fillable = [
        'tenant_id',
        'amount',
        'currency',
        'method',
        'reference',
        'notes',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
