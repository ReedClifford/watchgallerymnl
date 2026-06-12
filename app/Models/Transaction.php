<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $fillable = [
        'title',
        'caption',
        'transaction_date',
        'is_visible',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_visible' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(TransactionImage::class)->orderBy('sort_order');
    }

    public function firstImage()
    {
        return $this->hasOne(TransactionImage::class)->orderBy('sort_order');
    }
}