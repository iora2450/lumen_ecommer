<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\Quotes\QuoteEmailService;
use App\Support\EconomicActivityCatalog;
use App\Support\GeographicCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(
        private EconomicActivityCatalog $economicActivities,
        private GeographicCatalog $geographicCatalog,
    ) {}

    public function index(Request $request)
    {
        $cart = $this->cartItems($request);

        return view('cart.index', [
            'items' => $cart['items'],
            'subtotal' => $cart['subtotal'],
            'economicActivities' => $this->economicActivities->all(),
            'departments' => $this->geographicCatalog->departments(),
            'municipalities' => $this->geographicCatalog->municipalities(),
            'districts' => $this->geographicCatalog->districts(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless(Product::visibleOnWeb()->whereKey($product->getKey())->exists(), 404);

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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_company' => ['nullable', 'string', 'max:255'],
            'requires_fiscal_credit' => ['nullable', 'boolean'],
            'fiscal_legal_name' => ['nullable', 'required_if:requires_fiscal_credit,1', 'string', 'max:255'],
            'fiscal_nit' => ['nullable', 'required_if:requires_fiscal_credit,1', 'string', 'max:25'],
            'fiscal_nrc' => ['nullable', 'required_if:requires_fiscal_credit,1', 'string', 'max:25'],
            'fiscal_activity_code' => [
                'nullable',
                'required_if:requires_fiscal_credit,1',
                'string',
                'max:10',
                Rule::in($this->economicActivities->codes()),
            ],
            'fiscal_activity_description' => ['nullable', 'required_if:requires_fiscal_credit,1', 'string', 'max:255'],
            'fiscal_department_code' => [
                'nullable',
                'required_if:requires_fiscal_credit,1',
                'string',
                'max:2',
                Rule::in($this->geographicCatalog->departmentCodes()),
            ],
            'fiscal_municipality_code' => [
                'nullable',
                'required_if:requires_fiscal_credit,1',
                'string',
                'max:2',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if (! $this->geographicCatalog->findMunicipality(
                        (string) $request->input('fiscal_department_code'),
                        (string) $value,
                    )) {
                        $fail('Selecciona un municipio válido para el departamento indicado.');
                    }
                },
            ],
            'fiscal_district_code' => [
                'nullable',
                'required_if:requires_fiscal_credit,1',
                'string',
                'max:2',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    if (! $this->geographicCatalog->findDistrict(
                        (string) $request->input('fiscal_department_code'),
                        (string) $value,
                    )) {
                        $fail('Selecciona un distrito válido para el departamento indicado.');
                    }
                },
            ],
            'fiscal_address' => ['nullable', 'required_if:requires_fiscal_credit,1', 'string', 'max:1000'],
            'fiscal_phone' => ['nullable', 'required_if:requires_fiscal_credit,1', 'string', 'max:30'],
            'fiscal_email' => ['nullable', 'required_if:requires_fiscal_credit,1', 'email', 'max:255'],
            'delivery_method' => ['required', Rule::in([Quote::DELIVERY_ADDRESS])],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'customer_name.required' => 'El nombre completo es obligatorio.',
            'customer_email.required' => 'El correo electrónico es obligatorio.',
            'customer_email.email' => 'Ingresa un correo electrónico válido.',
            'customer_phone.required' => 'El teléfono es obligatorio para confirmar una compra.',
            'fiscal_legal_name.required_if' => 'La razón social es obligatoria para solicitar crédito fiscal.',
            'fiscal_nit.required_if' => 'El NIT es obligatorio para solicitar crédito fiscal.',
            'fiscal_nrc.required_if' => 'El NRC es obligatorio para solicitar crédito fiscal.',
            'fiscal_activity_code.required_if' => 'El código de actividad económica es obligatorio para el DTE.',
            'fiscal_activity_code.in' => 'Selecciona una actividad económica válida del catálogo.',
            'fiscal_activity_description.required_if' => 'La actividad económica o giro es obligatoria para el DTE.',
            'fiscal_department_code.required_if' => 'El departamento fiscal es obligatorio para el DTE.',
            'fiscal_department_code.in' => 'Selecciona un departamento válido del catálogo DTE.',
            'fiscal_municipality_code.required_if' => 'El municipio fiscal es obligatorio para el DTE.',
            'fiscal_district_code.required_if' => 'El distrito fiscal es obligatorio para el DTE.',
            'fiscal_address.required_if' => 'La dirección fiscal es obligatoria para el DTE.',
            'fiscal_phone.required_if' => 'El teléfono de facturación es obligatorio para el DTE.',
            'fiscal_email.required_if' => 'El correo de facturación es obligatorio para el DTE.',
            'fiscal_email.email' => 'Ingresa un correo de facturación válido.',
            'delivery_method.required' => 'La entrega a domicilio es obligatoria.',
            'delivery_method.in' => 'Solo está disponible la entrega a domicilio.',
            'shipping_address.required' => 'La dirección es obligatoria para la entrega a domicilio.',
        ]);

        $requiresFiscalCredit = (bool) ($validated['requires_fiscal_credit'] ?? false);
        $economicActivity = $requiresFiscalCredit
            ? $this->economicActivities->find($validated['fiscal_activity_code'])
            : null;
        $validated['fiscal_activity_description'] = $economicActivity['actividad_economica'] ?? null;

        $department = $requiresFiscalCredit
            ? $this->geographicCatalog->findDepartment($validated['fiscal_department_code'])
            : null;
        $municipality = $requiresFiscalCredit
            ? $this->geographicCatalog->findMunicipality(
                $validated['fiscal_department_code'],
                $validated['fiscal_municipality_code'],
            )
            : null;
        $district = $requiresFiscalCredit
            ? $this->geographicCatalog->findDistrict(
                $validated['fiscal_department_code'],
                $validated['fiscal_district_code'],
            )
            : null;

        $validated['fiscal_department'] = $department['nombre'] ?? null;
        $validated['fiscal_municipality'] = $municipality['nombre'] ?? null;
        $validated['fiscal_district'] = $district['nombre'] ?? null;
        $validated['coupon_code'] = filled($validated['coupon_code'] ?? null)
            ? strtoupper(trim($validated['coupon_code']))
            : null;

        $quote = DB::transaction(function () use ($validated, $cart, $requiresFiscalCredit) {
            $subtotal = round(array_sum(array_map(
                fn (array $item) => round($item['price'] * $item['qty'], 2),
                $cart['items'],
            )), 2);
            $coupon = null;
            $discountAmount = 0.0;

            if ($validated['coupon_code']) {
                $coupon = Coupon::where('code', $validated['coupon_code'])->lockForUpdate()->first();

                if (! $coupon) {
                    throw ValidationException::withMessages(['coupon_code' => 'El código de cupón no existe.']);
                }

                if ($error = $coupon->availabilityError($subtotal)) {
                    throw ValidationException::withMessages(['coupon_code' => $error]);
                }

                $discountAmount = $coupon->calculateDiscount($subtotal);
            }

            $quote = Quote::create([
                'request_type' => Quote::TYPE_PURCHASE,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_company' => $validated['customer_company'] ?? null,
                'requires_fiscal_credit' => $requiresFiscalCredit,
                'fiscal_legal_name' => $requiresFiscalCredit ? $validated['fiscal_legal_name'] : null,
                'fiscal_nit' => $requiresFiscalCredit ? $validated['fiscal_nit'] : null,
                'fiscal_nrc' => $requiresFiscalCredit ? $validated['fiscal_nrc'] : null,
                'fiscal_activity_code' => $requiresFiscalCredit ? $validated['fiscal_activity_code'] : null,
                'fiscal_activity_description' => $requiresFiscalCredit ? $validated['fiscal_activity_description'] : null,
                'fiscal_department_code' => $requiresFiscalCredit ? $validated['fiscal_department_code'] : null,
                'fiscal_department' => $requiresFiscalCredit ? $validated['fiscal_department'] : null,
                'fiscal_municipality_code' => $requiresFiscalCredit ? $validated['fiscal_municipality_code'] : null,
                'fiscal_municipality' => $requiresFiscalCredit ? $validated['fiscal_municipality'] : null,
                'fiscal_district_code' => $requiresFiscalCredit ? $validated['fiscal_district_code'] : null,
                'fiscal_district' => $requiresFiscalCredit ? $validated['fiscal_district'] : null,
                'fiscal_address' => $requiresFiscalCredit ? $validated['fiscal_address'] : null,
                'fiscal_phone' => $requiresFiscalCredit ? $validated['fiscal_phone'] : null,
                'fiscal_email' => $requiresFiscalCredit ? $validated['fiscal_email'] : null,
                'shipping_address' => $validated['shipping_address'],
                'delivery_method' => $validated['delivery_method'],
                'payment_method' => Quote::PAYMENT_PENDING_COORDINATION,
                'payment_status' => Quote::PAYMENT_PENDING_COORDINATION,
                'notes' => $validated['notes'] ?? null,
                'status' => Quote::STATUS_PENDING,
                'subtotal' => $subtotal,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'discount_amount' => $discountAmount,
                'total' => round($subtotal - $discountAmount, 2),
            ]);

            foreach ($cart['items'] as $item) {
                $itemSubtotal = round($item['price'] * $item['qty'], 2);

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

            $coupon?->increment('times_used');

            return $quote->load('items');
        });

        app(QuoteEmailService::class)->send($quote);
        $request->session()->forget('cart.items');

        return redirect()
            ->route('cart.success', $quote->quote_number)
            ->with('success', 'Compra recibida correctamente.');
    }

    public function success(string $orderNumber)
    {
        $order = Quote::with('items')
            ->where('request_type', Quote::TYPE_PURCHASE)
            ->where('quote_number', $orderNumber)
            ->firstOrFail();

        return view('cart.success', compact('order'));
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
                $variant = ProductVariant::with('product.category')->find($entry['id'] ?? null);

                if ($variant && $variant->is_active && $variant->product
                    && Product::visibleOnWeb()->whereKey($variant->product_id)->exists()) {
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
                $product = Product::visibleOnWeb()->find($entry['id'] ?? null);

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
