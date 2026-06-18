<?php

namespace App\Models;

use App\Casts\PostgresBoolean;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PointRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'action_key',
        'name',
        'description',
        'points',
        'max_per_day',
        'requires_approval',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'max_per_day' => 'integer',
            'requires_approval' => PostgresBoolean::class,
        ];
    }
}
