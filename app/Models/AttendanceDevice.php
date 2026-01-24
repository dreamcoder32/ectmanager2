<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'device_model',
        'serial_number',
        'ip_address',
        'port',
        'location',
        'is_active',
        'last_sync_at',
        'sync_error',
    ];

    /**
     * Get the company that owns the device.
     */
    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get the attendance records for this device.
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'device_id');
    }

    /**
     * Scope to get only active devices.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Update the last sync timestamp.
     */
    public function updateLastSync(?string $error = null): void
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_error' => $error,
        ]);
    }

    /**
     * Get the device connection URL.
     */
    public function getConnectionUrl(): string
    {
        return "http://{$this->ip_address}:{$this->port}";
    }
}
