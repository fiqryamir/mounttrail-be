<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Mount extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'max_participants',
        'location',
        'altitude',
        'duration_days',
        'images',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'altitude' => 'decimal:2',
    ];

    public function trails(): HasMany
    {
        return $this->hasMany(Trail::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function activeTrails(): HasMany
    {
        return $this->trails()->where('is_active', true);
    }

    public function getAvailableGuides($date = null)
    {
        $query = User::role('guide');
        
        if ($date) {
            $query->whereDoesntHave('guidedBookings', function ($bookingQuery) use ($date) {
                $bookingQuery->where('booking_date', $date)
                           ->whereIn('status', ['confirmed']);
            });
        }
        
        return $query->get();
    }
}
