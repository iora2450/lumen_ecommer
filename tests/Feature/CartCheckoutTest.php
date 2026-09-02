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

    public function test_quote_uses_latest_cart_quantities_and_clears_the_cart(): void
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
                'customer_name' => 'Cliente Cotización',
                'customer_email' => 'cotizacion@example.com',
                'items' => [
                    'product-'.$product->id => ['qty' => 3],
                ],
            ]);

        $quote = Quote::with('items')->sole();

        $response->assertRedirect(route('quote.success', $quote->quote_number));
        $response->assertSessionMissing('cart.items');
        $this->assertSame(Quote::TYPE_QUOTE, $quote->request_type);
        $this->assertSame(Quote::PAYMENT_NOT_APPLICABLE, $quote->payment_status);
        $this->assertStringStartsWith('Q-', $quote->quote_number);
        $this->assertSame(3, $quote->items->sole()->qty);
        $this->assertSame('150.00', $quote->subtotal);

        Mail::assertSent(QuoteReceivedMail::class);
        Mail::assertSent(QuoteNotificationMail::class);

        $this->get(route('quote.success', $quote->quote_number))
            ->assertOk()
            ->assertSee('Tu cotización fue recibida');
    }

    public function test_purchase_requires_contact_and_delivery_details(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), [
                'request_type' => Quote::TYPE_PURCHASE,
                'customer_name' => 'Cliente Compra',
                'customer_email' => 'compra@example.com',
                'delivery_method' => Quote::DELIVERY_ADDRESS,
            ]);

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHasErrors(['customer_phone', 'shipping_address']);
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_purchase_is_recorded_without_charging_the_customer(): void
    {
        $product = $this->product();

        $response = $this
            ->withSession(['cart.items' => $this->cartFor($product)])
            ->post(route('cart.checkout'), [
                'request_type' => Quote::TYPE_PURCHASE,
                'customer_name' => 'Cliente Compra',
                'customer_email' => 'compra@example.com',
                'customer_phone' => '2222-3333',
                'delivery_method' => Quote::DELIVERY_PICKUP,
            ]);

        $quote = Quote::sole();

        $response->assertRedirect(route('quote.success', $quote->quote_number));
        $this->assertStringStartsWith('P-', $quote->quote_number);
        $this->assertSame(Quote::TYPE_PURCHASE, $quote->request_type);
        $this->assertSame(Quote::DELIVERY_PICKUP, $quote->delivery_method);
        $this->assertSame(Quote::PAYMENT_PENDING_COORDINATION, $quote->payment_method);
        $this->assertSame(Quote::PAYMENT_PENDING_COORDINATION, $quote->payment_status);
        $this->assertNull($quote->shipping_address);

        $this->get(route('quote.success', $quote->quote_number))
            ->assertOk()
            ->assertSee('No hemos realizado ningún cobro');
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
