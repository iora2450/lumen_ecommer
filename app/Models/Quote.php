<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Quote extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_RESPONDED = 'responded';

    public const STATUS_CLOSED = 'closed';

    public const TYPE_QUOTE = 'quote';

    public const TYPE_PURCHASE = 'purchase';

    public const DELIVERY_PICKUP = 'pickup';

    public const DELIVERY_ADDRESS = 'delivery';

    public const PAYMENT_PENDING_COORDINATION = 'pending_coordination';

    public const PAYMENT_NOT_APPLICABLE = 'not_applicable';

    protected $fillable = [
        'quote_number', 'request_type', 'customer_name', 'customer_email',
        'customer_phone', 'customer_company', 'shipping_address', 'delivery_method',
        'payment_method', 'payment_status', 'subtotal', 'notes', 'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quote $quote) {
            if (empty($quote->quote_number)) {
                $prefix = $quote->request_type === self::TYPE_PURCHASE ? 'P' : 'Q';
                $quote->quote_number = $prefix.'-'.date('Ymd').'-'.strtoupper(Str::random(6));
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

    public function getDeliveryMethodLabelAttribute(): ?string
    {
        return match ($this->delivery_method) {
            self::DELIVERY_ADDRESS => 'Entrega a domicilio',
            self::DELIVERY_PICKUP => 'Retiro en tienda',
            default => null,
        };
    }

    public function getPaymentMethodLabelAttribute(): ?string
    {
        return match ($this->payment_method) {
            self::PAYMENT_PENDING_COORDINATION => 'Por coordinar con ventas',
            default => null,
        };
    }

    public function getPaymentStatusLabelAttribute(): ?string
    {
        return match ($this->payment_status) {
            self::PAYMENT_PENDING_COORDINATION => 'Pendiente de coordinación',
            default => null,
        };
    }
}
