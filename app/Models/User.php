<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

final class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        'point_balance',
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
        'email_verified_at'
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
            'point_balance' => 'integer',
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
