<?php

namespace Tests\Feature;

use App\Models\{Coupon, ErpOrderDelivery, Product, Quote};
use App\Services\Erp\ErpSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PurchaseTaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_checkout_and_snapshot_match_with_mixed_tax_and_rounding(): void
    {
        Mail::fake();
        $taxed = Product::create(['lumen_id' => 1, 'sku' => 'TAXED', 'name' => 'Gravado', 'price' => 0.05, 'tax_rate' => 13, 'qty' => 10, 'is_active' => true]);
        $exempt = Product::create(['lumen_id' => 2, 'sku' => 'EXEMPT', 'name' => 'Exento', 'price' => 0.05, 'tax_rate' => 0, 'qty' => 10, 'is_active' => true]);
        $coupon = Coupon::create(['code' => 'CENTAVO', 'name' => 'Prueba', 'discount_type' => 'fixed', 'discount_value' => 0.01, 'is_active' => true]);
        $cart = [];
        foreach ([$taxed, $exempt] as $product) {
            $cart['product-'.$product->id] = ['type' => 'product', 'id' => $product->id, 'qty' => 1];
        }
        $data = ['coupon_code' => 'CENTAVO', 'items' => ['product-'.$taxed->id => ['qty' => 3]],
            'customer_name' => 'Comprador', 'customer_email' => 'buyer@example.com', 'customer_phone' => '22223333',
            'shipping_address' => 'Dirección', 'delivery_method' => 'delivery', 'total' => 999, 'tax_rate' => 0];
        $preview = $this->withSession(['cart.items' => $cart])->postJson(route('cart.totals'), $data)
            ->assertOk()->assertJsonPath('subtotal', 0.2)->assertJsonPath('tax_amount', 0.02)->assertJsonPath('total', 0.21)
            ->assertJsonPath('items.0.discount_amount', 0.01)->assertJsonPath('items.1.tax_amount', 0);
        $this->assertSame(0, (int) $coupon->fresh()->times_used);
        $this->assertDatabaseCount('quotes', 0);
        $this->get(route('cart.index'))->assertOk()->assertSee('Subtotal sin IVA')->assertSee('data-summary-tax', false);
        $this->post(route('cart.checkout'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $quote = Quote::with('items')->sole();
        $this->assertSame('0.21', $quote->total);
        $this->assertSame('0.02', $quote->tax_amount);
        $this->assertEquals($preview->json('total'), $quote->total);
        $payload = ErpOrderDelivery::sole()->payload;
        $this->assertFalse($payload['prices_include_tax']);
        $this->assertSame('0.16', $payload['items'][0]['total']);
        $this->assertSame('0.05', $payload['items'][1]['total']);
        $this->assertSame('0.00', $payload['items'][1]['tax_rate']);
        $this->get(route('cart.success', $quote->quote_number))->assertOk()->assertSee('IVA')->assertSee('$0.21');
    }

    public function test_one_hundred_dollars_adds_thirteen_iva_and_invalid_coupon_is_rejected(): void
    {
        $product = Product::create(['sku' => 'TEST', 'name' => 'Producto', 'price' => 100, 'qty' => 10, 'is_active' => true]);
        $this->withSession(['cart.items' => ['product-'.$product->id => ['type' => 'product', 'id' => $product->id, 'qty' => 1]]])
            ->postJson(route('cart.totals'))->assertOk()->assertJsonPath('tax_amount', 13)->assertJsonPath('total', 113);
        $response = $this->postJson(route('cart.totals'), ['coupon_code' => 'NO-EXISTE']);
        $this->assertSame(422, $response->status(), $response->getContent());
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_zero_total_coupon_cannot_create_an_undeliverable_purchase(): void
    {
        Mail::fake();
        $product = Product::create(['sku' => 'FREE', 'name' => 'Producto', 'price' => 100, 'qty' => 10, 'is_active' => true]);
        $coupon = Coupon::create(['code' => 'TODO', 'name' => 'Prueba', 'discount_type' => 'percentage', 'discount_value' => 100, 'is_active' => true]);
        $this->withSession(['cart.items' => ['product-'.$product->id => ['type' => 'product', 'id' => $product->id, 'qty' => 1]]])
            ->post(route('cart.checkout'), ['coupon_code' => 'TODO', 'customer_name' => 'Comprador', 'customer_email' => 'buyer@example.com',
                'customer_phone' => '22223333', 'shipping_address' => 'Dirección', 'delivery_method' => 'delivery'])
            ->assertSessionHasErrors('coupon_code');
        $this->assertDatabaseCount('quotes', 0);
        $this->assertDatabaseCount('erp_order_deliveries', 0);
        $this->assertSame(0, (int) $coupon->fresh()->times_used);
        Mail::assertNothingSent();
    }

    public function test_catalog_sync_preserves_exemption_and_rejects_unsupported_rates(): void
    {
        $sync = app(ErpSyncService::class);
        $data = ['erp_id' => 101, 'sku' => 'EXENTO', 'name' => 'Exento', 'price' => 100, 'tax_rate' => 0];
        $sync->syncProducts([$data]);
        $this->assertSame('0.00', Product::sole()->tax_rate);
        unset($data['tax_rate']);
        $sync->syncProducts([$data]);
        $this->assertSame('0.00', Product::sole()->tax_rate);
        $data['tax_rate'] = 15;
        $sync->syncProducts([$data]);
        $this->assertSame('0.00', Product::sole()->tax_rate);
    }
}
