<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'value_type',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Helper to cast setting value safely based on value_type column.
     * (Helper để chuyển đổi kiểu dữ liệu an toàn của setting theo cột value_type)
     */
    public function getCastValueAttribute(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        return match ($this->value_type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => is_numeric($this->value) ? (float) $this->value : 0,
            'json' => json_decode($this->value, true),
            default => (string) $this->value,
        };
    }
}
