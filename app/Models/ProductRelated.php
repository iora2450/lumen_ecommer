<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRelated extends Model
{
    public const RELATIONS = [
        'similar',
        'accessory',
        'replacement',
        'upsell',
        'cross_sell',
        'same_family',
        'required_component',
    ];

    protected $fillable = [
        'product_id',
        'related_erp_id',
        'related_sku',
        'relation_type',
        'sort_order',
        'resolved_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'resolved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
