<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PointReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'required_points',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'expires_in_days',
        'usage_limit_per_user',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'required_points' => 'integer',
            'discount_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'expires_in_days' => 'integer',
            'usage_limit_per_user' => 'integer',
        ];
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(UserVoucher::class);
    }
}
