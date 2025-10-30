<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParcelMessage extends Model
{
    protected $fillable = [
        'parcel_id',
        'user_id',
        'message_type',
        'message_status',
        'message_content',
        'phone_number',
        'sent_at',
        'whatsapp_message_id',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the parcel that owns the message.
     */
    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    /**
     * Get the user that sent the message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by message type.
     */
    public function scopeByMessageType($query, $messageType)
    {
        return $query->where('message_type', $messageType);
    }

    /**
     * Scope a query to filter by message status.
     */
    public function scopeByMessageStatus($query, $messageStatus)
    {
        return $query->where('message_status', $messageStatus);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by parcel.
     */
    public function scopeByParcel($query, $parcelId)
    {
        return $query->where('parcel_id', $parcelId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }

    /**
     * Check if the message was sent successfully.
     */
    public function wasSent(): bool
    {
        return in_array($this->message_status, ['sent', 'delivered', 'read']);
    }

    /**
     * Check if the message failed.
     */
    public function hasFailed(): bool
    {
        return $this->message_status === 'failed';
    }

    /**
     * Get formatted sent time.
     */
    public function getFormattedSentAtAttribute(): string
    {
        return $this->sent_at->format('Y-m-d H:i:s');
    }

    /**
     * Check if this is an incoming message.
     */
    public function isIncoming(): bool
    {
        return $this->message_type === 'incoming';
    }

    /**
     * Check if this is an outgoing message.
     */
    public function isOutgoing(): bool
    {
        return $this->message_type === 'outgoing';
    }

    /**
     * Get the sender name (user or customer).
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isOutgoing() && $this->user) {
            return $this->user->display_name ?? $this->user->email;
        }
        
        if ($this->isIncoming() && $this->parcel) {
            return $this->parcel->recipient_name;
        }
        
        return 'Unknown';
    }

    /**
     * Get the sender type.
     */
    public function getSenderTypeAttribute(): string
    {
        if ($this->isOutgoing()) {
            return 'agent';
        }
        
        return 'customer';
    }

    /**
     * Scope to get only incoming messages.
     */
    public function scopeIncoming($query)
    {
        return $query->where('message_type', 'incoming');
    }

    /**
     * Scope to get only outgoing messages.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('message_type', 'outgoing');
    }

    /**
     * Scope to get messages for a specific parcel.
     */
    public function scopeForParcel($query, $parcelId)
    {
        return $query->where('parcel_id', $parcelId);
    }
}