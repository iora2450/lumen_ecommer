<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\Quotes\QuoteEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $this->cartItems($request);

        return view('cart.index', [
            'items' => $cart['items'],
            'subtotal' => $cart['subtotal'],
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $qty = (int) ($data['qty'] ?? 1);
        $key = 'product-'.$product->id;

        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('is_active', true)
                ->findOrFail($data['variant_id']);
            $key = 'variant-'.$variant->id;
        }

        $cart = $request->session()->get('cart.items', []);
        $cart[$key] = [
            'type' => str_starts_with($key, 'variant-') ? 'variant' : 'product',
            'id' => str_starts_with($key, 'variant-') ? (int) $data['variant_id'] : $product->id,
            'qty' => ($cart[$key]['qty'] ?? 0) + $qty,
        ];

        $request->session()->put('cart.items', $cart);

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.qty' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $cart = $request->session()->get('cart.items', []);

        foreach ($data['items'] ?? [] as $key => $item) {
            if (! isset($cart[$key])) {
                continue;
            }

            if ((int) $item['qty'] === 0) {
                unset($cart[$key]);

                continue;
            }

            $cart[$key]['qty'] = (int) $item['qty'];
        }

        $request->session()->put('cart.items', $cart);

        return back()->with('success', 'Carrito actualizado.');
    }

    public function remove(Request $request, string $key)
    {
        $cart = $request->session()->get('cart.items', []);
        unset($cart[$key]);
        $request->session()->put('cart.items', $cart);

        return back()->with('success', 'Producto eliminado del carrito.');
    }

    public function checkout(Request $request)
    {
        $quantityData = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.qty' => ['required', 'integer', 'min:0', 'max:10000'],
        ], [
            'items.*.qty.integer' => 'Cada cantidad debe ser un número entero.',
            'items.*.qty.min' => 'Las cantidades no pueden ser negativas.',
            'items.*.qty.max' => 'La cantidad máxima por producto es 10,000.',
        ]);

        if (! empty($quantityData['items'])) {
            $this->applyQuantities($request, $quantityData['items']);
        }

        $cart = $this->cartItems($request);

        if (count($cart['items']) === 0) {
            return back()->with('error', 'Agrega al menos un producto antes de finalizar.');
        }

        $validated = $request->validate([
            'request_type' => ['required', 'in:quote,purchase'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'required_if:request_type,purchase', 'string', 'max:30'],
            'customer_company' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['nullable', 'required_if:request_type,purchase', Rule::in([
                Quote::DELIVERY_ADDRESS,
                Quote::DELIVERY_PICKUP,
            ])],
            'shipping_address' => ['nullable', 'required_if:delivery_method,'.Quote::DELIVERY_ADDRESS, 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_name.required' => 'El nombre completo es obligatorio.',
            'customer_email.required' => 'El correo electrónico es obligatorio.',
            'customer_email.email' => 'Ingresa un correo electrónico válido.',
            'customer_phone.required_if' => 'El teléfono es obligatorio para confirmar una compra.',
            'delivery_method.required_if' => 'Selecciona entrega a domicilio o retiro en tienda.',
            'shipping_address.required_if' => 'La dirección es obligatoria para la entrega a domicilio.',
        ]);

        $quote = DB::transaction(function () use ($validated, $cart) {
            $quote = Quote::create([
                'request_type' => $validated['request_type'],
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_company' => $validated['customer_company'] ?? null,
                'shipping_address' => ($validated['delivery_method'] ?? null) === Quote::DELIVERY_ADDRESS
                    ? ($validated['shipping_address'] ?? null)
                    : null,
                'delivery_method' => $validated['request_type'] === Quote::TYPE_PURCHASE
                    ? $validated['delivery_method']
                    : null,
                'payment_method' => $validated['request_type'] === Quote::TYPE_PURCHASE
                    ? Quote::PAYMENT_PENDING_COORDINATION
                    : null,
                'payment_status' => $validated['request_type'] === Quote::TYPE_PURCHASE
                    ? Quote::PAYMENT_PENDING_COORDINATION
                    : Quote::PAYMENT_NOT_APPLICABLE,
                'notes' => $validated['notes'] ?? null,
                'status' => Quote::STATUS_PENDING,
                'subtotal' => 0,
            ]);

            $subtotal = 0;
            foreach ($cart['items'] as $item) {
                $itemSubtotal = round($item['price'] * $item['qty'], 2);
                $subtotal += $itemSubtotal;

                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'subtotal' => $itemSubtotal,
                    'attributes' => $item['attributes'],
                ]);
            }

            $quote->update(['subtotal' => round($subtotal, 2)]);

            return $quote->load('items');
        });

        app(QuoteEmailService::class)->send($quote);
        $request->session()->forget('cart.items');

        return redirect()
            ->route('quote.success', $quote->quote_number)
            ->with('success', $quote->request_type_label.' recibida correctamente.');
    }

    private function cartItems(Request $request): array
    {
        $cart = $request->session()->get('cart.items', []);
        $items = [];
        $subtotal = 0;

        foreach ($cart as $key => $entry) {
            $qty = max(1, (int) ($entry['qty'] ?? 1));
            $item = null;

            if (($entry['type'] ?? null) === 'variant') {
                $variant = ProductVariant::with('product')->find($entry['id'] ?? null);

                if ($variant && $variant->is_active && $variant->product?->is_active) {
                    $item = [
                        'key' => $key,
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'sku' => $variant->sku,
                        'name' => $variant->product->name.' - '.$variant->name,
                        'price' => (float) $variant->price,
                        'qty' => $qty,
                        'image_url' => $variant->product->display_image_url,
                        'attributes' => $variant->attributes,
                        'available_qty' => max(0, (int) $variant->qty),
                    ];
                }
            } else {
                $product = Product::active()->find($entry['id'] ?? null);

                if ($product) {
                    $item = [
                        'key' => $key,
                        'product_id' => $product->id,
                        'variant_id' => null,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'price' => (float) $product->effective_price,
                        'qty' => $qty,
                        'image_url' => $product->display_image_url,
                        'attributes' => null,
                        'available_qty' => max(0, (int) $product->qty),
                    ];
                }
            }

            if (! $item) {
                continue;
            }

            $item['subtotal'] = round($item['price'] * $item['qty'], 2);
            $subtotal += $item['subtotal'];
            $items[] = $item;
        }

        return [
            'items' => $items,
            'subtotal' => round($subtotal, 2),
        ];
    }

    private function applyQuantities(Request $request, array $items): void
    {
        $cart = $request->session()->get('cart.items', []);

        foreach ($items as $key => $item) {
            if (! isset($cart[$key])) {
                continue;
            }

            $qty = (int) $item['qty'];

            if ($qty === 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['qty'] = $qty;
            }
        }

        $request->session()->put('cart.items', $cart);
    }
}
