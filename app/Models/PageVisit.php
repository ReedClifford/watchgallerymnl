<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $fillable = [
        'visitor_id',
        'session_id',
        'page_path',
        'page_url',
        'page_title',
        'page_type',
        'referrer',
        'device',
        'user_agent',
        'ip_hash',
        'duration_seconds',
        'interactions_count',
        'is_valid',
        'is_engaged',
        'is_bot',
        'entered_at',
        'validated_at',
        'last_seen_at',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'is_engaged' => 'boolean',
        'is_bot' => 'boolean',
        'entered_at' => 'datetime',
        'validated_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}