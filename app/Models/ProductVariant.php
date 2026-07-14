<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'lumen_variant_id', 'sku', 'name',
        'price', 'compare_at_price', 'cost',
        'qty', 'available_qty', 'reserved_qty', 'stock_status',
        'attributes', 'is_active', 'erp_last_sync_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'qty' => 'integer',
        'available_qty' => 'integer',
        'reserved_qty' => 'integer',
        'attributes' => 'array',
        'is_active' => 'boolean',
        'erp_last_sync_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductVariantImage::class)->orderBy('sort_order');
    }
}