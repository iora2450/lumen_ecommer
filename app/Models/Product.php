<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    public const PROMOTION_INACTIVE = 'inactive';

    public const PROMOTION_ACTIVE = 'active';

    public const PROMOTION_SCHEDULED = 'scheduled';

    public const PROMOTION_EXPIRED = 'expired';

    public const PROMOTION_INVALID = 'invalid';

    protected $fillable = [
        'lumen_id', 'sku', 'name', 'slug',
        'short_description', 'description',
        'price', 'compare_at_price', 'cost', 'currency',
        'qty', 'available_qty', 'reserved_qty', 'backorder_qty', 'stock_status',
        'image_url',
        'category_id', 'brand_id',
        'is_featured', 'is_promotion', 'promotion_price',
        'promotion_starts_at', 'promotion_ends_at',
        'specs', 'tags', 'certifications',
        'weight', 'dimensions', 'upc', 'mpn',
        'is_active', 'synced_at', 'erp_last_sync_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'promotion_price' => 'decimal:2',
        'qty' => 'integer',
        'available_qty' => 'integer',
        'reserved_qty' => 'integer',
        'backorder_qty' => 'integer',
        'is_featured' => 'boolean',
        'is_promotion' => 'boolean',
        'is_active' => 'boolean',
        'specs' => 'array',
        'tags' => 'array',
        'certifications' => 'array',
        'synced_at' => 'datetime',
        'erp_last_sync_at' => 'datetime',
        'promotion_starts_at' => 'datetime',
        'promotion_ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-'.strtoupper(Str::random(8));
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

    public function technicalSpecs(): HasMany
    {
        return $this->hasMany(ProductTechnicalSpec::class)->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class)->orderBy('sort_order');
    }

    public function relatedProducts(): HasMany
    {
        return $this->hasMany(ProductRelated::class)->orderBy('sort_order');
    }

    public function getEffectivePriceAttribute(): float
    {
        return $this->promotion_status === self::PROMOTION_ACTIVE
            ? (float) $this->promotion_price
            : (float) $this->price;
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->promotion_status === self::PROMOTION_ACTIVE;
    }

    public function getPromotionStatusAttribute(): string
    {
        if (! $this->is_promotion) {
            return self::PROMOTION_INACTIVE;
        }

        $price = (float) $this->price;
        $promotionPrice = (float) $this->promotion_price;

        if ($price <= 0 || $promotionPrice <= 0 || $promotionPrice >= $price) {
            return self::PROMOTION_INVALID;
        }

        $now = now();

        if ($this->promotion_starts_at && $this->promotion_starts_at->gt($now)) {
            return self::PROMOTION_SCHEDULED;
        }

        if ($this->promotion_ends_at && $this->promotion_ends_at->lt($now)) {
            return self::PROMOTION_EXPIRED;
        }

        return self::PROMOTION_ACTIVE;
    }

    public function getPromotionStatusLabelAttribute(): string
    {
        return match ($this->promotion_status) {
            self::PROMOTION_ACTIVE => 'Oferta activa',
            self::PROMOTION_SCHEDULED => 'Programada',
            self::PROMOTION_EXPIRED => 'Vencida',
            self::PROMOTION_INVALID => 'Revisar oferta',
            default => 'Sin oferta',
        };
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (! $this->is_on_sale) {
            return null;
        }

        return (int) round((1 - ((float) $this->promotion_price / (float) $this->price)) * 100);
    }

    public function getStockBadgeAttribute(): string
    {
        if ($this->stock_status) {
            return $this->stock_status;
        }

        return match (true) {
            $this->qty <= 0 => 'out_of_stock',
            $this->qty < 10 => 'low_stock',
            default => 'in_stock',
        };
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->plainText($this->description);
    }

    public function getDisplayImageUrlAttribute(): ?string
    {
        $image = trim((string) $this->image_url);

        if ($image === '') {
            return null;
        }

        $image = trim(explode(',', $image)[0]);

        if (preg_match('#^https?://#i', $image)) {
            return $this->encodeUrlPath($image);
        }

        if (str_starts_with($image, '/')) {
            return asset($image);
        }

        if (str_contains($image, '/')) {
            return asset($image);
        }

        $baseUrl = rtrim((string) config('erp.legacy_public_url'), '/');
        $imagePath = trim((string) config('erp.legacy_product_image_path'), '/');

        return $baseUrl.'/'.$imagePath.'/'.$this->encodePath($image);
    }

    protected function plainText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?>/i', ' ', $text);
        $text = preg_replace('/<\s*\/?(p|div|span|li|ul|ol)[^>]*>/i', ' ', $text);
        $text = strip_tags($text);
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text) ?: null;
    }

    protected function encodeUrlPath(string $url): string
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['path'])) {
            return $url;
        }

        $encodedPath = $this->encodePath($parts['path']);
        $rebuilt = ($parts['scheme'] ?? 'http').'://'.($parts['host'] ?? '');
        $rebuilt .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $rebuilt .= $encodedPath;
        $rebuilt .= isset($parts['query']) ? '?'.$parts['query'] : '';

        return $rebuilt;
    }

    protected function encodePath(string $path): string
    {
        return collect(explode('/', $path))
            ->map(fn ($segment) => $segment === '' ? '' : rawurlencode($segment))
            ->implode('/');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisibleOnWeb($query)
    {
        return $query
            ->active()
            ->where(function ($query) {
                $query->whereNull('category_id')
                    ->orWhereHas('category', fn ($category) => $category->active());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->active();
    }

    public function scopeInStock($query)
    {
        return $query->where('qty', '>', 0);
    }

    public function scopeOnSale($query, $at = null)
    {
        $at ??= now();

        return $query
            ->where('is_promotion', true)
            ->whereNotNull('promotion_price')
            ->where('promotion_price', '>', 0)
            ->whereColumn('promotion_price', '<', 'price')
            ->where(function ($query) use ($at) {
                $query->whereNull('promotion_starts_at')
                    ->orWhere('promotion_starts_at', '<=', $at);
            })
            ->where(function ($query) use ($at) {
                $query->whereNull('promotion_ends_at')
                    ->orWhere('promotion_ends_at', '>=', $at);
            });
    }
}
