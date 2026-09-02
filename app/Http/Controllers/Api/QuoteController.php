<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\Quotes\QuoteEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_company' => 'nullable|string|max:255',
            'shipping_address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required_without:items.*.variant_id|nullable|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.qty' => 'required|integer|min:1|max:10000',
        ]);

        try {
            $quote = DB::transaction(function () use ($validated) {
                $quote = Quote::create([
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'customer_company' => $validated['customer_company'] ?? null,
                    'shipping_address' => $validated['shipping_address'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'status' => Quote::STATUS_PENDING,
                    'subtotal' => 0,
                ]);

                $subtotal = 0;
                foreach ($validated['items'] as $item) {
                    if (! empty($item['variant_id'])) {
                        $variant = ProductVariant::findOrFail($item['variant_id']);
                        $product = $variant->product;
                        $sku = $variant->sku;
                        $name = $product->name.' — '.$variant->name;
                        $price = $variant->price;
                        $attributes = $variant->attributes;
                    } else {
                        $product = Product::findOrFail($item['product_id']);
                        $sku = $product->sku;
                        $name = $product->name;
                        $price = $product->effective_price;
                        $attributes = null;
                    }

                    $itemSubtotal = round($price * $item['qty'], 2);
                    $subtotal += $itemSubtotal;

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'product_id' => $product->id,
                        'variant_id' => $item['variant_id'] ?? null,
                        'sku' => $sku,
                        'name' => $name,
                        'price' => $price,
                        'qty' => $item['qty'],
                        'subtotal' => $itemSubtotal,
                        'attributes' => $attributes,
                    ]);
                }

                $quote->update(['subtotal' => round($subtotal, 2)]);

                return $quote->load('items');
            });

            app(QuoteEmailService::class)->send($quote);

            return response()->json([
                'success' => true,
                'message' => 'Cotización recibida. Te contactaremos pronto.',
                'quote_number' => $quote->quote_number,
                'data' => $quote,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la cotización.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(string $quoteNumber): JsonResponse
    {
        $quote = Quote::with('items')->where('quote_number', $quoteNumber)->firstOrFail();

        return response()->json(['data' => $quote]);
    }
}
