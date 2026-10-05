<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_admin_can_create_a_coupon_with_conditions(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('admin.coupons.store'), [
                'code' => ' verano20 ',
                'name' => 'Promoción de verano',
                'discount_type' => Coupon::TYPE_PERCENTAGE,
                'discount_value' => '20',
                'minimum_subtotal' => '150',
                'starts_at' => '2026-09-01T08:00',
                'ends_at' => '2026-10-31T23:59',
                'usage_limit' => '100',
                'is_active' => '1',
            ]);

        $coupon = Coupon::sole();

        $response->assertRedirect(route('admin.coupons.index'));
        $this->assertSame('VERANO20', $coupon->code);
        $this->assertSame('20.00', $coupon->discount_value);
        $this->assertSame('150.00', $coupon->minimum_subtotal);
        $this->assertSame(100, $coupon->usage_limit);
        $this->assertTrue($coupon->is_active);
    }

    public function test_checkout_applies_a_valid_percentage_coupon(): void
    {
        $product = $this->product();
        $coupon = Coupon::create([
            'code' => 'AHORRO20',
            'name' => 'Ahorro del veinte por ciento',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 20,
            'minimum_subtotal' => 150,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'usage_limit' => 5,
            'is_active' => true,
        ]);

        $response = $this->withSession(['cart.items' => $this->cartFor($product, 2)])
            ->post(route('cart.checkout'), array_merge($this->checkoutData(), [
                'coupon_code' => ' ahorro20 ',
            ]));

        $quote = Quote::sole();

        $response->assertRedirect(route('cart.success', $quote->quote_number));
        $this->assertSame('200.00', $quote->subtotal);
        $this->assertSame($coupon->id, $quote->coupon_id);
        $this->assertSame('AHORRO20', $quote->coupon_code);
        $this->assertSame('40.00', $quote->discount_amount);
        $this->assertSame('160.00', $quote->total);
        $this->assertSame(1, $coupon->fresh()->times_used);

        $this->get(route('cart.success', $quote->quote_number))
            ->assertOk()
            ->assertSee('Cupón AHORRO20')
            ->assertSee('$160.00');
    }

    public function test_checkout_rejects_a_coupon_when_minimum_purchase_is_not_met(): void
    {
        $product = $this->product();
        Coupon::create([
            'code' => 'MINIMO150',
            'name' => 'Compra mínima',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 10,
            'minimum_subtotal' => 150,
            'is_active' => true,
        ]);

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), array_merge($this->checkoutData(), [
                'coupon_code' => 'MINIMO150',
            ]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors(['coupon_code']);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_checkout_rejects_expired_and_exhausted_coupons(): void
    {
        $product = $this->product();
        $expired = Coupon::create([
            'code' => 'VENCIDO',
            'name' => 'Cupón vencido',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 10,
            'ends_at' => now()->subMinute(),
            'is_active' => true,
        ]);
        $exhausted = Coupon::create([
            'code' => 'AGOTADO',
            'name' => 'Cupón agotado',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 10,
            'usage_limit' => 1,
            'times_used' => 1,
            'is_active' => true,
        ]);

        $this->assertSame('Este cupón ya venció.', $expired->availabilityError(100));
        $this->assertSame('Este cupón alcanzó su límite de usos.', $exhausted->availabilityError(100));

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), array_merge($this->checkoutData(), ['coupon_code' => 'VENCIDO']))
            ->assertSessionHasErrors(['coupon_code']);

        $this->assertDatabaseCount('quotes', 0);
    }

    private function checkoutData(): array
    {
        return [
            'customer_name' => 'Cliente con cupón',
            'customer_email' => 'cupon@example.com',
            'customer_phone' => '2222-3333',
            'delivery_method' => Quote::DELIVERY_ADDRESS,
            'shipping_address' => 'Colonia de prueba, calle principal #1',
        ];
    }

    private function product(): Product
    {
        return Product::create([
            'sku' => 'COUPON-SKU',
            'name' => 'Producto para cupón',
            'price' => 100,
            'qty' => 10,
            'is_active' => true,
        ]);
    }

    private function cartFor(Product $product, int $quantity = 1): array
    {
        return [
            'product-'.$product->id => [
                'type' => 'product',
                'id' => $product->id,
                'qty' => $quantity,
            ],
        ];
    }
}
