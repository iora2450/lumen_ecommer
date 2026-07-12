<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'lumen_id', 'sku', 'name', 'slug', 'description',
        'price', 'compare_at_price', 'cost', 'qty', 'image_url',
        'category_id', 'brand_id',
        'is_featured', 'is_promotion', 'promotion_price',
        'specs', 'tags', 'certifications',
        'weight', 'dimensions', 'upc', 'mpn',
        'is_active', 'synced_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'promotion_price' => 'decimal:2',
        'qty' => 'integer',
        'is_featured' => 'boolean',
        'is_promotion' => 'boolean',
        'is_active' => 'boolean',
        'specs' => 'array',
        'tags' => 'array',
        'certifications' => 'array',
        'synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-' . strtoupper(Str::random(8));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->is_promotion && $this->promotion_price) {
            return (float) $this->promotion_price;
        }
        return (float) $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->is_promotion && $this->promotion_price && $this->promotion_price < $this->price;
    }

    public function getStockBadgeAttribute(): string
    {
        return match (true) {
            $this->qty <= 0 => 'out_of_stock',
            $this->qty < 10 => 'low_stock',
            default => 'in_stock',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->active();
    }

    public function scopeInStock($query)
    {
        return $query->where('qty', '>', 0);
    }
}