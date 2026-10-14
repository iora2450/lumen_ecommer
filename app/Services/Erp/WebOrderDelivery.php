<?php

namespace App\Services\Erp;

use App\Models\ErpOrderDelivery;
use App\Models\Quote;
use Illuminate\Support\Facades\Http;

class WebOrderDelivery
{
    // Called INSIDE checkout's transaction: the order and its immutable outbox
    // entry either both commit or neither does. No network request at checkout.
    public function enqueue(Quote $quote): void
    {
        if ($quote->request_type !== Quote::TYPE_PURCHASE) {
            return;
        }
        $quote->loadMissing('items.product', 'items.variant');
        $fields = ['customer_name', 'customer_email', 'customer_phone', 'customer_company',
            'requires_fiscal_credit', 'fiscal_legal_name', 'fiscal_nit', 'fiscal_nrc',
            'fiscal_activity_code', 'fiscal_activity_description', 'fiscal_department_code',
            'fiscal_municipality_code', 'fiscal_district_code', 'fiscal_address', 'fiscal_phone',
            'fiscal_email', 'shipping_address', 'delivery_method', 'notes', 'coupon_code'];
        $customer = $quote->only($fields);
        $customer['requested_document_type'] = $quote->requires_fiscal_credit
            ? 'fiscal_credit'
            : 'consumer_final';
        ErpOrderDelivery::firstOrCreate(['quote_id' => $quote->id], ['payload' => [
            'version' => $quote->tax_amount === null ? 1 : 2,
            'source' => config('web_orders.source'),
            'external_id' => (string) $quote->id,
            'document_type' => 'receipt',
            'order_number' => $quote->quote_number,
            'currency' => 'USD',
            'customer' => $customer,
            'subtotal' => $quote->subtotal,
            'discount' => $quote->discount_amount,
            'total' => $quote->total,
            'tax' => $quote->tax_amount,
            'prices_include_tax' => $quote->tax_amount === null,
            'items' => $quote->items->map(fn ($item) => [
                'erp_product_id' => $item->product?->lumen_id,
                'erp_variant_id' => $item->variant?->lumen_variant_id,
                'sku' => $item->sku, 'name' => $item->name,
                'qty' => $item->qty, 'price' => $item->price, 'subtotal' => $item->subtotal,
                'tax_rate' => $item->tax_rate, 'discount' => $item->discount_amount,
                'tax' => $item->tax_amount, 'total' => $item->total,
            ])->all(),
        ]]);
    }

    public function sync(ErpOrderDelivery $delivery): void
    {
        if ($delivery->delivered_at) {
            return;
        }
        if (! config('web_orders.enabled')) {
            return;
        }
        if (! config('web_orders.base_url') || ! config('web_orders.token')) {
            throw new \RuntimeException('Configure WEB_ORDERS_ERP_URL y WEB_ORDERS_TOKEN.');
        }
        try {
            $client = Http::withToken(config('web_orders.token'))->acceptJson()->timeout(20)->connectTimeout(5);
            $url = rtrim(config('web_orders.base_url'), '/').'/web-orders';
            $response = $client->post($url, $delivery->payload);
            // Do not retain remote response bodies: they can contain sensitive data.
            if (! $response->successful()) {
                throw new \RuntimeException('ERP respondió HTTP '.$response->status());
            }
            $status = $response->json('data');
            if (! is_array($status) || ($status['external_id'] ?? null) !== $delivery->payload['external_id']
                || ($status['source'] ?? null) !== $delivery->payload['source']
                || ($status['document_type'] ?? null) !== 'receipt'
                || ($status['receipt_number'] ?? null) !== $delivery->payload['order_number']
                || ($status['receipt_status'] ?? null) !== 'received') {
                throw new \RuntimeException('Respuesta ERP inválida.');
            }
            $delivery->update([
                'delivered_at' => $delivery->delivered_at ?? now(), 'checked_at' => now(),
                'erp_status' => $status, 'last_error' => null,
                'next_attempt_at' => null,
                'attempts' => 0,
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                'attempts' => $delivery->attempts + 1,
                'last_error' => $e instanceof \Illuminate\Http\Client\ConnectionException
                    ? 'No se pudo conectar con lumensistema.' : mb_substr($e->getMessage(), 0, 500),
                'next_attempt_at' => now()->addSeconds(min(3600, 30 * (2 ** min(7, $delivery->attempts)))),
            ]);
        }
    }
}
