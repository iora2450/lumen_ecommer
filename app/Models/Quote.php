<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_REVIEWED  = 'reviewed';
    public const STATUS_RESPONDED = 'responded';
    public const STATUS_CLOSED    = 'closed';

    public const TYPE_QUOTE = 'quote';
    public const TYPE_PURCHASE = 'purchase';

    protected $fillable = [
        'quote_number', 'request_type', 'customer_name', 'customer_email',
        'customer_phone', 'customer_company', 'shipping_address',
        'subtotal', 'notes', 'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->quote_number)) {
                $quote->quote_number = 'Q-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return $this->request_type === self::TYPE_PURCHASE ? 'Compra' : 'Cotización';
    }
}
