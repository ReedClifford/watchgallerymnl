<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WatchImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'watch_id',
        'image_path',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function watch()
    {
        return $this->belongsTo(Watch::class);
    }
}