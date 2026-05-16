<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TourCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'icon_background',
        'sort_order',
        'status', // active, inactive
    ];

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }
}
