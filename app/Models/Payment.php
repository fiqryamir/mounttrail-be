<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'billplz_bill_id',
        'billplz_url',
        'amount',
        'status',
        'payment_method',
        'paid_at',
        'billplz_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'billplz_response' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsPaid(array $billplzResponse = []): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'billplz_response' => $billplzResponse,
        ]);
    }

    public function markAsFailed(array $billplzResponse = []): void
    {
        $this->update([
            'status' => 'failed',
            'billplz_response' => $billplzResponse,
        ]);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'paid' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
