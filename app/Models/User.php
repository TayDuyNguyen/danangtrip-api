<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
use App\Services\BrevoMailService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

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

    public function pointBalance(): HasOne
    {
        return $this->hasOne(UserPointBalance::class);
    }

    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    public function userVouchers(): HasMany
    {
        return $this->hasMany(UserVoucher::class);
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
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
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

    /**
     * Public URL for the user's avatar (null when not set).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $avatar = $this->attributes['avatar'] ?? null;

        if (! $avatar) {
            return null;
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        return '/api/v1/media/'.ltrim($avatar, '/');
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

    public function sendPasswordResetNotification($token): void
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $resetUrl = $frontendUrl.'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ]);

        app(BrevoMailService::class)->sendMailable(
            email: $this->email,
            name: $this->full_name ?: $this->username,
            mailable: new ResetPasswordMail(
                resetUrl: $resetUrl,
                recipientName: $this->full_name ?: $this->username
            ),
            context: [
                'mail_type' => 'reset_password',
                'user_id' => $this->id,
            ],
        );
    }
}
