<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'point_reward_id',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'expires_at',
        'used_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'point_reward_id' => 'integer',
            'discount_value' => 'decimal:2',
            'max_discount_amount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(PointReward::class, 'point_reward_id');
    }

    public function isValidForUser(int $userId): bool
    {
        if ($this->user_id !== $userId || $this->status !== 'active') {
            return false;
        }

        if ($this->used_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function calculateDiscount(float $orderTotal): float
    {
        if ($orderTotal < (float) $this->min_order_amount) {
            return 0.0;
        }

        if ($this->discount_type === 'percent') {
            $discount = $orderTotal * ((float) $this->discount_value / 100);

            if ($this->max_discount_amount !== null) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }

            return min($discount, $orderTotal);
        }

        return min((float) $this->discount_value, $orderTotal);
    }
}
