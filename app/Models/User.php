<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'bio',
        'experience_years',
        'certifications',
        'specialties',
        'rating',
        'is_available',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'certifications' => 'array',
            'specialties' => 'array',
            'rating' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    /**
     * Check if user is a guide
     */
    public function isGuide(): bool
    {
        return $this->hasRole('guide');
    }

    /**
     * Check if user is a regular user
     */
    public function isUser(): bool
    {
        return $this->hasRole('user');
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user has admin privileges (admin or super_admin)
     */
    public function hasAdminPrivileges(): bool
    {
        return $this->hasAnyRole(['admin', 'super_admin']);
    }

    /**
     * Bookings created by this user
     */
    public function createdBookings()
    {
        return $this->hasMany(Booking::class, 'created_by');
    }

    /**
     * Bookings this user is participating in
     */
    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_users')
                    ->withPivot(['is_creator', 'status', 'joined_at'])
                    ->withTimestamps();
    }

    /**
     * Payments made by this user
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Bookings where this user is the guide
     */
    public function guidedBookings()
    {
        return $this->hasMany(Booking::class, 'guide_id');
    }

    /**
     * Check if user is a guide
     */
    public function isGuideRole(): bool
    {
        return $this->hasRole('guide');
    }

    /**
     * Check if guide is available on a specific date
     */
    public function isAvailableOn(string $date): bool
    {
        if (!$this->hasRole('guide')) {
            return false;
        }
        
        return $this->is_available && 
               !$this->guidedBookings()
                    ->where('booking_date', $date)
                    ->whereIn('status', ['confirmed'])
                    ->exists();
    }

    /**
     * Get experience level based on years
     */
    public function getExperienceLevelAttribute(): string
    {
        if (!$this->experience_years) {
            return 'New';
        }
        
        return match (true) {
            $this->experience_years >= 10 => 'Expert',
            $this->experience_years >= 5 => 'Advanced',
            $this->experience_years >= 2 => 'Intermediate',
            default => 'Beginner'
        };
    }

    /**
     * Scope to get only guides
     */
    public function scopeGuides($query)
    {
        return $query->role('guide');
    }

    /**
     * Scope to get available guides
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
