<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AboutUsContent extends Model
{
    protected $fillable = [
        'eyebrow',
        'title',
        'body',
        'dealer_name',
        'dealer_message',
        'is_active',
        'owner_image_path',
        'owner_bio',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(AboutUsImage::class)->orderBy('sort_order');
    }
}