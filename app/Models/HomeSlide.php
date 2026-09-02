<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSlide extends Model
{
    protected $fillable = [
        'badge',
        'title',
        'subtitle',
        'image_url',
        'link_url',
        'button_text',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayImageUrlAttribute(): string
    {
        $imageUrl = trim($this->image_url);

        if (preg_match('#^https?://#i', $imageUrl) || str_starts_with($imageUrl, '/')) {
            return $imageUrl;
        }

        return asset($imageUrl);
    }
}
