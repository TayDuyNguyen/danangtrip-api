<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LandingPage
 * Representing SEO Landing Pages
 */
final class LandingPage extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'landing_pages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'slug',
        'title',
        'page_type',
        'intro',
        'hero_image',
        'seo_title',
        'seo_description',
        'og_image',
        'filters',
        'content_blocks',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'content_blocks' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
