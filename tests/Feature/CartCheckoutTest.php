<?php

namespace Tests\Feature;

use App\Mail\QuoteNotificationMail;
use App\Mail\QuoteReceivedMail;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['quotes.notification_emails' => ['ventas@example.com']]);
    }

    public function test_cart_only_offers_purchase_checkout(): void
    {
        $product = $this->product();

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Finalizar compra')
            ->assertSee('Confirmar compra')
            ->assertSee('Chatea con nosotros')
            ->assertSee('+503 6033 1749')
            ->assertSee('marketing@lumens.com.sv')
            ->assertSee('https://wa.me/50360331749', false)
            ->assertSee('Entrega a domicilio')
            ->assertDontSee('Retiro en tienda')
            ->assertDontSee('value="pickup"', false)
            ->assertDontSee('Cotización')
            ->assertDontSee('name="request_type"', false);

        $this->get('/cotizar')->assertNotFound();
    }

    public function test_checkout_uses_latest_cart_quantities_and_only_creates_a_purchase(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession([
                'cart.items' => [
                    'product-'.$product->id => [
                        'type' => 'product',
                        'id' => $product->id,
                        'qty' => 1,
                    ],
                ],
            ])
            ->post(route('cart.checkout'), [
                'request_type' => Quote::TYPE_QUOTE,
                'customer_name' => 'Cliente Compra',
                'customer_email' => 'compra@example.com',
                'customer_phone' => '2222-3333',
                'delivery_method' => Quote::DELIVERY_ADDRESS,
                'shipping_address' => 'Colonia de prueba, calle principal #1',
                'items' => [
                    'product-'.$product->id => ['qty' => 3],
                ],
            ]);

        $quote = Quote::with('items')->sole();

        $response->assertRedirect(route('cart.success', $quote->quote_number));
        $response->assertSessionMissing('cart.items');
        $this->assertSame(Quote::TYPE_PURCHASE, $quote->request_type);
        $this->assertSame(Quote::PAYMENT_PENDING_COORDINATION, $quote->payment_status);
        $this->assertStringStartsWith('P-', $quote->quote_number);
        $this->assertSame(3, $quote->items->sole()->qty);
        $this->assertSame('150.00', $quote->subtotal);

        Mail::assertSent(QuoteReceivedMail::class);
        Mail::assertSent(QuoteNotificationMail::class);

        $this->get(route('cart.success', $quote->quote_number))
            ->assertOk()
            ->assertSee('Tu compra fue recibida')
            ->assertDontSee('cotización', false);
    }

    public function test_purchase_requires_contact_and_delivery_details(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), [
                'customer_name' => 'Cliente Compra',
                'customer_email' => 'compra@example.com',
                'delivery_method' => Quote::DELIVERY_ADDRESS,
            ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHasErrors(['customer_phone', 'shipping_address']);
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_purchase_is_recorded_for_home_delivery_without_charging_the_customer(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession(['cart.items' => $this->cartFor($product)])
            ->post(route('cart.checkout'), [
                'customer_name' => 'Cliente Compra',
                'customer_email' => 'compra@example.com',
                'customer_phone' => '2222-3333',
                'delivery_method' => Quote::DELIVERY_ADDRESS,
                'shipping_address' => 'Colonia de prueba, calle principal #1',
            ]);

        $quote = Quote::sole();

        $response->assertRedirect(route('cart.success', $quote->quote_number));
        $this->assertStringStartsWith('P-', $quote->quote_number);
        $this->assertSame(Quote::TYPE_PURCHASE, $quote->request_type);
        $this->assertSame(Quote::DELIVERY_ADDRESS, $quote->delivery_method);
        $this->assertSame(Quote::PAYMENT_PENDING_COORDINATION, $quote->payment_method);
        $this->assertSame(Quote::PAYMENT_PENDING_COORDINATION, $quote->payment_status);
        $this->assertSame('Colonia de prueba, calle principal #1', $quote->shipping_address);

        $this->get(route('cart.success', $quote->quote_number))
            ->assertOk()
            ->assertSee('No hemos realizado ningún cobro');
    }

    public function test_checkout_rejects_store_pickup(): void
    {
        $product = $this->product();

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), [
                'customer_name' => 'Cliente Compra',
                'customer_email' => 'compra@example.com',
                'customer_phone' => '2222-3333',
                'delivery_method' => Quote::DELIVERY_PICKUP,
                'shipping_address' => 'Colonia de prueba, calle principal #1',
            ])
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors(['delivery_method']);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_fiscal_credit_requires_dte_recipient_details(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), [
                'customer_name' => 'Cliente Fiscal',
                'customer_email' => 'cliente@example.com',
                'customer_phone' => '2222-3333',
                'delivery_method' => Quote::DELIVERY_ADDRESS,
                'shipping_address' => 'Colonia de prueba, calle principal #1',
                'requires_fiscal_credit' => '1',
            ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHasErrors([
            'fiscal_legal_name',
            'fiscal_nit',
            'fiscal_nrc',
            'fiscal_activity_code',
            'fiscal_activity_description',
            'fiscal_department_code',
            'fiscal_municipality_code',
            'fiscal_district_code',
            'fiscal_address',
            'fiscal_phone',
            'fiscal_email',
        ]);
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_fiscal_credit_details_are_saved_with_the_request(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession(['cart.items' => $this->cartFor($product)])
            ->post(route('cart.checkout'), [
                'customer_name' => 'Contacto de compras',
                'customer_email' => 'compras@example.com',
                'customer_phone' => '2222-3333',
                'delivery_method' => Quote::DELIVERY_ADDRESS,
                'shipping_address' => 'Colonia de prueba, calle principal #1',
                'requires_fiscal_credit' => '1',
                'fiscal_legal_name' => 'Cliente Fiscal, S.A. de C.V.',
                'fiscal_nit' => '0614-010199-101-2',
                'fiscal_nrc' => '123456-7',
                'fiscal_activity_code' => '46900',
                'fiscal_activity_description' => 'Venta al por mayor de otros productos',
                'fiscal_department_code' => '06',
                'fiscal_municipality_code' => '23',
                'fiscal_district_code' => '14',
                'fiscal_address' => 'Colonia de prueba, calle principal #1',
                'fiscal_phone' => '2222-3333',
                'fiscal_email' => 'facturacion@example.com',
            ]);

        $quote = Quote::sole();

        $response->assertRedirect(route('cart.success', $quote->quote_number));
        $this->assertTrue($quote->requires_fiscal_credit);
        $this->assertSame('Cliente Fiscal, S.A. de C.V.', $quote->fiscal_legal_name);
        $this->assertSame('0614-010199-101-2', $quote->fiscal_nit);
        $this->assertSame('123456-7', $quote->fiscal_nrc);
        $this->assertSame('46900', $quote->fiscal_activity_code);
        $this->assertSame('06', $quote->fiscal_department_code);
        $this->assertSame('San Salvador', $quote->fiscal_department);
        $this->assertSame('23', $quote->fiscal_municipality_code);
        $this->assertSame('SAN SALVADOR CENTRO', $quote->fiscal_municipality);
        $this->assertSame('14', $quote->fiscal_district_code);
        $this->assertSame('SAN SALVADOR', $quote->fiscal_district);
        $this->assertSame('facturacion@example.com', $quote->fiscal_email);
    }

    private function product(): Product
    {
        return Product::create([
            'sku' => 'CART-TEST-SKU',
            'name' => 'Luminaria de prueba',
            'price' => 50,
            'qty' => 12,
            'is_active' => true,
        ]);
    }

    private function cartFor(Product $product): array
    {
        return [
            'product-'.$product->id => [
                'type' => 'product',
                'id' => $product->id,
                'qty' => 1,
            ],
        ];
    }
}
