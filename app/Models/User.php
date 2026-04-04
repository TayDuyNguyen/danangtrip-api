<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

final class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function approvedRatings(): HasMany
    {
        return $this->hasMany(Rating::class, 'approved_by');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteLocations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'favorites');
    }

    public function views(): HasMany
    {
        return $this->hasMany(View::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function searchLogs(): HasMany
    {
        return $this->hasMany(SearchLog::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class, 'author_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class, 'created_by');
    }

    public function repliedContacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'replied_by');
    }

    /**
     * The attributes that are mass assignable.
     * (Danh sách các thuộc tính có thể gán массово)
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'avatar',
        'phone',
        'birthdate',
        'gender',
        'city',
        'role',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * (Danh sách các thuộc tính cần ẩn khi serialize)
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     * (Danh sách các thuộc tính cần cast)
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'user_id' => $this->id,
            'role' => $this->role,
        ];
    }
}
