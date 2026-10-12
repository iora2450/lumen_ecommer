<?php

namespace App\Services\Quotes;

use Illuminate\Validation\ValidationException;

class PurchaseTotals
{
    public function calculate(array $items, float $discount = 0): array
    {
        $subtotal = array_sum(array_map(fn ($item) => (int) round($item['price'] * 100) * $item['qty'], $items));
        $discountCents = (int) round($discount * 100);
        if ($discountCents < 0 || $discountCents > $subtotal) {
            throw ValidationException::withMessages(['coupon_code' => 'Descuento inválido.']);
        }
        $running = $allocated = $tax = 0;
        foreach ($items as &$item) {
            $rate = (float) $item['tax_rate'];
            if (! in_array($rate, [0.0, 13.0], true)) {
                throw ValidationException::withMessages(['items' => 'Revise el impuesto del producto '.$item['sku'].'.']);
            }
            $base = (int) round($item['price'] * 100) * $item['qty'];
            $running += $base;
            $cumulative = $subtotal ? (int) round($discountCents * $running / $subtotal) : 0;
            $lineDiscount = $cumulative - $allocated;
            $allocated = $cumulative;
            $lineTax = (int) round(($base - $lineDiscount) * $rate / 100);
            $tax += $lineTax;
            $item['subtotal'] = $base / 100;
            $item['discount_amount'] = $lineDiscount / 100;
            $item['tax_amount'] = $lineTax / 100;
            $item['total'] = ($base - $lineDiscount + $lineTax) / 100;
        }
        unset($item);
        return ['items' => $items, 'subtotal' => $subtotal / 100, 'discount_amount' => $discountCents / 100,
            'tax_amount' => $tax / 100, 'total' => ($subtotal - $discountCents + $tax) / 100];
    }
}
