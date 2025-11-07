<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelPriceChange extends Model
{
    protected $fillable = [
        "parcel_id",
        "old_price",
        "new_price",
        "changed_by",
        "reason",
    ];

    protected $casts = [
        "old_price" => "decimal:2",
        "new_price" => "decimal:2",
    ];

    /**
     * Get the parcel that this price change belongs to.
     */
    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    /**
     * Get the user who made the price change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "changed_by");
    }
}
