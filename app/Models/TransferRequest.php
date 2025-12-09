<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'supervisor_id',
        'admin_id',
        'status',
        'verification_code',
    ];

    protected $hidden = [
        'verification_code',
    ];

    protected $appends = ['total_amount'];

    public function getTotalAmountAttribute()
    {
        return $this->recoltes->sum(function ($recolte) {
            $expenseTotal = $recolte->expenses->sum('amount');
            $baseAmount = $recolte->manual_amount ?? $recolte->collections->sum('amount');
            return $baseAmount - $expenseTotal;
        });
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function recoltes()
    {
        return $this->hasMany(Recolte::class);
    }
}
