<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'minimum_subtotal',
        'starts_at',
        'ends_at',
        'usage_limit',
        'times_used',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'minimum_subtotal' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'times_used' => 'integer',
        'is_active' => 'boolean',
    ];

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function availabilityError(float $subtotal, ?CarbonInterface $now = null): ?string
    {
        $now ??= now();

        if (! $this->is_active) {
            return 'Este cupón no está activo.';
        }

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'Este cupón todavía no está vigente.';
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return 'Este cupón ya venció.';
        }

        if ($this->minimum_subtotal !== null && $subtotal < (float) $this->minimum_subtotal) {
            return 'Este cupón requiere una compra mínima de $'.number_format((float) $this->minimum_subtotal, 2).'.';
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return 'Este cupón alcanzó su límite de usos.';
        }

        return null;
    }

    public function calculateDiscount(float $subtotal): float
    {
        $discount = $this->discount_type === self::TYPE_PERCENTAGE
            ? $subtotal * ((float) $this->discount_value / 100)
            : (float) $this->discount_value;

        return round(min($subtotal, max(0, $discount)), 2);
    }

    public function getDiscountLabelAttribute(): string
    {
        return $this->discount_type === self::TYPE_PERCENTAGE
            ? rtrim(rtrim(number_format((float) $this->discount_value, 2, '.', ''), '0'), '.').' %'
            : '$'.number_format((float) $this->discount_value, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            ! $this->is_active => 'Inactivo',
            $this->starts_at && now()->lt($this->starts_at) => 'Programado',
            $this->ends_at && now()->gt($this->ends_at) => 'Vencido',
            $this->usage_limit !== null && $this->times_used >= $this->usage_limit => 'Agotado',
            default => 'Activo',
        };
    }
}
