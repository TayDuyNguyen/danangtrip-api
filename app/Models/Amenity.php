<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Amenity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'category',
    ];

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_amenities')
            ->withPivot('created_at');
    }
}
