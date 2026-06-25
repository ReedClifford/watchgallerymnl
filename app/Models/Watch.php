<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Watch extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model_name',
        'reference_number',
        'condition',
        'description',
        'movement',
        'case_size',
        'case_material',
        'dial_color',
        'crystal',
        'bracelet_or_strap',
        'water_resistance',
        'box_papers',
        'capital_price',
        'selling_price',
        'discounted_price',
        'status',
        'is_visible',
        'is_featured',
        'sold_price',
        'date_sold',
        'buyer_name',
        'gender',
        'suggested_srp',
        'is_in_demand',
    ];

    protected $casts = [
        'capital_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'sold_price' => 'decimal:2',
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'date_sold' => 'date',
        'is_in_demand' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(WatchImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(WatchImage::class)->where('is_primary', true);
    }
}