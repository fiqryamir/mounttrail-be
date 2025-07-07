<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trail extends Model
{
    protected $fillable = [
        'mount_id',
        'name',
        'description',
        'difficulty_level',
        'distance_km',
        'estimated_hours',
        'waypoints',
        'is_active',
    ];

    protected $casts = [
        'waypoints' => 'array',
        'is_active' => 'boolean',
        'distance_km' => 'decimal:2',
    ];

    public function mount(): BelongsTo
    {
        return $this->belongsTo(Mount::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getDifficultyLevelColorAttribute(): string
    {
        return match ($this->difficulty_level) {
            'easy' => 'green',
            'moderate' => 'yellow',
            'hard' => 'orange',
            'extreme' => 'red',
            default => 'gray'
        };
    }
}
