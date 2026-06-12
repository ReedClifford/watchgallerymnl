<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AboutUsImage extends Model
{
    protected $fillable = [
        'about_us_content_id',
        'image_path',
        'caption',
        'sort_order',
        'is_primary',
    ];

    public function aboutUsContent(): BelongsTo
    {
        return $this->belongsTo(AboutUsContent::class);
    }
}