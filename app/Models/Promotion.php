<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Promotion
 * Model cho bảng promotions — mã giảm giá / khuyến mãi.
 */
final class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_per_user' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Check if the promotion is currently valid (active + within date range + usage limit).
     * (Kiểm tra khuyến mãi có đang hợp lệ không)
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount for a given order total.
     * (Tính số tiền được giảm cho một đơn hàng)
     */
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

            return $discount;
        }

        // fixed
        return min((float) $this->discount_value, $orderTotal);
    }
}
