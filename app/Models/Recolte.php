<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Recolte extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'note',
        'created_by',
        'company_id',
        'manual_amount',
        'amount_discrepancy_note',
        'transfer_request_id',
    ];

    protected $casts = [
        'manual_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recolte) {
            $recolte->code = self::generateUniqueCode();
        });
    }

    /**
     * Generate a unique 6-digit code
     */
    private static function generateUniqueCode()
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the collections associated with this recolte
     */
    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'recolte_collections');
    }

    /**
     * The user who created the recolte
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The company this recolte belongs to
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the expenses associated with this recolte
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get the transfer request associated with this recolte
     */
    public function transferRequest()
    {
        return $this->belongsTo(TransferRequest::class);
    }
}
