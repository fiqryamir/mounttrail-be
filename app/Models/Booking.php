<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'group_code',
        'mount_id',
        'trail_id',
        'guide_id',
        'created_by',
        'booking_date',
        'start_time',
        'max_participants',
        'current_participants',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_time' => 'datetime:H:i',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (!$booking->group_code) {
                $booking->group_code = self::generateGroupCode();
            }
        });
    }

    public static function generateGroupCode(): string
    {
        do {
            $code = strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (self::where('group_code', $code)->exists());

        return $code;
    }

    public function mount(): BelongsTo
    {
        return $this->belongsTo(Mount::class);
    }

    public function trail(): BelongsTo
    {
        return $this->belongsTo(Trail::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guide_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'booking_users')
                    ->withPivot(['is_creator', 'status', 'joined_at'])
                    ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function canAcceptMoreParticipants(): bool
    {
        return $this->current_participants < $this->max_participants;
    }

    public function addParticipant(User $user): bool
    {
        if (!$this->canAcceptMoreParticipants()) {
            return false;
        }

        $this->users()->attach($user->id, [
            'is_creator' => false,
            'status' => 'pending',
            'joined_at' => now(),
        ]);

        $this->increment('current_participants');
        
        return true;
    }

    public function removeParticipant(User $user): bool
    {
        if ($this->users()->where('user_id', $user->id)->first()?->pivot->is_creator) {
            return false;
        }

        $this->users()->detach($user->id);
        $this->decrement('current_participants');
        
        return true;
    }

    public function isParticipant(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'confirmed' => 'green',
            'cancelled' => 'red',
            'completed' => 'blue',
            default => 'gray'
        };
    }
}
