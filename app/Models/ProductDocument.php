<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductDocument extends Model
{
    public const TYPES = [
        'datasheet',
        'manual',
        'certificate',
        'ies',
        'warranty',
        'installation_guide',
    ];

    protected $fillable = [
        'product_id',
        'type',
        'title',
        'url',
        'language',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
